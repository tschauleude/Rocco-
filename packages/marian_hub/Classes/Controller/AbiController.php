<?php

declare(strict_types=1);

namespace Marian\Hub\Controller;

use Marian\Hub\Domain\Model\AbiTask;
use Marian\Hub\Domain\Repository\AbiTaskRepository;
use Marian\Hub\Domain\Repository\CommitteeRepository;
use Marian\Hub\Domain\Repository\MilestoneRepository;
use Psr\Http\Message\ResponseInterface;

/**
 * Abi-Organisation: Kanban-Board, Komitees, Countdown und Terminleiste.
 */
class AbiController extends AbstractHubController
{
    public function __construct(
        private readonly AbiTaskRepository $taskRepository,
        private readonly CommitteeRepository $committeeRepository,
        private readonly MilestoneRepository $milestoneRepository,
    ) {}

    /**
     * Das Board: alle Aufgaben in ihren Spalten, plus Kennzahlen.
     */
    public function boardAction(): ResponseInterface
    {
        $forward = $this->forwardToSelectedView(['countdown', 'committees'], 'board');
        if ($forward !== null) {
            return $forward;
        }

        $board = $this->taskRepository->findGroupedByPhase();
        $counts = array_map('count', $board);
        $total = array_sum($counts);

        $this->view->assignMultiple([
            'phases' => AbiTask::PHASES,
            'board' => $board,
            'counts' => $counts,
            'totalTasks' => $total,
            'doneShare' => $total > 0 ? (int)round(($counts[AbiTask::PHASE_DONE] ?? 0) / $total * 100) : 0,
            'committees' => $this->committeeRepository->findAll(),
            'dueSoon' => $this->taskRepository->findDueWithin($this->intSetting('dueSoonDays', 14)),
            'totalBudget' => $this->taskRepository->getTotalBudget(),
            'countdownTarget' => $this->milestoneRepository->findCountdownTarget(),
        ]);

        return $this->htmlResponse();
    }

    /**
     * Countdown und Zeitstrahl der Meilensteine.
     */
    public function countdownAction(): ResponseInterface
    {
        $target = $this->milestoneRepository->findCountdownTarget();

        $this->view->assignMultiple([
            'target' => $target,
            'daysLeft' => $target?->getDaysLeft(),
            'targetTimestamp' => $target?->getDate()?->getTimestamp(),
            'milestones' => $this->milestoneRepository->findUpcoming($this->intSetting('milestoneLimit', 12)),
            'allMilestones' => $this->milestoneRepository->findAll(),
        ]);

        return $this->htmlResponse();
    }

    /**
     * Komitees mit Fortschritt und Mitgliedern.
     */
    public function committeesAction(): ResponseInterface
    {
        $this->view->assignMultiple([
            'committees' => $this->committeeRepository->findAll(),
            'totalBudget' => $this->taskRepository->getTotalBudget(),
        ]);

        return $this->htmlResponse();
    }
}
