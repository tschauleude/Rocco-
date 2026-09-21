<?php

declare(strict_types=1);

namespace Marian\Shop\Payment;

/**
 * Alles, was eine Zahlungsart über die Umgebung wissen muss, ohne selbst
 * Request oder Routing zu kennen.
 */
final class PaymentContext
{
    public function __construct(
        public readonly string $successUrl,
        public readonly string $cancelUrl,
        public readonly string $locale = 'de',
        public readonly string $currency = 'EUR',
    ) {}
}
