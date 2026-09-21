<?php

declare(strict_types=1);

namespace Marian\Shop\Payment;

use Marian\Shop\Domain\Model\Order;
use Marian\Shop\Service\Money;
use Marian\Shop\Service\OrderMailService;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * Verarbeitet Stripe-Ereignisse.
 *
 * Arbeitet bewusst direkt auf der Datenbank statt über Extbase: ein Webhook
 * ist kein Frontend-Request, Extbase-Repositories hätten hier keinen
 * Konfigurationskontext.
 */
class StripeWebhookHandler
{
    private const ORDER_TABLE = 'tx_marianshop_domain_model_order';

    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly OrderMailService $mailService,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * @param array<string, mixed> $event
     * @return array{handled: bool, message: string}
     */
    public function handle(array $event): array
    {
        $type = (string)($event['type'] ?? '');
        $object = $event['data']['object'] ?? [];
        if (!is_array($object)) {
            return ['handled' => false, 'message' => 'Ereignis ohne Daten'];
        }

        return match ($type) {
            'checkout.session.completed', 'checkout.session.async_payment_succeeded'
                => $this->markPaid($object),
            'checkout.session.expired', 'checkout.session.async_payment_failed'
                => $this->markFailed($object),
            default => ['handled' => false, 'message' => 'Ereignis ' . $type . ' wird nicht ausgewertet'],
        };
    }

    /**
     * @param array<string, mixed> $session
     * @return array{handled: bool, message: string}
     */
    private function markPaid(array $session): array
    {
        $order = $this->findOrder($session);
        if ($order === null) {
            return ['handled' => false, 'message' => 'Bestellung nicht gefunden'];
        }

        // Stripe stellt Ereignisse mehrfach zu – doppelt bezahlt gibt es nicht.
        if ($order['payment_status'] === Order::PAYMENT_PAID) {
            return ['handled' => true, 'message' => 'Bereits als bezahlt vermerkt'];
        }

        $now = time();
        $this->connectionPool->getConnectionForTable(self::ORDER_TABLE)->update(
            self::ORDER_TABLE,
            [
                'payment_status' => Order::PAYMENT_PAID,
                'status' => Order::STATUS_CONFIRMED,
                'paid_at' => $now,
                'tstamp' => $now,
            ],
            ['uid' => (int)$order['uid']],
            [Connection::PARAM_STR, Connection::PARAM_STR, Connection::PARAM_INT, Connection::PARAM_INT]
        );

        $this->logger->info('Zahlung über Webhook verbucht', ['order' => $order['order_number']]);

        $this->mailService->sendPaymentReceived(
            (string)$order['email'],
            (string)$order['order_number'],
            Money::format((int)$order['total_gross'])
        );

        return ['handled' => true, 'message' => 'Zahlung verbucht'];
    }

    /**
     * @param array<string, mixed> $session
     * @return array{handled: bool, message: string}
     */
    private function markFailed(array $session): array
    {
        $order = $this->findOrder($session);
        if ($order === null) {
            return ['handled' => false, 'message' => 'Bestellung nicht gefunden'];
        }

        if ($order['payment_status'] === Order::PAYMENT_PAID) {
            // Eine bereits bezahlte Bestellung nicht durch ein spätes
            // Abbruch-Ereignis zurückwerfen.
            return ['handled' => true, 'message' => 'Bereits bezahlt, Abbruch ignoriert'];
        }

        $this->connectionPool->getConnectionForTable(self::ORDER_TABLE)->update(
            self::ORDER_TABLE,
            ['payment_status' => Order::PAYMENT_FAILED, 'tstamp' => time()],
            ['uid' => (int)$order['uid']],
            [Connection::PARAM_STR, Connection::PARAM_INT]
        );

        $this->logger->info('Zahlung abgebrochen', ['order' => $order['order_number']]);

        return ['handled' => true, 'message' => 'Abbruch vermerkt'];
    }

    /**
     * Sucht die Bestellung über die gespeicherte Sitzungs-ID und ersatzweise
     * über die Bestellnummer aus client_reference_id.
     *
     * @param array<string, mixed> $session
     * @return array<string, mixed>|null
     */
    private function findOrder(array $session): ?array
    {
        $sessionId = (string)($session['id'] ?? '');
        $orderNumber = (string)($session['client_reference_id'] ?? '');

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::ORDER_TABLE);
        $queryBuilder->getRestrictions()->removeAll();

        $constraints = [];
        if ($sessionId !== '') {
            $constraints[] = $queryBuilder->expr()->eq(
                'payment_reference',
                $queryBuilder->createNamedParameter($sessionId)
            );
        }
        if ($orderNumber !== '') {
            $constraints[] = $queryBuilder->expr()->eq(
                'order_number',
                $queryBuilder->createNamedParameter($orderNumber)
            );
        }

        if ($constraints === []) {
            return null;
        }

        $row = $queryBuilder
            ->select('uid', 'order_number', 'email', 'total_gross', 'payment_status')
            ->from(self::ORDER_TABLE)
            ->where(
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->or(...$constraints),
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        return $row === false ? null : $row;
    }
}
