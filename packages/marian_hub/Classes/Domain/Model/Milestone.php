<?php

declare(strict_types=1);

namespace Marian\Hub\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Ein Termin auf dem Weg zum Abi: Klausurphase, Mottowoche, Abiball, Deadline.
 */
class Milestone extends AbstractEntity
{
    public const KINDS = [
        'deadline' => 'Deadline',
        'exam' => 'Klausur/Prüfung',
        'event' => 'Veranstaltung',
        'mottoweek' => 'Mottowoche',
        'ball' => 'Abiball',
        'trip' => 'Fahrt',
    ];

    protected string $title = '';

    protected string $description = '';

    protected ?\DateTime $date = null;

    protected string $kind = 'deadline';

    /**
     * Genau ein Meilenstein darf das Ziel des großen Countdowns sein.
     */
    protected bool $isCountdownTarget = false;

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

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(?\DateTime $date): void
    {
        $this->date = $date;
    }

    public function getKind(): string
    {
        return $this->kind;
    }

    public function setKind(string $kind): void
    {
        $this->kind = $kind;
    }

    public function getKindLabel(): string
    {
        return self::KINDS[$this->kind] ?? $this->kind;
    }

    public function getIsCountdownTarget(): bool
    {
        return $this->isCountdownTarget;
    }

    public function setIsCountdownTarget(bool $isCountdownTarget): void
    {
        $this->isCountdownTarget = $isCountdownTarget;
    }

    public function getDaysLeft(): ?int
    {
        if ($this->date === null) {
            return null;
        }

        $today = new \DateTimeImmutable('today');
        $target = (new \DateTimeImmutable())->setTimestamp($this->date->getTimestamp())->setTime(0, 0);

        return (int)$today->diff($target)->format('%r%a');
    }

    public function isPast(): bool
    {
        $daysLeft = $this->getDaysLeft();

        return $daysLeft !== null && $daysLeft < 0;
    }
}
