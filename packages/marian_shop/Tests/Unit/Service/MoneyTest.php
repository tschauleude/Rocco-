<?php

declare(strict_types=1);

namespace Marian\Shop\Tests\Unit\Service;

use Marian\Shop\Service\Money;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class MoneyTest extends UnitTestCase
{
    /**
     * @test
     */
    public function eurosBecomeCentsWithoutDrift(): void
    {
        self::assertSame(1999, Money::toCents(19.99));
        self::assertSame(3990, Money::toCents(39.90));
        self::assertSame(0, Money::toCents(0.0));
        self::assertSame(-250, Money::toCents(-2.50));
    }

    /**
     * @test
     */
    public function amountsAreFormattedInGermanNotation(): void
    {
        self::assertSame('1.234,50 €', Money::format(123450));
        self::assertSame('0,00 €', Money::format(0));
        self::assertSame('8,50 €', Money::format(850));
    }

    /**
     * @test
     */
    public function taxIsSplitOutOfAGrossAmount(): void
    {
        self::assertSame(1000, Money::netFromGross(1190, 19.0));
        self::assertSame(190, Money::taxFromGross(1190, 19.0));
        self::assertSame(79, Money::taxFromGross(1200, 7.0));
    }

    /**
     * @test
     */
    public function withoutTaxNetEqualsGross(): void
    {
        self::assertSame(1500, Money::netFromGross(1500, 0.0));
        self::assertSame(0, Money::taxFromGross(1500, 0.0));
    }

    /**
     * @test
     */
    public function distributionKeepsEveryCent(): void
    {
        $shares = Money::distribute(499, ['a' => 1000, 'b' => 500, 'c' => 333]);

        self::assertSame(499, array_sum($shares), 'Die Verteilung darf keinen Cent verlieren.');
        self::assertSame(273, $shares['a'], 'Der Rundungsrest gehört auf den größten Anteil.');
    }

    /**
     * @test
     */
    public function distributionHandlesEqualWeights(): void
    {
        $shares = Money::distribute(100, ['x' => 1, 'y' => 1, 'z' => 1]);

        self::assertSame(100, array_sum($shares));
        self::assertSame([34, 33, 33], array_values($shares));
    }

    /**
     * @test
     */
    public function distributionWithoutWeightsYieldsNothing(): void
    {
        self::assertSame([], Money::distribute(500, []));
        self::assertSame(['a' => 0], Money::distribute(500, ['a' => 0]));
    }
}
