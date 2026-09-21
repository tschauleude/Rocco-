<?php

declare(strict_types=1);

namespace Marian\Shop\Domain\Model;

use Marian\Shop\Service\Money;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Eine Bestellposition. Titel, Artikelnummer und Preis sind Kopien aus dem
 * Katalog zum Zeitpunkt der Bestellung – der Artikel darf sich später ändern
 * oder verschwinden, ohne die Bestellung zu verfälschen.
 */
class OrderItem extends AbstractEntity
{
    protected int $product = 0;

    protected int $variant = 0;

    protected string $title = '';

    protected string $variantTitle = '';

    protected string $sku = '';

    protected int $quantity = 1;

    protected int $unitGross = 0;

    protected int $lineGross = 0;

    protected int $lineTax = 0;

    protected float $taxRate = 19.0;

    public function getProduct(): int
    {
        return $this->product;
    }

    public function setProduct(int $product): void
    {
        $this->product = $product;
    }

    public function getVariant(): int
    {
        return $this->variant;
    }

    public function setVariant(int $variant): void
    {
        $this->variant = $variant;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getVariantTitle(): string
    {
        return $this->variantTitle;
    }

    public function setVariantTitle(string $variantTitle): void
    {
        $this->variantTitle = $variantTitle;
    }

    public function getSku(): string
    {
        return $this->sku;
    }

    public function setSku(string $sku): void
    {
        $this->sku = $sku;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): void
    {
        $this->quantity = max(1, $quantity);
    }

    public function getUnitGross(): int
    {
        return $this->unitGross;
    }

    public function setUnitGross(int $unitGross): void
    {
        $this->unitGross = $unitGross;
    }

    public function getLineGross(): int
    {
        return $this->lineGross;
    }

    public function setLineGross(int $lineGross): void
    {
        $this->lineGross = $lineGross;
    }

    public function getLineTax(): int
    {
        return $this->lineTax;
    }

    public function setLineTax(int $lineTax): void
    {
        $this->lineTax = $lineTax;
    }

    public function getTaxRate(): float
    {
        return $this->taxRate;
    }

    public function setTaxRate(float $taxRate): void
    {
        $this->taxRate = $taxRate;
    }

    public function getFullTitle(): string
    {
        return $this->variantTitle !== ''
            ? $this->title . ' (' . $this->variantTitle . ')'
            : $this->title;
    }

    public function getFormattedUnitPrice(): string
    {
        return Money::format($this->unitGross);
    }

    public function getFormattedLineTotal(): string
    {
        return Money::format($this->lineGross);
    }
}
