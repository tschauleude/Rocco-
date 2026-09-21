<?php

declare(strict_types=1);

namespace Marian\Shop\Domain\Model;

use Marian\Shop\Service\Money;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Eine Ausführung eines Artikels – bei einem Pulli die Größe.
 * Der Aufschlag darf negativ sein (z. B. Kindergröße günstiger).
 */
class ProductVariant extends AbstractEntity
{
    protected string $title = '';

    protected string $sku = '';

    protected float $priceDelta = 0.0;

    protected int $stock = 0;

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getSku(): string
    {
        return $this->sku;
    }

    public function setSku(string $sku): void
    {
        $this->sku = $sku;
    }

    public function getPriceDelta(): float
    {
        return $this->priceDelta;
    }

    public function setPriceDelta(float $priceDelta): void
    {
        $this->priceDelta = $priceDelta;
    }

    public function getPriceDeltaInCents(): int
    {
        return Money::toCents($this->priceDelta);
    }

    public function getStock(): int
    {
        return $this->stock;
    }

    public function setStock(int $stock): void
    {
        $this->stock = $stock;
    }

    public function isAvailable(): bool
    {
        return $this->stock > 0;
    }
}
