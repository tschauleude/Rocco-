<?php

declare(strict_types=1);

namespace Marian\Hub\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Ein physischer Sensor an einem Arduino/ESP, der Messwerte an die Ingest-API schickt.
 */
class Sensor extends AbstractEntity
{
    public const KINDS = [
        'temperature' => 'Temperatur',
        'humidity' => 'Luftfeuchte',
        'pressure' => 'Luftdruck',
        'co2' => 'CO₂',
        'brightness' => 'Helligkeit',
        'distance' => 'Abstand',
        'moisture' => 'Bodenfeuchte',
        'noise' => 'Lautstärke',
        'power' => 'Leistung',
        'generic' => 'Sonstiges',
    ];

    protected string $title = '';

    /**
     * Technischer Schlüssel, den der Arduino mitsendet (z. B. "balkon-temp").
     */
    protected string $identifier = '';

    protected string $description = '';

    protected string $location = '';

    protected string $unit = '';

    protected string $kind = 'generic';

    protected int $decimals = 1;

    /**
     * SHA-256 des API-Tokens – der Klartext liegt nur auf dem Gerät.
     */
    protected string $tokenHash = '';

    protected ?float $warnMin = null;

    protected ?float $warnMax = null;

    protected ?\DateTime $lastSeen = null;

    protected int $retentionDays = 90;

    protected bool $active = true;

    protected ?Project $project = null;

    /**
     * @var ObjectStorage<Reading>
     */
    protected ObjectStorage $readings;

    public function __construct()
    {
        $this->readings = new ObjectStorage();
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function setLocation(string $location): void
    {
        $this->location = $location;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }

    public function setUnit(string $unit): void
    {
        $this->unit = $unit;
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

    public function getDecimals(): int
    {
        return $this->decimals;
    }

    public function setDecimals(int $decimals): void
    {
        $this->decimals = max(0, min(4, $decimals));
    }

    public function getTokenHash(): string
    {
        return $this->tokenHash;
    }

    public function setTokenHash(string $tokenHash): void
    {
        $this->tokenHash = $tokenHash;
    }

    public function getWarnMin(): ?float
    {
        return $this->warnMin;
    }

    public function setWarnMin(?float $warnMin): void
    {
        $this->warnMin = $warnMin;
    }

    public function getWarnMax(): ?float
    {
        return $this->warnMax;
    }

    public function setWarnMax(?float $warnMax): void
    {
        $this->warnMax = $warnMax;
    }

    public function getLastSeen(): ?\DateTime
    {
        return $this->lastSeen;
    }

    public function setLastSeen(?\DateTime $lastSeen): void
    {
        $this->lastSeen = $lastSeen;
    }

    public function getRetentionDays(): int
    {
        return $this->retentionDays;
    }

    public function setRetentionDays(int $retentionDays): void
    {
        $this->retentionDays = $retentionDays;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): void
    {
        $this->project = $project;
    }

    /**
     * @return ObjectStorage<Reading>
     */
    public function getReadings(): ObjectStorage
    {
        return $this->readings;
    }

    /**
     * @param ObjectStorage<Reading> $readings
     */
    public function setReadings(ObjectStorage $readings): void
    {
        $this->readings = $readings;
    }

    /**
     * Gilt als offline, wenn seit einer Stunde nichts mehr ankam.
     */
    public function isOnline(): bool
    {
        if ($this->lastSeen === null) {
            return false;
        }

        return (time() - $this->lastSeen->getTimestamp()) < 3600;
    }

    public function isOutOfRange(float $value): bool
    {
        if ($this->warnMin !== null && $value < $this->warnMin) {
            return true;
        }

        return $this->warnMax !== null && $value > $this->warnMax;
    }

    public function format(float $value): string
    {
        $formatted = number_format($value, $this->decimals, ',', '.');

        return $this->unit !== '' ? $formatted . ' ' . $this->unit : $formatted;
    }
}
