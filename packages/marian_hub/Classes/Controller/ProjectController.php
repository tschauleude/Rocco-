<?php

declare(strict_types=1);

namespace Marian\Hub\Controller;

use Marian\Hub\Domain\Model\Project;
use Marian\Hub\Domain\Repository\ProjectRepository;
use Marian\Hub\Domain\Repository\ReadingRepository;
use Marian\Hub\Domain\Repository\SensorRepository;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Annotation as Extbase;

/**
 * Projekte: Übersicht nach Status und Einzelansicht mit Logbuch, Stückliste und Sensoren.
 */
class ProjectController extends AbstractHubController
{
    public function __construct(
        private readonly ProjectRepository $projectRepository,
        private readonly SensorRepository $sensorRepository,
        private readonly ReadingRepository $readingRepository,
    ) {}

    /**
     * Projektübersicht, wahlweise als flache Liste oder nach Status gruppiert.
     */
    public function listAction(int $category = 0): ResponseInterface
    {
        $grouped = (bool)($this->settings['groupByStatus'] ?? true);
        $categoryUid = $category > 0 ? $category : $this->intSetting('category', 0);

        $this->view->assignMultiple([
            'grouped' => $grouped && $categoryUid === 0,
            'groups' => $grouped && $categoryUid === 0 ? $this->projectRepository->findGroupedByStatus() : [],
            'projects' => $this->projectRepository->findByStatuses([], $categoryUid),
            'statuses' => Project::STATUSES,
            'activeCategory' => $categoryUid,
        ]);

        return $this->htmlResponse();
    }

    /**
     * Ein Projekt mit allem, was dazugehört.
     */
    #[Extbase\IgnoreValidation(['value' => 'project'])]
    public function showAction(?Project $project = null): ResponseInterface
    {
        if ($project === null) {
            $this->pageNotFound('Dieses Projekt gibt es nicht.');
        }

        $sensors = [];
        foreach ($this->sensorRepository->findByProjectUid($project->getUid() ?? 0) as $sensor) {
            $latest = $this->readingRepository->findLatestValue($sensor);
            $sensors[] = [
                'sensor' => $sensor,
                'latestFormatted' => $latest !== null ? $sensor->format($latest['value']) : '–',
                'lastAt' => $latest['time'] ?? null,
                'online' => $sensor->isOnline(),
            ];
        }

        $this->view->assignMultiple([
            'project' => $project,
            'materials' => $project->getMaterialList(),
            'sensors' => $sensors,
            'hoursSpent' => $project->getHoursSpent(),
        ]);

        return $this->htmlResponse();
    }
}
