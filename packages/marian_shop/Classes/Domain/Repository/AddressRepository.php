<?php

declare(strict_types=1);

namespace Marian\Shop\Domain\Repository;

use Marian\Shop\Domain\Model\Address;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<Address>
 */
class AddressRepository extends Repository
{
    protected $defaultOrderings = [
        'isDefault' => QueryInterface::ORDER_DESCENDING,
        'lastName' => QueryInterface::ORDER_ASCENDING,
    ];

    /**
     * Das Adressbuch eines Kunden – ohne die an Bestellungen eingefrorenen
     * Kopien.
     *
     * @return QueryResultInterface<Address>
     */
    public function findByCustomer(int $feUserUid): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->matching(
            $query->logicalAnd(
                $query->equals('feUser', $feUserUid),
                $query->equals('isArchived', false),
            )
        );

        return $query->execute();
    }

    /**
     * Sicherer Zugriff: liefert die Adresse nur, wenn sie diesem Kunden gehört.
     */
    public function findOwnedBy(int $addressUid, int $feUserUid): ?Address
    {
        if ($addressUid <= 0 || $feUserUid <= 0) {
            return null;
        }

        $query = $this->createQuery();
        $query->matching(
            $query->logicalAnd(
                $query->equals('uid', $addressUid),
                $query->equals('feUser', $feUserUid),
                $query->equals('isArchived', false),
            )
        );
        $query->setLimit(1);

        return $query->execute()->getFirst();
    }
}
