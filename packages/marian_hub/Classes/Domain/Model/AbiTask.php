<?php

declare(strict_types=1);

namespace Marian\Hub\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Eine Aufgabe auf dem Abi-Board.
 */
class AbiTask extends AbstractEntity
{
    public const PHASE_IDEA = 'idea';
    public const PHASE_PLANNED = 'planned';
    public const PHASE_DOING = 'doing';
    public const PHASE_DONE = 'done';

    /**
     * Spalten des Kanban-Boards in Anzeigereihenfolge.
     */
    public const PHASES = [
        self::PHASE_IDEA => 'Ideen',
        self::PHASE_PLANNED => 'Geplant',
        self::PHASE_DOING => 'Läuft',
        self::PHASE_DONE => 'Erledigt',
    ];

    protected string $title = '';

    protected string $description = '';

    protected string $phase = self::PHASE_IDEA;

    /**
     * 0 = niedrig, 1 = normal, 2 = hoch, 3 = brennt.
     */
    protected int $priority = 1;

    protected ?\DateTime $dueDate = null;

    protected string $assignee = '';

    /**
     * Geschätzter Aufwand in Stunden.
     */
    protected float $estimate = 0.0;

    /**
     * Eingeplantes Budget in Euro.
     */
    protected float $budget = 0.0;

    protected ?Committee $committee = null;

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getPhase(): string
    {
        return $this->phase;
    }

    public function setPhase(string $phase): void
    {
        $this->phase = isset(self::PHASES[$phase]) ? $phase : self::PHASE_IDEA;
    }

    public function getPhaseLabel(): string
    {
        return self::PHASES[$this->phase] ?? $this->phase;
    }

    public function isDone(): bool
    {
        return $this->phase === self::PHASE_DONE;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): void
    {
        $this->priority = max(0, min(3, $priority));
    }

    public function getDueDate(): ?\DateTime
    {
        return $this->dueDate;
    }

    public function setDueDate(?\DateTime $dueDate): void
    {
        $this->dueDate = $dueDate;
    }

    public function getAssignee(): string
    {
        return $this->assignee;
    }

    public function setAssignee(string $assignee): void
    {
        $this->assignee = $assignee;
    }

    public function getEstimate(): float
    {
        return $this->estimate;
    }

    public function setEstimate(float $estimate): void
    {
        $this->estimate = $estimate;
    }

    public function getBudget(): float
    {
        return $this->budget;
    }

    public function setBudget(float $budget): void
    {
        $this->budget = $budget;
    }

    public function getCommittee(): ?Committee
    {
        return $this->committee;
    }

    public function setCommittee(?Committee $committee): void
    {
        $this->committee = $committee;
    }

    /**
     * Verbleibende Tage bis zur Fälligkeit; negativ heißt überfällig.
     */
    public function getDaysLeft(): ?int
    {
        if ($this->dueDate === null) {
            return null;
        }

        $today = new \DateTimeImmutable('today');
        $due = (new \DateTimeImmutable())->setTimestamp($this->dueDate->getTimestamp())->setTime(0, 0);

        return (int)$today->diff($due)->format('%r%a');
    }

    public function isOverdue(): bool
    {
        $daysLeft = $this->getDaysLeft();

        return !$this->isDone() && $daysLeft !== null && $daysLeft < 0;
    }
}
