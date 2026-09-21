<?php

declare(strict_types=1);

namespace Marian\Shop\Domain\Model;

use Marian\Shop\Service\Money;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Eine Versandart. Ab einem einstellbaren Warenwert entfallen die Kosten.
 */
class ShippingMethod extends AbstractEntity
{
    protected string $title = '';

    protected string $description = '';

    protected float $price = 0.0;

    /**
     * Ab diesem Warenwert ist der Versand frei; 0 heißt "nie".
     */
    protected float $freeFrom = 0.0;

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

    public function getPrice(): float
    {
        return $this->price;
    }

    public function setPrice(float $price): void
    {
        $this->price = $price;
    }

    public function getPriceInCents(): int
    {
        return Money::toCents($this->price);
    }

    public function getFreeFrom(): float
    {
        return $this->freeFrom;
    }

    public function setFreeFrom(float $freeFrom): void
    {
        $this->freeFrom = $freeFrom;
    }

    public function getFreeFromInCents(): int
    {
        return Money::toCents($this->freeFrom);
    }

    /**
     * Versandkosten für einen gegebenen Warenwert.
     */
    public function costFor(int $subtotalGross): int
    {
        if ($this->freeFrom > 0.0 && $subtotalGross >= $this->getFreeFromInCents()) {
            return 0;
        }

        return $this->getPriceInCents();
    }
}
