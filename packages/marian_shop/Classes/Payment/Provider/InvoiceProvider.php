<?php

declare(strict_types=1);

namespace Marian\Shop\Payment\Provider;

use Marian\Shop\Domain\Model\Order;
use Marian\Shop\Payment\PaymentContext;
use Marian\Shop\Payment\PaymentProviderInterface;
use Marian\Shop\Payment\PaymentResult;

/**
 * Kauf auf Rechnung: Die Ware geht raus, bezahlt wird danach.
 * Es gibt nichts abzuwickeln – die Bestellung bleibt schlicht offen.
 */
class InvoiceProvider implements PaymentProviderInterface
{
    public function getIdentifier(): string
    {
        return 'invoice';
    }

    public function getLabel(): string
    {
        return 'Rechnung';
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function start(Order $order, PaymentContext $context): PaymentResult
    {
        return PaymentResult::pending(
            'Die Rechnung liegt der Lieferung bei.'
        );
    }
}
