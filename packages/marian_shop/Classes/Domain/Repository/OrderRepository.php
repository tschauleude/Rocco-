<?php

declare(strict_types=1);

namespace Marian\Shop\Domain\Repository;

use Marian\Shop\Domain\Model\Order;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<Order>
 */
class OrderRepository extends Repository
{
    protected $defaultOrderings = [
        'orderedAt' => QueryInterface::ORDER_DESCENDING,
    ];

    /**
     * Bestellungen eines Kunden – die Grundlage der Bestellübersicht im Konto.
     *
     * @return QueryResultInterface<Order>
     */
    public function findByCustomer(int $feUserUid): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->matching($query->equals('feUser', $feUserUid));

        return $query->execute();
    }

    public function findOneByOrderNumber(string $orderNumber): ?Order
    {
        $query = $this->createQuery();
        $query->matching($query->equals('orderNumber', $orderNumber));
        $query->setLimit(1);

        return $query->execute()->getFirst();
    }

    /**
     * Zuordnung einer Zahlung zur Bestellung – der Webhook kennt nur die
     * Referenz des Zahlungsdienstleisters.
     */
    public function findOneByPaymentReference(string $reference): ?Order
    {
        if ($reference === '') {
            return null;
        }

        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->matching($query->equals('paymentReference', $reference));
        $query->setLimit(1);

        return $query->execute()->getFirst();
    }
}
