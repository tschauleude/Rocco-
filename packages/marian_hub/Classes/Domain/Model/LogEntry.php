<?php

declare(strict_types=1);

namespace Marian\Hub\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Ein Eintrag im Projekt-Logbuch: was wurde gemacht, was hat es gebracht.
 */
class LogEntry extends AbstractEntity
{
    public const MOODS = [
        'great' => 'Läuft super',
        'neutral' => 'Normaler Fortschritt',
        'stuck' => 'Festgefahren',
        'breakthrough' => 'Durchbruch',
    ];

    protected string $title = '';

    protected ?\DateTime $entryDate = null;

    protected string $bodytext = '';

    protected string $mood = 'neutral';

    protected float $hoursSpent = 0.0;

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getEntryDate(): ?\DateTime
    {
        return $this->entryDate;
    }

    public function setEntryDate(?\DateTime $entryDate): void
    {
        $this->entryDate = $entryDate;
    }

    public function getBodytext(): string
    {
        return $this->bodytext;
    }

    public function setBodytext(string $bodytext): void
    {
        $this->bodytext = $bodytext;
    }

    public function getMood(): string
    {
        return $this->mood;
    }

    public function setMood(string $mood): void
    {
        $this->mood = $mood;
    }

    public function getMoodLabel(): string
    {
        return self::MOODS[$this->mood] ?? $this->mood;
    }

    public function getHoursSpent(): float
    {
        return $this->hoursSpent;
    }

    public function setHoursSpent(float $hoursSpent): void
    {
        $this->hoursSpent = $hoursSpent;
    }
}
