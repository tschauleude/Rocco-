<?php

declare(strict_types=1);

namespace Marian\Shop\Domain\Repository;

use Marian\Shop\Domain\Model\PaymentMethod;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<PaymentMethod>
 */
class PaymentMethodRepository extends Repository
{
    protected $defaultOrderings = [
        'sorting' => QueryInterface::ORDER_ASCENDING,
    ];

    public function findDefault(): ?PaymentMethod
    {
        $query = $this->createQuery();
        $query->setLimit(1);

        return $query->execute()->getFirst();
    }
}
