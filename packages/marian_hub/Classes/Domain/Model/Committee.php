<?php

declare(strict_types=1);

namespace Marian\Hub\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Ein Komitee der Abi-Orga (Abiball, Abizeitung, Mottowoche, Kasse, ...).
 */
class Committee extends AbstractEntity
{
    protected string $title = '';

    protected string $description = '';

    protected string $lead = '';

    protected string $color = '#6366f1';

    /**
     * Mitglieder, eine Person pro Zeile.
     */
    protected string $members = '';

    /**
     * @var ObjectStorage<AbiTask>
     */
    protected ObjectStorage $tasks;

    public function __construct()
    {
        $this->tasks = new ObjectStorage();
    }

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

    public function getLead(): string
    {
        return $this->lead;
    }

    public function setLead(string $lead): void
    {
        $this->lead = $lead;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function setColor(string $color): void
    {
        $this->color = $color;
    }

    public function getMembers(): string
    {
        return $this->members;
    }

    public function setMembers(string $members): void
    {
        $this->members = $members;
    }

    /**
     * @return string[]
     */
    public function getMemberList(): array
    {
        $lines = preg_split('/\R/', trim($this->members)) ?: [];

        return array_values(array_filter(array_map('trim', $lines), static fn (string $line): bool => $line !== ''));
    }

    public function getMemberCount(): int
    {
        return count($this->getMemberList());
    }

    /**
     * @return ObjectStorage<AbiTask>
     */
    public function getTasks(): ObjectStorage
    {
        return $this->tasks;
    }

    /**
     * @param ObjectStorage<AbiTask> $tasks
     */
    public function setTasks(ObjectStorage $tasks): void
    {
        $this->tasks = $tasks;
    }

    public function addTask(AbiTask $task): void
    {
        $this->tasks->attach($task);
    }

    public function removeTask(AbiTask $task): void
    {
        $this->tasks->detach($task);
    }

    /**
     * Anteil erledigter Aufgaben in Prozent.
     */
    public function getProgress(): int
    {
        $total = count($this->tasks);
        if ($total === 0) {
            return 0;
        }

        $done = 0;
        foreach ($this->tasks as $task) {
            if ($task->isDone()) {
                $done++;
            }
        }

        return (int)round($done / $total * 100);
    }
}
