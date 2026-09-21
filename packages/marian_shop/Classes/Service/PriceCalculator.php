<?php

declare(strict_types=1);

namespace Marian\Shop\Service;

use Marian\Shop\Domain\Cart\Cart;
use Marian\Shop\Domain\Cart\CartTotals;
use Marian\Shop\Domain\Model\PaymentMethod;
use Marian\Shop\Domain\Model\ShippingMethod;

/**
 * Rechnet einen Warenkorb zu Endsummen aus.
 *
 * Versandkosten und Zahlungsaufschlag enthalten Umsatzsteuer. Enthält der
 * Warenkorb mehrere Steuersätze, werden diese Nebenkosten anteilig auf die
 * Sätze verteilt – die sogenannte Aufteilung nach dem Verhältnis der
 * Hauptleistungen. Ein pauschaler Satz auf den Versand wäre einfacher,
 * aber falsch.
 */
class PriceCalculator
{
    /**
     * Fällt kein Artikel an (leerer Warenkorb), gilt dieser Satz für die
     * Nebenkosten.
     */
    private const FALLBACK_TAX_RATE = 19.0;

    public function calculate(
        Cart $cart,
        ?ShippingMethod $shipping = null,
        ?PaymentMethod $payment = null,
    ): CartTotals {
        $subtotal = $cart->getSubtotalGross();

        // Ein leerer Warenkorb kostet nichts: weder Versand noch Aufschlag.
        // Sonst zeigt die Kasse nach dem Leeren des Korbs einen Betrag an.
        $empty = $cart->isEmpty();
        $shippingGross = $empty ? 0 : ($shipping?->costFor($subtotal) ?? 0);
        $surchargeGross = $empty ? 0 : ($payment?->getSurchargeInCents() ?? 0);
        $total = $subtotal + $shippingGross + $surchargeGross;

        $taxRows = $this->buildTaxRows($cart, $shippingGross + $surchargeGross);

        $taxTotal = 0;
        foreach ($taxRows as $row) {
            $taxTotal += $row['tax'];
        }

        return new CartTotals(
            subtotalGross: $subtotal,
            shippingGross: $shippingGross,
            surchargeGross: $surchargeGross,
            totalGross: $total,
            totalNet: $total - $taxTotal,
            taxTotal: $taxTotal,
            taxRows: $taxRows,
            freeShippingRemaining: $this->freeShippingRemaining($shipping, $subtotal),
        );
    }

    /**
     * Steueraufschlüsselung inklusive anteiliger Nebenkosten.
     *
     * @return array<string, array{rate: float, net: int, tax: int, gross: int}>
     */
    private function buildTaxRows(Cart $cart, int $extraGross): array
    {
        $grossByRate = $cart->getGrossByTaxRate();

        if ($grossByRate === []) {
            if ($extraGross === 0) {
                return [];
            }

            $grossByRate = [number_format(self::FALLBACK_TAX_RATE, 2, '.', '') => 0];
        }

        // Nebenkosten im Verhältnis der Warenwerte verteilen. Sind alle
        // Warenwerte null, bekommt der erste Satz alles.
        $weights = $grossByRate;
        if (array_sum($weights) === 0) {
            $weights = array_map(static fn (): int => 1, $weights);
        }

        $extraShares = Money::distribute($extraGross, $weights);

        $rows = [];
        foreach ($grossByRate as $rateKey => $gross) {
            $rate = (float)$rateKey;
            $rowGross = $gross + ($extraShares[$rateKey] ?? 0);
            $rowTax = Money::taxFromGross($rowGross, $rate);

            $rows[$rateKey] = [
                'rate' => $rate,
                'net' => $rowGross - $rowTax,
                'tax' => $rowTax,
                'gross' => $rowGross,
            ];
        }

        krsort($rows, SORT_NUMERIC);

        return $rows;
    }

    /**
     * Wie viel noch bis zum versandkostenfreien Einkauf fehlt; 0 wenn der
     * Versand bereits frei ist oder die Versandart keine Grenze kennt.
     */
    private function freeShippingRemaining(?ShippingMethod $shipping, int $subtotal): int
    {
        if ($shipping === null || $shipping->getFreeFrom() <= 0.0) {
            return 0;
        }

        return max(0, $shipping->getFreeFromInCents() - $subtotal);
    }
}
