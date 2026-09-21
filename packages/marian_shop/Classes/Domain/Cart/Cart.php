<?php

declare(strict_types=1);

namespace Marian\Shop\Domain\Cart;

use Marian\Shop\Service\Money;

/**
 * Der Warenkorb als Werteobjekt: eine Liste von Positionen plus die
 * Rechenwege, die ohne Versand- und Zahlungsart auskommen.
 */
final class Cart
{
    /**
     * @param array<string, CartItem> $items
     */
    public function __construct(private readonly array $items = []) {}

    /**
     * @return array<string, CartItem>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    /**
     * Fluid greift über {cart.isEmpty} zu und sucht dafür getIsEmpty().
     */
    public function getIsEmpty(): bool
    {
        return $this->isEmpty();
    }

    /**
     * Anzahl der Artikel insgesamt (nicht der Positionen).
     */
    public function getQuantity(): int
    {
        $quantity = 0;
        foreach ($this->items as $item) {
            $quantity += $item->quantity;
        }

        return $quantity;
    }

    public function getPositionCount(): int
    {
        return count($this->items);
    }

    public function getSubtotalGross(): int
    {
        $sum = 0;
        foreach ($this->items as $item) {
            $sum += $item->getLineGross();
        }

        return $sum;
    }

    public function getFormattedSubtotal(): string
    {
        return Money::format($this->getSubtotalGross());
    }

    /**
     * Bruttosummen je Steuersatz – Grundlage für die Verteilung der
     * Versandkosten auf die Steuersätze.
     *
     * @return array<string, int>
     */
    public function getGrossByTaxRate(): array
    {
        $rows = [];
        foreach ($this->items as $item) {
            $key = number_format($item->getTaxRate(), 2, '.', '');
            $rows[$key] = ($rows[$key] ?? 0) + $item->getLineGross();
        }

        return $rows;
    }

    /**
     * Positionen, deren Menge den Bestand übersteigt.
     *
     * @return array<string, CartItem>
     */
    public function getItemsExceedingStock(): array
    {
        return array_filter($this->items, static fn (CartItem $item): bool => $item->exceedsStock());
    }

    public function hasStockProblems(): bool
    {
        return $this->getItemsExceedingStock() !== [];
    }

    public function getHasStockProblems(): bool
    {
        return $this->hasStockProblems();
    }

    public function getTotalWeight(): float
    {
        $weight = 0.0;
        foreach ($this->items as $item) {
            $weight += $item->product->getWeight() * $item->quantity;
        }

        return round($weight, 3);
    }
}
