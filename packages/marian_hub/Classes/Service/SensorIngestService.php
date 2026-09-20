<?php

declare(strict_types=1);

namespace Marian\Hub\Service;

use Marian\Hub\Exception\IngestException;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * Nimmt Messwerte von Arduino/ESP-Geräten entgegen, prüft sie und schreibt sie weg.
 *
 * Bewusst ohne Extbase: der Weg vom HTTP-Request in die Tabelle soll kurz sein,
 * damit auch ein Sensor im Minutentakt keine Last erzeugt.
 */
class SensorIngestService
{
    private const SENSOR_TABLE = 'tx_marianhub_domain_model_sensor';
    private const READING_TABLE = 'tx_marianhub_domain_model_reading';

    /**
     * Mehr Messwerte pro Request nimmt der Server nicht an.
     */
    private const MAX_READINGS_PER_REQUEST = 200;

    /**
     * Messzeitpunkte weiter als eine Stunde in der Zukunft sind Unsinn (Uhr falsch gestellt).
     */
    private const MAX_CLOCK_SKEW = 3600;

    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Verarbeitet eine Ingest-Anfrage.
     *
     * @param array<string, mixed> $payload
     * @return array{sensor: string, stored: int, skipped: int}
     * @throws IngestException
     */
    public function ingest(array $payload, string $headerToken = ''): array
    {
        $identifier = trim((string)($payload['sensor'] ?? $payload['id'] ?? ''));
        if ($identifier === '') {
            throw new IngestException('Feld "sensor" fehlt.', 400);
        }

        $token = $headerToken !== '' ? $headerToken : (string)($payload['token'] ?? '');
        $sensor = $this->authenticate($identifier, $token);

        ['readings' => $readings, 'rejected' => $rejected] = $this->extractReadings($payload);
        if ($readings === []) {
            throw new IngestException('Kein gültiger Messwert übermittelt.', 400);
        }

        $stored = $this->store($sensor, $readings);
        $this->touchSensor((int)$sensor['uid']);

        return [
            'sensor' => $identifier,
            'stored' => $stored,
            // Verworfene Werte gehören in die Antwort: sonst sucht man auf dem
            // Gerät nach einem Fehler, den der Server längst gemeldet hat.
            'skipped' => $rejected + (count($readings) - $stored),
        ];
    }

    /**
     * Sucht den Sensor und prüft das Token in konstanter Zeit.
     *
     * Unbekannter Sensor und falsches Token liefern bewusst dieselbe Antwort,
     * damit sich über die API keine Sensorkennungen durchprobieren lassen.
     *
     * @return array<string, mixed>
     * @throws IngestException
     */
    private function authenticate(string $identifier, string $token): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::SENSOR_TABLE);
        $sensor = $queryBuilder
            ->select('uid', 'pid', 'title', 'token_hash', 'active')
            ->from(self::SENSOR_TABLE)
            ->where(
                $queryBuilder->expr()->eq('identifier', $queryBuilder->createNamedParameter($identifier)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        $expectedHash = is_array($sensor) ? (string)$sensor['token_hash'] : '';
        $givenHash = hash('sha256', $token);

        // Auch ohne Treffer wird verglichen, damit die Laufzeit nichts verrät.
        $tokenMatches = $expectedHash !== '' && hash_equals($expectedHash, $givenHash);

        if (!is_array($sensor) || $token === '' || !$tokenMatches) {
            $this->logger->warning('Abgelehnte Sensor-Anmeldung', ['sensor' => $identifier]);

            throw new IngestException('Sensor oder Token unbekannt.', 401);
        }

        if (!(bool)$sensor['active']) {
            throw new IngestException('Dieser Sensor ist deaktiviert.', 403);
        }

        return $sensor;
    }

    /**
     * Holt die Messwerte aus dem Payload – Einzelwert oder Liste.
     *
     * @param array<string, mixed> $payload
     * @return array{readings: array<int, array{value: float, measuredAt: int, payload: string}>, rejected: int}
     * @throws IngestException
     */
    private function extractReadings(array $payload): array
    {
        $now = time();
        $raw = [];

        if (isset($payload['readings']) && is_array($payload['readings'])) {
            $raw = $payload['readings'];
        } elseif (array_key_exists('value', $payload)) {
            $raw = [['value' => $payload['value'], 'measured_at' => $payload['measured_at'] ?? $now]];
        }

        if (count($raw) > self::MAX_READINGS_PER_REQUEST) {
            throw new IngestException(
                sprintf('Maximal %d Messwerte pro Anfrage.', self::MAX_READINGS_PER_REQUEST),
                413
            );
        }

        $readings = [];
        $rejected = 0;
        foreach ($raw as $entry) {
            if (!is_array($entry) || !array_key_exists('value', $entry)) {
                $rejected++;
                continue;
            }

            $value = $entry['value'];
            if (!is_numeric($value)) {
                $rejected++;
                continue;
            }

            $value = (float)$value;
            if (!is_finite($value)) {
                $rejected++;
                continue;
            }

            $measuredAt = isset($entry['measured_at']) && is_numeric($entry['measured_at'])
                ? (int)$entry['measured_at']
                : $now;

            // Geräte ohne RTC schicken gern Sekunden seit dem Boot – das wäre 1970.
            if ($measuredAt < 946_684_800 || $measuredAt > $now + self::MAX_CLOCK_SKEW) {
                $measuredAt = $now;
            }

            $extra = array_diff_key($entry, array_flip(['value', 'measured_at']));

            $readings[] = [
                'value' => round($value, 4),
                'measuredAt' => $measuredAt,
                'payload' => $extra === [] ? '' : (json_encode($extra) ?: ''),
            ];
        }

        return ['readings' => $readings, 'rejected' => $rejected];
    }

    /**
     * @param array<string, mixed> $sensor
     * @param array<int, array{value: float, measuredAt: int, payload: string}> $readings
     */
    private function store(array $sensor, array $readings): int
    {
        $connection = $this->connectionPool->getConnectionForTable(self::READING_TABLE);
        $now = time();
        $stored = 0;

        foreach ($readings as $reading) {
            $stored += $connection->insert(
                self::READING_TABLE,
                [
                    'pid' => (int)$sensor['pid'],
                    'sensor' => (int)$sensor['uid'],
                    'value' => $reading['value'],
                    'measured_at' => $reading['measuredAt'],
                    'payload' => $reading['payload'],
                    'tstamp' => $now,
                    'crdate' => $now,
                ],
                [
                    Connection::PARAM_INT,
                    Connection::PARAM_INT,
                    Connection::PARAM_STR,
                    Connection::PARAM_INT,
                    Connection::PARAM_STR,
                    Connection::PARAM_INT,
                    Connection::PARAM_INT,
                ]
            );
        }

        return $stored;
    }

    /**
     * Merkt sich, wann der Sensor zuletzt gesendet hat.
     */
    private function touchSensor(int $sensorUid): void
    {
        $this->connectionPool->getConnectionForTable(self::SENSOR_TABLE)->update(
            self::SENSOR_TABLE,
            ['last_seen' => time(), 'tstamp' => time()],
            ['uid' => $sensorUid],
            [Connection::PARAM_INT, Connection::PARAM_INT]
        );
    }

    /**
     * Erzeugt ein neues Gerätetoken und gibt Klartext und Hash zurück.
     *
     * @return array{token: string, hash: string}
     */
    public static function createToken(): array
    {
        $token = bin2hex(random_bytes(20));

        return ['token' => $token, 'hash' => hash('sha256', $token)];
    }
}
