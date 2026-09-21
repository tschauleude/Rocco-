<?php

declare(strict_types=1);

namespace Marian\Shop\Service;

use Marian\Shop\Domain\Cart\Cart;
use Marian\Shop\Domain\Cart\CartTotals;
use Marian\Shop\Domain\Checkout\CheckoutForm;
use Marian\Shop\Domain\Model\Order;
use Marian\Shop\Domain\Model\OrderItem;
use Marian\Shop\Domain\Model\PaymentMethod;
use Marian\Shop\Domain\Model\ShippingMethod;
use Marian\Shop\Domain\Repository\OrderRepository;
use Marian\Shop\Exception\CheckoutException;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

/**
 * Macht aus einem Warenkorb eine Bestellung.
 */
class OrderService
{
    private const PRODUCT_TABLE = 'tx_marianshop_domain_model_product';
    private const VARIANT_TABLE = 'tx_marianshop_domain_model_productvariant';
    private const ORDER_TABLE = 'tx_marianshop_domain_model_order';

    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly PersistenceManagerInterface $persistenceManager,
        private readonly ConnectionPool $connectionPool,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Legt die Bestellung an und bucht den Bestand ab.
     *
     * @throws CheckoutException wenn der Bestand zwischenzeitlich nicht mehr reicht
     */
    public function createFromCart(
        Cart $cart,
        CartTotals $totals,
        CheckoutForm $form,
        ShippingMethod $shipping,
        PaymentMethod $payment,
        int $feUserUid,
        int $storagePid,
    ): Order {
        if ($cart->isEmpty()) {
            throw new CheckoutException('Der Warenkorb ist leer.');
        }

        // Erst den Bestand sichern, dann die Bestellung schreiben. Schlägt eine
        // Abbuchung fehl, werden die bereits gebuchten zurückgegeben.
        $reserved = $this->reserveStock($cart);

        try {
            $order = $this->buildOrder($cart, $totals, $form, $shipping, $payment, $feUserUid, $storagePid);
            $this->orderRepository->add($order);
            $this->persistenceManager->persistAll();

            // Die Bestellnummer braucht die uid, die es erst nach dem Speichern gibt.
            $order->setOrderNumber($this->buildOrderNumber($order));
            $this->orderRepository->update($order);
            $this->persistenceManager->persistAll();
        } catch (\Throwable $exception) {
            $this->releaseStock($reserved);
            $this->logger->error('Bestellung konnte nicht angelegt werden', [
                'exception' => $exception->getMessage(),
            ]);

            throw new CheckoutException(
                'Die Bestellung konnte nicht gespeichert werden. Bitte versuche es noch einmal.',
                0,
                $exception
            );
        }

        return $order;
    }

    private function buildOrder(
        Cart $cart,
        CartTotals $totals,
        CheckoutForm $form,
        ShippingMethod $shipping,
        PaymentMethod $payment,
        int $feUserUid,
        int $storagePid,
    ): Order {
        $order = new Order();
        $order->setPid($storagePid);
        $order->setFeUser($feUserUid);
        $order->setEmail($form->email);
        $order->setPhone($form->phone);
        $order->setCustomerNote($form->note);
        $order->setAcceptedTerms($form->acceptTerms);
        $order->setAcceptedWithdrawal($form->acceptWithdrawal);
        $order->setOrderedAt(new \DateTime());

        $billing = $form->getBillingAddress()->copyForOrder('billing');
        $billing->setPid($storagePid);
        $order->setBillingAddress($billing);

        $shippingAddress = $form->getShippingAddress();
        if ($shippingAddress !== null) {
            $copy = $shippingAddress->copyForOrder('shipping');
            $copy->setPid($storagePid);
            $order->setShippingAddress($copy);
        }

        $order->setShippingMethod($shipping->getUid() ?? 0);
        $order->setShippingTitle($shipping->getTitle());
        $order->setShippingGross($totals->shippingGross);

        $order->setPaymentMethod($payment->getUid() ?? 0);
        $order->setPaymentTitle($payment->getTitle());
        $order->setPaymentProvider($payment->getProvider());
        $order->setPaymentSurchargeGross($totals->surchargeGross);

        $order->setSubtotalGross($totals->subtotalGross);
        $order->setTotalGross($totals->totalGross);
        $order->setTotalNet($totals->totalNet);
        $order->setTaxTotal($totals->taxTotal);
        $order->setTaxBreakdown($totals->getTaxBreakdownJson());

        foreach ($cart->getItems() as $cartItem) {
            $item = new OrderItem();
            $item->setPid($storagePid);
            $item->setProduct($cartItem->product->getUid() ?? 0);
            $item->setVariant($cartItem->variant?->getUid() ?? 0);
            $item->setTitle($cartItem->getTitle());
            $item->setVariantTitle($cartItem->getVariantTitle());
            $item->setSku($cartItem->getSku());
            $item->setQuantity($cartItem->quantity);
            $item->setUnitGross($cartItem->getUnitGross());
            $item->setLineGross($cartItem->getLineGross());
            $item->setLineTax($cartItem->getLineTax());
            $item->setTaxRate($cartItem->getTaxRate());
            $order->addItem($item);
        }

        return $order;
    }

