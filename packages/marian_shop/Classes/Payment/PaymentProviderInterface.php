<?php

declare(strict_types=1);

namespace Marian\Shop\Payment;

use Marian\Shop\Domain\Model\Order;

/**
 * Eine Zahlungsart-Implementierung.
 *
 * Eine weitere Zahlungsart hinzuzufügen heißt: diese Schnittstelle
 * implementieren, den Dienst mit "marianshop.payment_provider" taggen und im
 * Backend eine Zahlungsart mit der passenden Kennung anlegen. Am Bestellablauf
 * ändert sich nichts.
 */
interface PaymentProviderInterface
{
    /**
     * Kennung, die im Backend an der Zahlungsart hinterlegt wird.
     */
    public function getIdentifier(): string;

    /**
     * Klartextname für Backend und Protokolle.
     */
    public function getLabel(): string;

    /**
     * Ob die Zahlungsart einsatzbereit ist – Stripe etwa nur mit Schlüsseln.
     */
    public function isAvailable(): bool;

    /**
     * Startet die Zahlung zu einer bereits gespeicherten Bestellung.
     */
    public function start(Order $order, PaymentContext $context): PaymentResult;
}
