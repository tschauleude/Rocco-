<?php

declare(strict_types=1);

namespace Marian\Shop\Domain\Cart;

use Marian\Shop\Service\Money;

/**
 * Das Ergebnis der Preisberechnung: alle Summen einer Bestellung.
 *
 * @phpstan-type TaxRow array{rate: float, net: int, tax: int, gross: int}
 */
final class CartTotals
{
    /**
     * @param array<string, TaxRow> $taxRows Steuersatz (als "19.00") => Beträge
     */
    public function __construct(
        public readonly int $subtotalGross,
        public readonly int $shippingGross,
        public readonly int $surchargeGross,
        public readonly int $totalGross,
        public readonly int $totalNet,
        public readonly int $taxTotal,
        public readonly array $taxRows,
        public readonly int $freeShippingRemaining = 0,
    ) {}

    public function getFormattedSubtotal(): string
    {
        return Money::format($this->subtotalGross);
    }

    public function getFormattedShipping(): string
    {
        return $this->shippingGross === 0 ? 'kostenlos' : Money::format($this->shippingGross);
    }

    public function getFormattedSurcharge(): string
    {
        return Money::format($this->surchargeGross);
    }

    public function getFormattedTotal(): string
    {
        return Money::format($this->totalGross);
    }

    public function getFormattedNet(): string
    {
        return Money::format($this->totalNet);
    }

    public function getFormattedTax(): string
    {
        return Money::format($this->taxTotal);
    }

    public function getFormattedFreeShippingRemaining(): string
    {
        return Money::format($this->freeShippingRemaining);
    }

    public function hasSurcharge(): bool
    {
        return $this->surchargeGross !== 0;
    }

    public function getHasSurcharge(): bool
    {
        return $this->hasSurcharge();
    }

    public function hasFreeShipping(): bool
    {
        return $this->shippingGross === 0;
    }

    public function getHasFreeShipping(): bool
    {
        return $this->hasFreeShipping();
    }

    /**
     * Steueraufschlüsselung für die Bestellung, als JSON zum Wegschreiben.
     */
    public function getTaxBreakdownJson(): string
    {
        return json_encode($this->taxRows, JSON_THROW_ON_ERROR);
    }
}