    /**
     * Bestellnummer aus Jahr und laufender uid – eindeutig ohne Zählertabelle
     * und ohne die Gefahr, dass zwei gleichzeitige Bestellungen dieselbe
     * Nummer ziehen.
     */
    private function buildOrderNumber(Order $order): string
    {
        return sprintf('%s-%05d', date('Y'), $order->getUid() ?? 0);
    }

    /**
     * Bucht den Bestand ab. Die Bedingung steht im UPDATE selbst, damit
     * zwischen Prüfen und Buchen keine andere Bestellung dazwischenkommt.
     *
     * @return array<int, array{table: string, uid: int, quantity: int}>
     * @throws CheckoutException
     */
    private function reserveStock(Cart $cart): array
    {
        $reserved = [];

        foreach ($cart->getItems() as $item) {
            if (!$item->product->isStockManaged()) {
                continue;
            }

            $variant = $item->variant;
            $table = $variant !== null ? self::VARIANT_TABLE : self::PRODUCT_TABLE;
            $uid = $variant?->getUid() ?? $item->product->getUid() ?? 0;

            if ($uid <= 0) {
                continue;
            }

            if (!$this->decrementStock($table, $uid, $item->quantity)) {
                $this->releaseStock($reserved);

                throw new CheckoutException(sprintf(
                    'Von „%s" ist nicht mehr genug vorrätig. Bitte passe die Menge an.',
                    $item->getFullTitle()
                ));
            }

            $reserved[] = ['table' => $table, 'uid' => $uid, 'quantity' => $item->quantity];
        }

        return $reserved;
    }

    private function decrementStock(string $table, int $uid, int $quantity): bool
    {
        $connection = $this->connectionPool->getConnectionForTable($table);

        $affected = $connection->executeStatement(
            sprintf(
                'UPDATE %s SET stock = stock - :quantity WHERE uid = :uid AND stock >= :quantity',
                $connection->quoteIdentifier($table)
            ),
            ['quantity' => $quantity, 'uid' => $uid],
            ['quantity' => Connection::PARAM_INT, 'uid' => Connection::PARAM_INT]
        );

        return $affected > 0;
    }

    /**
     * @param array<int, array{table: string, uid: int, quantity: int}> $reserved
     */
    private function releaseStock(array $reserved): void
    {
        foreach ($reserved as $entry) {
            $connection = $this->connectionPool->getConnectionForTable($entry['table']);
            $connection->executeStatement(
                sprintf(
                    'UPDATE %s SET stock = stock + :quantity WHERE uid = :uid',
                    $connection->quoteIdentifier($entry['table'])
                ),
                ['quantity' => $entry['quantity'], 'uid' => $entry['uid']],
                ['quantity' => Connection::PARAM_INT, 'uid' => Connection::PARAM_INT]
            );
        }
    }

    /**
     * Vermerkt eine erfolgreiche Zahlung. Wird sowohl vom Webhook als auch
     * bei Zahlarten ohne Umleitung aufgerufen und ist absichtlich idempotent:
     * Stripe stellt Webhooks mehrfach zu.
     */
    public function markPaid(Order $order, string $reference = ''): bool
    {
        if ($order->isPaid()) {
            return false;
        }

        $order->setPaymentStatus(Order::PAYMENT_PAID);
        $order->setStatus(Order::STATUS_CONFIRMED);
        $order->setPaidAt(new \DateTime());
        if ($reference !== '') {
            $order->setPaymentReference($reference);
        }

        $this->orderRepository->update($order);
        $this->persistenceManager->persistAll();

        $this->logger->info('Zahlung verbucht', ['order' => $order->getOrderNumber()]);

        return true;
    }

    public function markFailed(Order $order, string $reason = ''): void
    {
        $order->setPaymentStatus(Order::PAYMENT_FAILED);
        $this->orderRepository->update($order);
        $this->persistenceManager->persistAll();

        $this->logger->warning('Zahlung fehlgeschlagen', [
            'order' => $order->getOrderNumber(),
            'reason' => $reason,
        ]);
    }

    public function storePaymentReference(Order $order, string $reference): void
    {
        $order->setPaymentReference($reference);
        $this->orderRepository->update($order);
        $this->persistenceManager->persistAll();
    }

    /**
     * Einmal-Schlüssel, mit dem auch ein Gast seine Bestellbestätigung
     * aufrufen kann, ohne dass sich fremde Bestellungen durchprobieren lassen.
     */
    public function createAccessToken(Order $order): string
    {
        return substr(
            hash_hmac(
                'sha256',
                'marianshop-order-' . ($order->getUid() ?? 0),
                (string)$GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']
            ),
            0,
            32
        );
    }

    public function isValidAccessToken(Order $order, string $token): bool
    {
        return hash_equals($this->createAccessToken($order), $token);
    }
}
