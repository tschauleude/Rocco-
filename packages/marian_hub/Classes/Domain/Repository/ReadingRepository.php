<?php

declare(strict_types=1);

namespace Marian\Hub\Domain\Repository;

use Marian\Hub\Domain\Model\Reading;
use Marian\Hub\Domain\Model\Sensor;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Messwerte. Reihen und Kennzahlen laufen bewusst über den QueryBuilder:
 * bei zehntausenden Messungen wäre das Mapping auf Objekte reine Verschwendung.
 *
 * @extends Repository<Reading>
 */
class ReadingRepository extends Repository
{
    public const TABLE = 'tx_marianhub_domain_model_reading';

    protected $defaultOrderings = [
        'measuredAt' => QueryInterface::ORDER_DESCENDING,
    ];

    public function __construct(private readonly ConnectionPool $connectionPool)
    {
        parent::__construct();
    }

    /**
     * Die jüngsten Messungen eines Sensors als Objekte (z. B. für Tabellen).
     *
     * @return QueryResultInterface<Reading>
     */
    public function findLatestBySensor(Sensor $sensor, int $limit = 20): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->matching($query->equals('sensor', $sensor->getUid()));
        $query->setLimit($limit);

        return $query->execute();
    }

    /**
     * Messreihe der letzten Stunden, aufsteigend nach Zeit.
     *
     * @return array<int, array{time: int, value: float}>
     */
    public function findSeries(Sensor $sensor, int $hours = 24, int $maxPoints = 240): array
    {
        $since = time() - $hours * 3600;
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);

        $rows = $queryBuilder
            ->select('measured_at', 'value')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq('sensor', $queryBuilder->createNamedParameter($sensor->getUid(), Connection::PARAM_INT)),
                $queryBuilder->expr()->gte('measured_at', $queryBuilder->createNamedParameter($since, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
            )
            ->orderBy('measured_at', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        $series = array_map(
            static fn (array $row): array => ['time' => (int)$row['measured_at'], 'value' => (float)$row['value']],
            $rows
        );

        return $this->downsample($series, $maxPoints);
    }

    /**
     * Kennzahlen einer Messreihe: Anzahl, Minimum, Maximum, Mittelwert, letzter Wert.
     *
     * @return array{count: int, min: ?float, max: ?float, avg: ?float, last: ?float, lastAt: ?int}
     */
    public function getStats(Sensor $sensor, int $hours = 24): array
    {
        $since = time() - $hours * 3600;
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);

        $row = $queryBuilder
            ->selectLiteral(
                'COUNT(*) AS reading_count',
                'MIN(value) AS min_value',
                'MAX(value) AS max_value',
                'AVG(value) AS avg_value',
            )
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq('sensor', $queryBuilder->createNamedParameter($sensor->getUid(), Connection::PARAM_INT)),
                $queryBuilder->expr()->gte('measured_at', $queryBuilder->createNamedParameter($since, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
            )
            ->executeQuery()
            ->fetchAssociative();

        $latest = $this->findLatestValue($sensor);

        return [
            'count' => (int)($row['reading_count'] ?? 0),
            'min' => isset($row['min_value']) ? (float)$row['min_value'] : null,
            'max' => isset($row['max_value']) ? (float)$row['max_value'] : null,
            'avg' => isset($row['avg_value']) ? round((float)$row['avg_value'], 4) : null,
            'last' => $latest['value'] ?? null,
            'lastAt' => $latest['time'] ?? null,
        ];
    }

    /**
     * Letzter Messwert eines Sensors.
     *
     * @return array{value: float, time: int}|null
     */
    public function findLatestValue(Sensor $sensor): ?array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);

        $row = $queryBuilder
            ->select('value', 'measured_at')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq('sensor', $queryBuilder->createNamedParameter($sensor->getUid(), Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
            )
            ->orderBy('measured_at', 'DESC')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        if ($row === false) {
            return null;
        }

        return ['value' => (float)$row['value'], 'time' => (int)$row['measured_at']];
    }

    /**
     * Löscht Messwerte, die älter als die Aufbewahrungsfrist des Sensors sind.
     *
     * @return int Anzahl entfernter Zeilen
     */
    public function purgeExpired(Sensor $sensor): int
    {
        if ($sensor->getRetentionDays() <= 0) {
            return 0;
        }

        return $this->deleteOlderThan($sensor);
    }

    private function deleteOlderThan(Sensor $sensor): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);

        return (int)$queryBuilder
            ->delete(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq('sensor', $queryBuilder->createNamedParameter($sensor->getUid(), Connection::PARAM_INT)),
                $queryBuilder->expr()->lt(
                    'measured_at',
                    $queryBuilder->createNamedParameter(time() - $sensor->getRetentionDays() * 86400, Connection::PARAM_INT)
                ),
            )
            ->executeStatement();
    }

    /**
     * Dünnt eine Reihe gleichmäßig aus, damit Diagramme nicht an Datenmengen ersticken.
     *
     * @param array<int, array{time: int, value: float}> $series
     * @return array<int, array{time: int, value: float}>
     */
    private function downsample(array $series, int $maxPoints): array
    {
        $count = count($series);
        if ($maxPoints <= 0 || $count <= $maxPoints) {
            return $series;
        }

        $step = $count / $maxPoints;
        $result = [];
        for ($i = 0; $i < $maxPoints; $i++) {
            $result[] = $series[(int)floor($i * $step)];
        }

        // Der jüngste Messwert darf beim Ausdünnen nie verloren gehen.
        $result[$maxPoints - 1] = $series[$count - 1];

        return $result;
    }
}
