<?php

declare(strict_types=1);

namespace Marian\Hub\Domain\Model;

use TYPO3\CMS\Extbase\Domain\Model\Category;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Ein Wiki-/Journalismus-Artikel inklusive Quellenapparat.
 */
class Article extends AbstractEntity
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_RESEARCH = 'research';
    public const STATUS_REVIEW = 'review';
    public const STATUS_PUBLISHED = 'published';

    public const STATUSES = [
        self::STATUS_DRAFT => 'Entwurf',
        self::STATUS_RESEARCH => 'Recherche',
        self::STATUS_REVIEW => 'Gegenlesen',
        self::STATUS_PUBLISHED => 'Veröffentlicht',
    ];

    /**
     * Durchschnittliche Lesegeschwindigkeit in Wörtern pro Minute.
     */
    private const WORDS_PER_MINUTE = 200;

    protected string $title = '';

    protected string $slug = '';

    protected string $subtitle = '';

    protected string $teaser = '';

    protected string $bodytext = '';

    protected string $status = self::STATUS_DRAFT;

    protected string $author = '';

    protected ?\DateTime $publishedAt = null;

    protected int $revision = 1;

    protected string $changeNote = '';

    /**
     * @var ObjectStorage<Category>
     */
    protected ObjectStorage $categories;

    /**
     * @var ObjectStorage<ArticleSource>
     */
    protected ObjectStorage $sources;

    /**
     * @var ObjectStorage<FileReference>
     */
    protected ObjectStorage $coverImage;

    protected ?Project $relatedProject = null;

    public function __construct()
    {
        $this->categories = new ObjectStorage();
        $this->sources = new ObjectStorage();
        $this->coverImage = new ObjectStorage();
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

    public function getBodytext(): string
    {
        return $this->bodytext;
    }

    public function setBodytext(string $bodytext): void
    {
        $this->bodytext = $bodytext;
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

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function setAuthor(string $author): void
    {
        $this->author = $author;
    }

    public function getPublishedAt(): ?\DateTime
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?\DateTime $publishedAt): void
    {
        $this->publishedAt = $publishedAt;
    }

    public function getRevision(): int
    {
        return $this->revision;
    }

    public function setRevision(int $revision): void
    {
        $this->revision = $revision;
    }

    public function getChangeNote(): string
    {
        return $this->changeNote;
    }

    public function setChangeNote(string $changeNote): void
    {
        $this->changeNote = $changeNote;
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
     * @return ObjectStorage<ArticleSource>
     */
    public function getSources(): ObjectStorage
    {
        return $this->sources;
    }

    /**
     * @param ObjectStorage<ArticleSource> $sources
     */
    public function setSources(ObjectStorage $sources): void
    {
        $this->sources = $sources;
    }

    public function addSource(ArticleSource $source): void
    {
        $this->sources->attach($source);
    }

    public function removeSource(ArticleSource $source): void
    {
        $this->sources->detach($source);
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

    public function getRelatedProject(): ?Project
    {
        return $this->relatedProject;
    }

    public function setRelatedProject(?Project $relatedProject): void
    {
        $this->relatedProject = $relatedProject;
    }

    public function getWordCount(): int
    {
        return str_word_count(strip_tags($this->bodytext), 0, 'äöüÄÖÜßáéíóúàèìòùâêîôû');
    }

    /**
     * Geschätzte Lesezeit in Minuten, mindestens eine.
     */
    public function getReadingTime(): int
    {
        return max(1, (int)ceil($this->getWordCount() / self::WORDS_PER_MINUTE));
    }

    /**
     * Erster Buchstabe für das alphabetische Register.
     */
    public function getInitial(): string
    {
        $initial = mb_strtoupper(mb_substr(trim($this->title), 0, 1));

        return preg_match('/^\p{L}$/u', $initial) === 1 ? $initial : '#';
    }
}
