<?php

declare(strict_types=1);

namespace Marian\Shop\Domain\Cart;

use Marian\Shop\Domain\Model\Product;
use Marian\Shop\Domain\Model\ProductVariant;
use Marian\Shop\Service\Money;

/**
 * Eine Position im Warenkorb. Der Preis wird aus dem Artikel berechnet und
 * nie aus der Sitzung gelesen – sonst könnte man ihn von außen setzen.
 */
final class CartItem
{
    public function __construct(
        public readonly Product $product,
        public readonly ?ProductVariant $variant,
        public readonly int $quantity,
    ) {}

    /**
     * Schlüssel der Position: Artikel plus Ausführung.
     */
    public function getKey(): string
    {
        return self::keyFor($this->product->getUid() ?? 0, $this->variant?->getUid() ?? 0);
    }

    public static function keyFor(int $productUid, int $variantUid): string
    {
        return $productUid . '-' . $variantUid;
    }

    public function getUnitGross(): int
    {
        return $this->product->getPriceInCents() + ($this->variant?->getPriceDeltaInCents() ?? 0);
    }

    public function getLineGross(): int
    {
        return $this->getUnitGross() * $this->quantity;
    }

    public function getTaxRate(): float
    {
        return $this->product->getTaxRate();
    }

    public function getLineTax(): int
    {
        return Money::taxFromGross($this->getLineGross(), $this->getTaxRate());
    }

    public function getTitle(): string
    {
        return $this->product->getTitle();
    }

    public function getVariantTitle(): string
    {
        return $this->variant?->getTitle() ?? '';
    }

    public function getFullTitle(): string
    {
        $variantTitle = $this->getVariantTitle();

        return $variantTitle !== '' ? $this->getTitle() . ' (' . $variantTitle . ')' : $this->getTitle();
    }

    public function getSku(): string
    {
        $variantSku = $this->variant?->getSku() ?? '';

        return $variantSku !== '' ? $variantSku : $this->product->getSku();
    }

    public function getAvailableStock(): int
    {
        return $this->product->getAvailableStock($this->variant);
    }

    /**
     * Mehr bestellt als lieferbar – die Kasse muss das abfangen.
     */
    public function exceedsStock(): bool
    {
        return $this->quantity > $this->getAvailableStock();
    }

    public function getExceedsStock(): bool
    {
        return $this->exceedsStock();
    }

    public function getFormattedUnitPrice(): string
    {
        return Money::format($this->getUnitGross());
    }

    public function getFormattedLineTotal(): string
    {
        return Money::format($this->getLineGross());
    }

    public function withQuantity(int $quantity): self
    {
        return new self($this->product, $this->variant, $quantity);
    }
}
