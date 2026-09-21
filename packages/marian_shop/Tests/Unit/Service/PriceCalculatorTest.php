<?php

declare(strict_types=1);

namespace Marian\Shop\Tests\Unit\Service;

use Marian\Shop\Domain\Cart\Cart;
use Marian\Shop\Domain\Cart\CartItem;
use Marian\Shop\Domain\Model\PaymentMethod;
use Marian\Shop\Domain\Model\Product;
use Marian\Shop\Domain\Model\ShippingMethod;
use Marian\Shop\Service\PriceCalculator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class PriceCalculatorTest extends UnitTestCase
{
    private PriceCalculator $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new PriceCalculator();
    }

    private function product(float $price, float $taxRate): Product
    {
        $product = new Product();
        $product->setPrice($price);
        $product->setTaxRate($taxRate);
        $product->setStockManaged(false);

        return $product;
    }

    private function shipping(float $price, float $freeFrom = 0.0): ShippingMethod
    {
        $shipping = new ShippingMethod();
        $shipping->setPrice($price);
        $shipping->setFreeFrom($freeFrom);

        return $shipping;
    }

    /**
     * @test
     */
    public function totalsAddUpForASingleTaxRate(): void
    {
        $cart = new Cart(['1-0' => new CartItem($this->product(29.90, 19.0), null, 2)]);

        $totals = $this->subject->calculate($cart, $this->shipping(4.90));

        self::assertSame(5980, $totals->subtotalGross);
        self::assertSame(490, $totals->shippingGross);
        self::assertSame(6470, $totals->totalGross);
        self::assertSame($totals->totalGross, $totals->totalNet + $totals->taxTotal);
    }

    /**
     * @test
     */
    public function shippingIsSplitAcrossTaxRates(): void
    {
        $cart = new Cart([
            '1-0' => new CartItem($this->product(29.90, 19.0), null, 2),
            '2-0' => new CartItem($this->product(12.00, 7.0), null, 1),
        ]);

        $totals = $this->subject->calculate($cart, $this->shipping(4.90, 100.00));

        self::assertCount(2, $totals->taxRows, 'Beide Steuersätze müssen auftauchen.');

        $grossSum = array_sum(array_column($totals->taxRows, 'gross'));
        $netSum = array_sum(array_column($totals->taxRows, 'net'));
        $taxSum = array_sum(array_column($totals->taxRows, 'tax'));

        self::assertSame($totals->totalGross, $grossSum, 'Die Steuerzeilen müssen die Gesamtsumme ergeben.');
        self::assertSame($grossSum, $netSum + $taxSum);
        self::assertSame($totals->totalNet, $netSum);
        self::assertSame($totals->taxTotal, $taxSum);
    }

    /**
     * @test
     */
    public function shippingIsFreeAboveTheThreshold(): void
    {
        $cart = new Cart(['1-0' => new CartItem($this->product(29.90, 19.0), null, 2)]);

        $totals = $this->subject->calculate($cart, $this->shipping(4.90, 50.00));

        self::assertSame(0, $totals->shippingGross);
        self::assertTrue($totals->hasFreeShipping());
        self::assertSame(0, $totals->freeShippingRemaining);
    }

    /**
     * @test
     */
    public function theRemainderToFreeShippingIsReported(): void
    {
        $cart = new Cart(['1-0' => new CartItem($this->product(29.90, 19.0), null, 1)]);

        $totals = $this->subject->calculate($cart, $this->shipping(4.90, 50.00));

        self::assertSame(2010, $totals->freeShippingRemaining);
    }

    /**
     * @test
     */
    public function anEmptyCartCostsNothing(): void
    {
        $payment = new PaymentMethod();
        $payment->setSurcharge(2.50);

        $totals = $this->subject->calculate(new Cart(), $this->shipping(4.90), $payment);

        self::assertSame(0, $totals->totalGross, 'Ein leerer Warenkorb darf keinen Versand kosten.');
        self::assertSame(0, $totals->shippingGross);
        self::assertSame(0, $totals->surchargeGross);
        self::assertSame([], $totals->taxRows);
    }

    /**
     * @test
     */
    public function paymentSurchargeIsAddedAndTaxed(): void
    {
        $payment = new PaymentMethod();
        $payment->setSurcharge(2.50);

        $cart = new Cart(['1-0' => new CartItem($this->product(12.00, 7.0), null, 3)]);

        $totals = $this->subject->calculate($cart, $this->shipping(4.90, 20.00), $payment);

        self::assertSame(250, $totals->surchargeGross);
        self::assertTrue($totals->hasSurcharge());
        self::assertSame(3600 + 250, $totals->totalGross, 'Ab 20 € ist der Versand frei.');
        self::assertSame(
            $totals->totalGross,
            array_sum(array_column($totals->taxRows, 'gross'))
        );
    }
}
