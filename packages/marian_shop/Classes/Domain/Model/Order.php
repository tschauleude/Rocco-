<?php

declare(strict_types=1);

namespace Marian\Shop\Domain\Model;

use Marian\Shop\Service\Money;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Eine Bestellung. Sämtliche Beträge sind Cent-Ganzzahlen und werden beim
 * Bestellen festgeschrieben: spätere Preisänderungen am Artikel dürfen eine
 * abgeschlossene Bestellung nicht rückwirkend verändern.
 */
class Order extends AbstractEntity
{
    public const STATUS_NEW = 'new';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SHIPPED = 'shipped';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_NEW => 'Eingegangen',
        self::STATUS_CONFIRMED => 'Bestätigt',
        self::STATUS_PROCESSING => 'In Bearbeitung',
        self::STATUS_SHIPPED => 'Versendet',
        self::STATUS_COMPLETED => 'Abgeschlossen',
        self::STATUS_CANCELLED => 'Storniert',
    ];

    public const PAYMENT_PENDING = 'pending';
    public const PAYMENT_PAID = 'paid';
    public const PAYMENT_FAILED = 'failed';
    public const PAYMENT_REFUNDED = 'refunded';

    public const PAYMENT_STATUSES = [
        self::PAYMENT_PENDING => 'Offen',
        self::PAYMENT_PAID => 'Bezahlt',
        self::PAYMENT_FAILED => 'Fehlgeschlagen',
        self::PAYMENT_REFUNDED => 'Erstattet',
    ];

    protected string $orderNumber = '';

    protected int $feUser = 0;

    protected string $email = '';

    protected string $phone = '';

    protected ?Address $billingAddress = null;

    protected ?Address $shippingAddress = null;

    /**
     * @var ObjectStorage<OrderItem>
     */
    protected ObjectStorage $items;

    protected int $shippingMethod = 0;

    protected string $shippingTitle = '';

    protected int $shippingGross = 0;

    protected int $paymentMethod = 0;

    protected string $paymentTitle = '';

    protected string $paymentProvider = '';

    protected int $paymentSurchargeGross = 0;

    protected int $subtotalGross = 0;

    protected int $totalGross = 0;

    protected int $totalNet = 0;

    protected int $taxTotal = 0;

    /**
     * Steueraufschlüsselung als JSON: Steuersatz => netto/steuer/brutto.
     */
    protected string $taxBreakdown = '';

    protected string $status = self::STATUS_NEW;

    protected string $paymentStatus = self::PAYMENT_PENDING;

    protected string $paymentReference = '';

    protected ?\DateTime $paidAt = null;

    protected string $customerNote = '';

    protected bool $acceptedTerms = false;

    protected bool $acceptedWithdrawal = false;

    protected ?\DateTime $orderedAt = null;

    public function __construct()
    {
        $this->items = new ObjectStorage();
    }

    public function getOrderNumber(): string
    {
        return $this->orderNumber;
    }

    public function setOrderNumber(string $orderNumber): void
    {
        $this->orderNumber = $orderNumber;
    }

    public function getFeUser(): int
    {
        return $this->feUser;
    }

    public function setFeUser(int $feUser): void
    {
        $this->feUser = $feUser;
    }

    public function isGuestOrder(): bool
    {
        return $this->feUser === 0;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): void
    {
        $this->phone = $phone;
    }

    public function getBillingAddress(): ?Address
    {
        return $this->billingAddress;
    }

    public function setBillingAddress(?Address $billingAddress): void
    {
        $this->billingAddress = $billingAddress;
    }

    public function getShippingAddress(): ?Address
    {
        return $this->shippingAddress;
    }

    public function setShippingAddress(?Address $shippingAddress): void
    {
        $this->shippingAddress = $shippingAddress;
    }

    /**
     * Die Lieferanschrift, oder ersatzweise die Rechnungsanschrift.
     */
    public function getEffectiveShippingAddress(): ?Address
    {
        return $this->shippingAddress ?? $this->billingAddress;
    }

    /**
     * @return ObjectStorage<OrderItem>
     */
    public function getItems(): ObjectStorage
    {
        return $this->items;
    }

    /**
     * @param ObjectStorage<OrderItem> $items
     */
    public function setItems(ObjectStorage $items): void
    {
        $this->items = $items;
    }

    public function addItem(OrderItem $item): void
    {
        $this->items->attach($item);
    }

    public function getItemCount(): int
    {
        $count = 0;
        foreach ($this->items as $item) {
            $count += $item->getQuantity();
        }

        return $count;
    }

    public function getShippingMethod(): int
    {
        return $this->shippingMethod;
    }

    public function setShippingMethod(int $shippingMethod): void
    {
        $this->shippingMethod = $shippingMethod;
    }

    public function getShippingTitle(): string
    {
        return $this->shippingTitle;
    }

    public function setShippingTitle(string $shippingTitle): void
    {
        $this->shippingTitle = $shippingTitle;
    }

    public function getShippingGross(): int
    {
        return $this->shippingGross;
    }

    public function setShippingGross(int $shippingGross): void
    {
        $this->shippingGross = $shippingGross;
    }

    public function getPaymentMethod(): int
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(int $paymentMethod): void
    {
        $this->paymentMethod = $paymentMethod;
    }

    public function getPaymentTitle(): string
    {
        return $this->paymentTitle;
    }

    public function setPaymentTitle(string $paymentTitle): void
    {
        $this->paymentTitle = $paymentTitle;
    }

    public function getPaymentProvider(): string
    {
        return $this->paymentProvider;
    }

    public function setPaymentProvider(string $paymentProvider): void
    {
        $this->paymentProvider = $paymentProvider;
    }

    public function getPaymentSurchargeGross(): int
    {
        return $this->paymentSurchargeGross;
    }

    public function setPaymentSurchargeGross(int $paymentSurchargeGross): void
    {
        $this->paymentSurchargeGross = $paymentSurchargeGross;
    }

    public function getSubtotalGross(): int
    {
        return $this->subtotalGross;
    }

    public function setSubtotalGross(int $subtotalGross): void
    {
        $this->subtotalGross = $subtotalGross;
    }

    public function getTotalGross(): int
    {
        return $this->totalGross;
    }

    public function setTotalGross(int $totalGross): void
    {
        $this->totalGross = $totalGross;
    }

    public function getTotalNet(): int
    {
        return $this->totalNet;
    }

    public function setTotalNet(int $totalNet): void
    {
        $this->totalNet = $totalNet;
    }

    public function getTaxTotal(): int
    {
        return $this->taxTotal;
    }

    public function setTaxTotal(int $taxTotal): void
    {
        $this->taxTotal = $taxTotal;
    }

    public function getTaxBreakdown(): string
    {
        return $this->taxBreakdown;
    }

    public function setTaxBreakdown(string $taxBreakdown): void
    {
        $this->taxBreakdown = $taxBreakdown;
    }

    /**
     * @return array<string, array{net: int, tax: int, gross: int}>
     */
    public function getTaxRows(): array
    {
        if ($this->taxBreakdown === '') {
            return [];
        }

        $decoded = json_decode($this->taxBreakdown, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getStatusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getPaymentStatus(): string
    {
        return $this->paymentStatus;
    }

    public function setPaymentStatus(string $paymentStatus): void
    {
        $this->paymentStatus = $paymentStatus;
    }

    public function getPaymentStatusLabel(): string
    {
        return self::PAYMENT_STATUSES[$this->paymentStatus] ?? $this->paymentStatus;
    }

    public function isPaid(): bool
    {
        return $this->paymentStatus === self::PAYMENT_PAID;
    }

    public function getIsPaid(): bool
    {
        return $this->isPaid();
    }

    public function getPaymentReference(): string
    {
        return $this->paymentReference;
    }

    public function setPaymentReference(string $paymentReference): void
    {
        $this->paymentReference = $paymentReference;
    }

    public function getPaidAt(): ?\DateTime
    {
        return $this->paidAt;
    }

    public function setPaidAt(?\DateTime $paidAt): void
    {
        $this->paidAt = $paidAt;
    }

    public function getCustomerNote(): string
    {
        return $this->customerNote;
    }

    public function setCustomerNote(string $customerNote): void
    {
        $this->customerNote = $customerNote;
    }

    public function getAcceptedTerms(): bool
    {
        return $this->acceptedTerms;
    }

    public function setAcceptedTerms(bool $acceptedTerms): void
    {
        $this->acceptedTerms = $acceptedTerms;
    }

    public function getAcceptedWithdrawal(): bool
    {
        return $this->acceptedWithdrawal;
    }

    public function setAcceptedWithdrawal(bool $acceptedWithdrawal): void
    {
        $this->acceptedWithdrawal = $acceptedWithdrawal;
    }

    public function getOrderedAt(): ?\DateTime
    {
        return $this->orderedAt;
    }

    public function setOrderedAt(?\DateTime $orderedAt): void
    {
        $this->orderedAt = $orderedAt;
    }

    public function getFormattedTotal(): string
    {
        return Money::format($this->totalGross);
    }

    public function getFormattedSubtotal(): string
    {
        return Money::format($this->subtotalGross);
    }

    public function getFormattedShipping(): string
    {
        return Money::format($this->shippingGross);
    }

    public function getFormattedSurcharge(): string
    {
        return Money::format($this->paymentSurchargeGross);
    }

    public function getFormattedTax(): string
    {
        return Money::format($this->taxTotal);
    }
}
