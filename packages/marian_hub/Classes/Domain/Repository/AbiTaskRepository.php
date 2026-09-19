<?php

declare(strict_types=1);

namespace Marian\Hub\Domain\Repository;

use Marian\Hub\Domain\Model\AbiTask;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<AbiTask>
 */
class AbiTaskRepository extends Repository
{
    protected $defaultOrderings = [
        'priority' => QueryInterface::ORDER_DESCENDING,
        'dueDate' => QueryInterface::ORDER_ASCENDING,
        'title' => QueryInterface::ORDER_ASCENDING,
    ];

    /**
     * Aufgaben einer Board-Spalte.
     *
     * @return QueryResultInterface<AbiTask>
     */
    public function findByPhase(string $phase): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->matching($query->equals('phase', $phase));

        return $query->execute();
    }

    /**
     * Alle Aufgaben, nach Board-Spalte gruppiert.
     *
     * @return array<string, array<int, AbiTask>>
     */
    public function findGroupedByPhase(): array
    {
        $board = [];
        foreach (array_keys(AbiTask::PHASES) as $phase) {
            $board[$phase] = [];
        }

        foreach ($this->findAll() as $task) {
            $board[$task->getPhase()][] = $task;
        }

        return $board;
    }

    /**
     * Offene Aufgaben mit Fälligkeit innerhalb der nächsten Tage.
     *
     * @return QueryResultInterface<AbiTask>
     */
    public function findDueWithin(int $days): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->matching(
            $query->logicalAnd(
                $query->logicalNot($query->equals('phase', AbiTask::PHASE_DONE)),
                $query->greaterThan('dueDate', 0),
                $query->lessThanOrEqual('dueDate', strtotime('+' . $days . ' days')),
            )
        );

        return $query->execute();
    }

    /**
     * Summe der eingeplanten Budgets aller nicht verworfenen Aufgaben.
     */
    public function getTotalBudget(): float
    {
        $sum = 0.0;
        foreach ($this->findAll() as $task) {
            $sum += $task->getBudget();
        }

        return round($sum, 2);
    }
}
