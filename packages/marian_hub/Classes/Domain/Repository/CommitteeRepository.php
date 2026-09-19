<?php

declare(strict_types=1);

namespace Marian\Hub\Domain\Repository;

use Marian\Hub\Domain\Model\Committee;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<Committee>
 */
class CommitteeRepository extends Repository
{
    protected $defaultOrderings = [
        'title' => QueryInterface::ORDER_ASCENDING,
    ];
}
