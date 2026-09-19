<?php

declare(strict_types=1);

namespace Marian\Hub\Domain\Model;

use TYPO3\CMS\Extbase\Domain\Model\Category;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Ein Projekt – vom Lötkolben-Basteln bis zur großen Idee.
 */
class Project extends AbstractEntity
{
    public const STATUS_IDEA = 'idea';
    public const STATUS_PLANNING = 'planning';
    public const STATUS_BUILDING = 'building';
    public const STATUS_RUNNING = 'running';
    public const STATUS_DONE = 'done';
    public const STATUS_PAUSED = 'paused';

    public const STATUSES = [
        self::STATUS_IDEA => 'Idee',
        self::STATUS_PLANNING => 'Planung',
        self::STATUS_BUILDING => 'Im Bau',
        self::STATUS_RUNNING => 'Läuft',
        self::STATUS_DONE => 'Fertig',
        self::STATUS_PAUSED => 'Pausiert',
    ];

    protected string $title = '';

    protected string $slug = '';

    protected string $subtitle = '';

    protected string $teaser = '';

    protected string $description = '';

    protected string $status = self::STATUS_IDEA;

    protected int $progress = 0;

    protected ?\DateTime $startedAt = null;

    protected ?\DateTime $finishedAt = null;

    protected string $repositoryUrl = '';

    /**
     * Stückliste, ein Bauteil pro Zeile – optional "Bauteil | Anzahl | Preis".
     */
    protected string $billOfMaterials = '';

    /**
     * @var ObjectStorage<FileReference>
     */
    protected ObjectStorage $coverImage;

    /**
     * @var ObjectStorage<Category>
     */
    protected ObjectStorage $categories;

    /**
     * @var ObjectStorage<LogEntry>
     */
    protected ObjectStorage $logEntries;

    public function __construct()
    {
        $this->coverImage = new ObjectStorage();
        $this->categories = new ObjectStorage();
        $this->logEntries = new ObjectStorage();
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function getSubtitle(): string
    {
        return $this->subtitle;
    }

    public function setSubtitle(string $subtitle): void
    {
        $this->subtitle = $subtitle;
    }

    public function getTeaser(): string
    {
        return $this->teaser;
    }

    public function setTeaser(string $teaser): void
    {
        $this->teaser = $teaser;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getStatusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getProgress(): int
    {
        return $this->progress;
    }

    public function setProgress(int $progress): void
    {
        $this->progress = max(0, min(100, $progress));
    }

    public function getStartedAt(): ?\DateTime
    {
        return $this->startedAt;
    }

    public function setStartedAt(?\DateTime $startedAt): void
    {
        $this->startedAt = $startedAt;
    }

    public function getFinishedAt(): ?\DateTime
    {
        return $this->finishedAt;
    }

    public function setFinishedAt(?\DateTime $finishedAt): void
    {
        $this->finishedAt = $finishedAt;
    }

    public function getRepositoryUrl(): string
    {
        return $this->repositoryUrl;
    }

    public function setRepositoryUrl(string $repositoryUrl): void
    {
        $this->repositoryUrl = $repositoryUrl;
    }

    public function getBillOfMaterials(): string
    {
        return $this->billOfMaterials;
    }

    public function setBillOfMaterials(string $billOfMaterials): void
    {
        $this->billOfMaterials = $billOfMaterials;
    }

    /**
     * Stückliste als Tabelle: jede Zeile wird an "|" in Spalten zerlegt.
     *
     * @return array<int, array{part: string, amount: string, price: string}>
     */
    public function getMaterialList(): array
    {
        $rows = [];
        foreach (preg_split('/\R/', trim($this->billOfMaterials)) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $columns = array_map('trim', explode('|', $line));
            $rows[] = [
                'part' => $columns[0] ?? '',
                'amount' => $columns[1] ?? '',
                'price' => $columns[2] ?? '',
            ];
        }

        return $rows;
    }

    /**
     * @return ObjectStorage<FileReference>
     */
    public function getCoverImage(): ObjectStorage
    {
        return $this->coverImage;
    }

    /**
     * @param ObjectStorage<FileReference> $coverImage
     */
    public function setCoverImage(ObjectStorage $coverImage): void
    {
        $this->coverImage = $coverImage;
    }

    public function getFirstCoverImage(): ?FileReference
    {
        foreach ($this->coverImage as $reference) {
            return $reference;
        }

        return null;
    }

    /**
     * @return ObjectStorage<Category>
     */
    public function getCategories(): ObjectStorage
    {
        return $this->categories;
    }

    /**
     * @param ObjectStorage<Category> $categories
     */
    public function setCategories(ObjectStorage $categories): void
    {
        $this->categories = $categories;
    }

    /**
     * @return ObjectStorage<LogEntry>
     */
    public function getLogEntries(): ObjectStorage
    {
        return $this->logEntries;
    }

    /**
     * @param ObjectStorage<LogEntry> $logEntries
     */
    public function setLogEntries(ObjectStorage $logEntries): void
    {
        $this->logEntries = $logEntries;
    }

    public function addLogEntry(LogEntry $logEntry): void
    {
        $this->logEntries->attach($logEntry);
    }

    public function removeLogEntry(LogEntry $logEntry): void
    {
        $this->logEntries->detach($logEntry);
    }

    /**
     * Summe der im Logbuch notierten Stunden.
     */
    public function getHoursSpent(): float
    {
        $hours = 0.0;
        foreach ($this->logEntries as $entry) {
            $hours += $entry->getHoursSpent();
        }

        return round($hours, 2);
    }

    public function isActive(): bool
    {
        return in_array($this->status, [self::STATUS_PLANNING, self::STATUS_BUILDING, self::STATUS_RUNNING], true);
    }
}
