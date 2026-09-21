<?php

declare(strict_types=1);

namespace Marian\Shop\Domain\Model;

use Marian\Shop\Service\Money;
use TYPO3\CMS\Extbase\Domain\Model\Category;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Ein Artikel im Shop. Preise sind Bruttopreise – im Verkauf an Verbraucher
 * ist der Endpreis das, was ausgezeichnet werden muss.
 */
class Product extends AbstractEntity
{
    protected string $title = '';

    protected string $slug = '';

    protected string $sku = '';

    protected string $subtitle = '';

    protected string $teaser = '';

    protected string $description = '';

    protected float $price = 0.0;

    /**
     * Früherer Preis für Streichpreis-Darstellung; 0 heißt "keiner".
     */
    protected float $priceOld = 0.0;

    protected float $taxRate = 19.0;

    protected int $stock = 0;

    protected bool $stockManaged = true;

    protected int $maxPerOrder = 10;

    protected float $weight = 0.0;

    protected string $deliveryTime = '';

    protected bool $featured = false;

    /**
     * @var ObjectStorage<FileReference>
     */
    protected ObjectStorage $images;

    /**
     * @var ObjectStorage<Category>
     */
    protected ObjectStorage $categories;

    /**
     * @var ObjectStorage<ProductVariant>
     */
    protected ObjectStorage $variants;

    public function __construct()
    {
        $this->images = new ObjectStorage();
        $this->categories = new ObjectStorage();
        $this->variants = new ObjectStorage();
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

    public function getSku(): string
    {
        return $this->sku;
    }

    public function setSku(string $sku): void
    {
        $this->sku = $sku;
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

    public function getPriceOld(): float
    {
        return $this->priceOld;
    }

    public function setPriceOld(float $priceOld): void
    {
        $this->priceOld = $priceOld;
    }

    public function hasReducedPrice(): bool
    {
        return $this->priceOld > $this->price && $this->priceOld > 0.0;
    }

    public function getHasReducedPrice(): bool
    {
        return $this->hasReducedPrice();
    }

    public function getTaxRate(): float
    {
        return $this->taxRate;
    }

    public function setTaxRate(float $taxRate): void
    {
        $this->taxRate = $taxRate;
    }

    public function getStock(): int
    {
        return $this->stock;
    }

    public function setStock(int $stock): void
    {
        $this->stock = $stock;
    }

    public function getStockManaged(): bool
    {
        return $this->stockManaged;
    }

    public function isStockManaged(): bool
    {
        return $this->stockManaged;
    }

    public function setStockManaged(bool $stockManaged): void
    {
        $this->stockManaged = $stockManaged;
    }

    public function getMaxPerOrder(): int
    {
        return max(1, $this->maxPerOrder);
    }

    public function setMaxPerOrder(int $maxPerOrder): void
    {
        $this->maxPerOrder = $maxPerOrder;
    }

    public function getWeight(): float
    {
        return $this->weight;
    }

    public function setWeight(float $weight): void
    {
        $this->weight = $weight;
    }

    public function getDeliveryTime(): string
    {
        return $this->deliveryTime;
    }

    public function setDeliveryTime(string $deliveryTime): void
    {
        $this->deliveryTime = $deliveryTime;
    }

    public function getFeatured(): bool
    {
        return $this->featured;
    }

    public function setFeatured(bool $featured): void
    {
        $this->featured = $featured;
    }

    /**
     * @return ObjectStorage<FileReference>
     */
    public function getImages(): ObjectStorage
    {
        return $this->images;
    }

    /**
     * @param ObjectStorage<FileReference> $images
     */
    public function setImages(ObjectStorage $images): void
    {
        $this->images = $images;
    }

    public function getFirstImage(): ?FileReference
    {
        foreach ($this->images as $image) {
            return $image;
        }

        return null;
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
     * @return ObjectStorage<ProductVariant>
     */
    public function getVariants(): ObjectStorage
    {
        return $this->variants;
    }

    /**
     * @param ObjectStorage<ProductVariant> $variants
     */
    public function setVariants(ObjectStorage $variants): void
    {
        $this->variants = $variants;
    }

    public function hasVariants(): bool
    {
        return count($this->variants) > 0;
    }

    public function getHasVariants(): bool
    {
        return $this->hasVariants();
    }

    public function findVariant(int $uid): ?ProductVariant
    {
        foreach ($this->variants as $variant) {
            if ($variant->getUid() === $uid) {
                return $variant;
            }
        }

        return null;
    }

    /**
     * Lieferbare Menge.
     *
     * Ohne Bestandsführung gilt der Artikel als unbegrenzt verfügbar. Bei
     * Artikeln mit Ausführungen liegt der Bestand an der Ausführung – ohne
     * gewählte Ausführung zählt, was über alle zusammen noch da ist. Sonst
     * stünde ein Pulli auf "ausverkauft", nur weil der Bestand an den Größen
     * hängt und nicht am Artikel selbst.
     */
    public function getAvailableStock(?ProductVariant $variant = null): int
    {
        if (!$this->stockManaged) {
            return PHP_INT_MAX;
        }

        if ($variant !== null) {
            return max(0, $variant->getStock());
        }

        if ($this->hasVariants()) {
            $sum = 0;
            foreach ($this->variants as $each) {
                $sum += max(0, $each->getStock());
            }

            return $sum;
        }

        return max(0, $this->stock);
    }

    public function isAvailable(?ProductVariant $variant = null): bool
    {
        return $this->getAvailableStock($variant) > 0;
    }

    /**
     * Wenig Bestand: Hinweis im Frontend.
     */
    public function isLowOnStock(): bool
    {
        if (!$this->stockManaged) {
            return false;
        }

        $available = $this->getAvailableStock();

        return $available > 0 && $available <= 5;
    }

    /**
     * Für den Hinweis "Nur noch X verfügbar" – zählt bei Artikeln mit
     * Ausführungen deren Summe.
     */
    public function getStockLeft(): int
    {
        return $this->getAvailableStock();
    }
}
