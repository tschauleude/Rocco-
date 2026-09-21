<?php

declare(strict_types=1);

namespace Marian\Shop\Domain\Repository;

use Marian\Shop\Domain\Model\ShippingMethod;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<ShippingMethod>
 */
class ShippingMethodRepository extends Repository
{
    protected $defaultOrderings = [
        'sorting' => QueryInterface::ORDER_ASCENDING,
    ];

    /**
     * Die voreingestellte Versandart: die erste in der Sortierung.
     */
    public function findDefault(): ?ShippingMethod
    {
        $query = $this->createQuery();
        $query->setLimit(1);

        return $query->execute()->getFirst();
    }
}
