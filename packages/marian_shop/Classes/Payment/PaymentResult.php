<?php

declare(strict_types=1);

namespace Marian\Shop\Payment;

/**
 * Ergebnis eines Zahlungsstarts.
 */
final class PaymentResult
{
    public const STATUS_REDIRECT = 'redirect';
    public const STATUS_PENDING = 'pending';
    public const STATUS_FAILED = 'failed';

    private function __construct(
        public readonly string $status,
        public readonly string $redirectUrl = '',
        public readonly string $reference = '',
        public readonly string $message = '',
    ) {}

    /**
     * Der Kunde muss zum Zahlungsdienstleister weitergeleitet werden.
     */
    public static function redirect(string $url, string $reference = ''): self
    {
        return new self(self::STATUS_REDIRECT, $url, $reference);
    }

    /**
     * Die Bestellung ist aufgegeben, bezahlt wird später (Rechnung, Vorkasse).
     */
    public static function pending(string $message = ''): self
    {
        return new self(self::STATUS_PENDING, '', '', $message);
    }

    public static function failed(string $message): self
    {
        return new self(self::STATUS_FAILED, '', '', $message);
    }

    public function isRedirect(): bool
    {
        return $this->status === self::STATUS_REDIRECT;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }
}
