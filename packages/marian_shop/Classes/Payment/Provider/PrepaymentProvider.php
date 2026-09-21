<?php

declare(strict_types=1);

namespace Marian\Shop\Payment\Provider;

use Marian\Shop\Domain\Model\Order;
use Marian\Shop\Payment\PaymentContext;
use Marian\Shop\Payment\PaymentProviderInterface;
use Marian\Shop\Payment\PaymentResult;

/**
 * Vorkasse: Der Kunde überweist, die Ware geht nach Zahlungseingang raus.
 * Die Bankverbindung steht als Hinweistext an der Zahlungsart im Backend.
 */
class PrepaymentProvider implements PaymentProviderInterface
{
    public function getIdentifier(): string
    {
        return 'prepayment';
    }

    public function getLabel(): string
    {
        return 'Vorkasse';
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function start(Order $order, PaymentContext $context): PaymentResult
    {
        return PaymentResult::pending(sprintf(
            'Bitte überweise %s mit dem Verwendungszweck %s.',
            $order->getFormattedTotal(),
            $order->getOrderNumber()
        ));
    }
}
