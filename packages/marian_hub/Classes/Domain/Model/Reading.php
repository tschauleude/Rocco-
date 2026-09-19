<?php

declare(strict_types=1);

namespace Marian\Hub\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Ein einzelner Messwert eines Sensors.
 */
class Reading extends AbstractEntity
{
    protected ?Sensor $sensor = null;

    protected float $value = 0.0;

    protected ?\DateTime $measuredAt = null;

    /**
     * Roh-JSON der Messung, falls das Gerät mehr mitschickt als den Wert.
     */
    protected string $payload = '';

    public function getSensor(): ?Sensor
    {
        return $this->sensor;
    }

    public function setSensor(?Sensor $sensor): void
    {
        $this->sensor = $sensor;
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function setValue(float $value): void
    {
        $this->value = $value;
    }

    public function getMeasuredAt(): ?\DateTime
    {
        return $this->measuredAt;
    }

    public function setMeasuredAt(?\DateTime $measuredAt): void
    {
        $this->measuredAt = $measuredAt;
    }

    public function getPayload(): string
    {
        return $this->payload;
    }

    public function setPayload(string $payload): void
    {
        $this->payload = $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public function getPayloadData(): array
    {
        if ($this->payload === '') {
            return [];
        }

        $decoded = json_decode($this->payload, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function getFormattedValue(): string
    {
        return $this->sensor?->format($this->value) ?? (string)$this->value;
    }
}
