<?php

declare(strict_types=1);

namespace Marian\Hub\Domain\Repository;

use Marian\Hub\Domain\Model\Project;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<Project>
 */
class ProjectRepository extends Repository
{
    protected $defaultOrderings = [
        'startedAt' => QueryInterface::ORDER_DESCENDING,
        'title' => QueryInterface::ORDER_ASCENDING,
    ];

    public function findOneBySlug(string $slug): ?Project
    {
        $query = $this->createQuery();
        $query->matching($query->equals('slug', $slug));
        $query->setLimit(1);

        return $query->execute()->getFirst();
    }

    /**
     * @param string[] $statuses
     * @return QueryResultInterface<Project>
     */
    public function findByStatuses(array $statuses, int $categoryUid = 0): QueryResultInterface
    {
        $query = $this->createQuery();
        $constraints = [];
        if ($statuses !== []) {
            $constraints[] = $query->in('status', $statuses);
        }
        if ($categoryUid > 0) {
            $constraints[] = $query->contains('categories', $categoryUid);
        }

        if ($constraints !== []) {
            $query->matching($query->logicalAnd(...$constraints));
        }

        return $query->execute();
    }

    /**
     * Projekte, nach Status gruppiert – in der Reihenfolge der Statuskonstanten.
     *
     * @return array<string, array<int, Project>>
     */
    public function findGroupedByStatus(): array
    {
        $groups = [];
        foreach (array_keys(Project::STATUSES) as $status) {
            $groups[$status] = [];
        }

        foreach ($this->findAll() as $project) {
            $groups[$project->getStatus()][] = $project;
        }

        return array_filter($groups, static fn (array $projects): bool => $projects !== []);
    }
}
