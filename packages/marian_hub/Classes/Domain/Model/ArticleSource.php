<?php

declare(strict_types=1);

namespace Marian\Hub\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Eine Quellenangabe zu einem Artikel – das Rückgrat sauberer journalistischer Arbeit.
 */
class ArticleSource extends AbstractEntity
{
    public const KINDS = [
        'web' => 'Website',
        'interview' => 'Interview',
        'book' => 'Buch',
        'paper' => 'Fachartikel',
        'document' => 'Dokument',
        'own' => 'Eigene Recherche',
    ];

    protected string $title = '';

    protected string $url = '';

    protected string $kind = 'web';

    protected ?\DateTime $accessedAt = null;

    protected string $note = '';

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
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

    public function getAccessedAt(): ?\DateTime
    {
        return $this->accessedAt;
    }

    public function setAccessedAt(?\DateTime $accessedAt): void
    {
        $this->accessedAt = $accessedAt;
    }

    public function getNote(): string
    {
        return $this->note;
    }

    public function setNote(string $note): void
    {
        $this->note = $note;
    }

    /**
     * Anzeigename der Domain, damit die Quellenliste lesbar bleibt.
     */
    public function getHost(): string
    {
        $host = parse_url($this->url, PHP_URL_HOST);

        return is_string($host) ? preg_replace('/^www\./', '', $host) ?? $host : '';
    }
}
