<?php

declare(strict_types=1);

namespace Marian\Hub\Controller;

use Marian\Hub\Domain\Model\Sensor;
use Marian\Hub\Domain\Repository\ReadingRepository;
use Marian\Hub\Domain\Repository\SensorRepository;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Annotation as Extbase;

/**
 * Sensorik: Live-Dashboard aller Arduino-Messstellen und Detailansicht mit Diagramm.
 */
class SensorController extends AbstractHubController
{
    /**
     * Auswählbare Zeiträume der Detailansicht in Stunden.
     */
    private const RANGES = [24 => '24 Stunden', 168 => '7 Tage', 720 => '30 Tage'];

    public function __construct(
        private readonly SensorRepository $sensorRepository,
        private readonly ReadingRepository $readingRepository,
    ) {}

    /**
     * Kachel-Dashboard: jeder Sensor mit letztem Wert und Mini-Verlauf.
     */
    public function dashboardAction(): ResponseInterface
    {
        $projectUid = $this->intSetting('project', 0);
        $sensors = $projectUid > 0
            ? $this->sensorRepository->findByProjectUid($projectUid)
            : $this->sensorRepository->findActive();

        $tiles = [];
        foreach ($sensors as $sensor) {
            $tiles[] = $this->buildTile($sensor, 24);
        }

        $this->view->assignMultiple([
            'tiles' => $tiles,
            'sensorCount' => count($tiles),
            'onlineCount' => count(array_filter($tiles, static fn (array $tile): bool => $tile['online'])),
            'alertCount' => count(array_filter($tiles, static fn (array $tile): bool => $tile['alert'])),
        ]);

        return $this->htmlResponse();
    }

    /**
     * Ein Sensor im Detail: Diagramm, Kennzahlen, letzte Rohwerte.
     */
    #[Extbase\IgnoreValidation(['value' => 'sensor'])]
    public function showAction(?Sensor $sensor = null, int $range = 24): ResponseInterface
    {
        if ($sensor === null) {
            $this->pageNotFound('Diesen Sensor gibt es nicht.');
        }

        $hours = isset(self::RANGES[$range]) ? $range : 24;

        $this->view->assignMultiple([
            'sensor' => $sensor,
            'tile' => $this->buildTile($sensor, $hours),
            'range' => $hours,
            'ranges' => self::RANGES,
            'readings' => $this->readingRepository->findLatestBySensor($sensor, $this->intSetting('tableRows', 25)),
        ]);

        return $this->htmlResponse();
    }

    /**
     * Baut die Anzeigedaten eines Sensors zusammen: Werte, Reihe, Zustand.
     *
     * @return array{sensor: Sensor, stats: array<string, mixed>, series: string, points: array<int, array{time: int, value: float}>, latest: ?float, latestFormatted: string, minFormatted: string, maxFormatted: string, avgFormatted: string, lastAt: ?int, online: bool, alert: bool, hours: int}
     */
    private function buildTile(Sensor $sensor, int $hours): array
    {
        $stats = $this->readingRepository->getStats($sensor, $hours);
        $series = $this->readingRepository->findSeries($sensor, $hours);
        $latest = $stats['last'];

        return [
            'sensor' => $sensor,
            'stats' => $stats,
            'series' => json_encode(
                array_map(
                    static fn (array $point): array => [$point['time'] * 1000, $point['value']],
                    $series
                ),
                JSON_THROW_ON_ERROR
            ),
            'points' => $series,
            'latest' => $latest,
            'latestFormatted' => $latest !== null ? $sensor->format($latest) : '–',
            'minFormatted' => $stats['min'] !== null ? $sensor->format($stats['min']) : '–',
            'maxFormatted' => $stats['max'] !== null ? $sensor->format($stats['max']) : '–',
            'avgFormatted' => $stats['avg'] !== null ? $sensor->format($stats['avg']) : '–',
            'lastAt' => $stats['lastAt'],
            'online' => $sensor->isOnline(),
            'alert' => $latest !== null && $sensor->isOutOfRange($latest),
            'hours' => $hours,
        ];
    }
}
