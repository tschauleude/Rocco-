<?php

declare(strict_types=1);

namespace Marian\Shop\Service;

/**
 * Geldbeträge werden in dieser Extension als ganzzahlige Cent geführt.
 *
 * Fließkomma und Geld vertragen sich nicht: 0.1 + 0.2 ist in PHP nicht 0.3,
 * und bei einer Bestellung mit zwanzig Positionen summiert sich so ein Fehler
 * zu einem falschen Rechnungsbetrag. Editoren pflegen Euro, gerechnet wird Cent.
 */
final class Money
{
    public static function toCents(float $euro): int
    {
        return (int)round($euro * 100);
    }

    public static function toEuro(int $cents): float
    {
        return $cents / 100;
    }

    /**
     * Deutsche Schreibweise inklusive Währung: "1.234,50 €".
     */
    public static function format(int $cents): string
    {
        return number_format($cents / 100, 2, ',', '.') . ' €';
    }

    /**
     * Nettobetrag zu einem Bruttobetrag bei gegebenem Steuersatz.
     */
    public static function netFromGross(int $grossCents, float $taxRate): int
    {
        return (int)round($grossCents / (1 + $taxRate / 100));
    }

    /**
     * Enthaltene Steuer in einem Bruttobetrag.
     */
    public static function taxFromGross(int $grossCents, float $taxRate): int
    {
        return $grossCents - self::netFromGross($grossCents, $taxRate);
    }

    /**
     * Verteilt einen Betrag im Verhältnis der Gewichte und legt den
     * Rundungsrest auf den größten Anteil, damit die Summe exakt stimmt.
     *
     * @param array<string|int, int> $weights
     * @return array<string|int, int>
     */
    public static function distribute(int $amountCents, array $weights): array
    {
        $total = array_sum($weights);
        if ($total <= 0 || $weights === []) {
            return array_map(static fn (): int => 0, $weights);
        }

        $shares = [];
        foreach ($weights as $key => $weight) {
            $shares[$key] = (int)floor($amountCents * $weight / $total);
        }

        $remainder = $amountCents - array_sum($shares);
        if ($remainder !== 0) {
            $largest = array_keys($weights, max($weights), true)[0];
            $shares[$largest] += $remainder;
        }

        return $shares;
    }
}
