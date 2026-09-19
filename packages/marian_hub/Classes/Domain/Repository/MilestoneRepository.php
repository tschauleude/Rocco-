<?php

declare(strict_types=1);

namespace Marian\Hub\Domain\Repository;

use Marian\Hub\Domain\Model\Milestone;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<Milestone>
 */
class MilestoneRepository extends Repository
{
    protected $defaultOrderings = [
        'date' => QueryInterface::ORDER_ASCENDING,
    ];

    /**
     * Kommende Termine ab heute.
     *
     * @return QueryResultInterface<Milestone>
     */
    public function findUpcoming(int $limit = 0): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->matching($query->greaterThanOrEqual('date', strtotime('today')));
        if ($limit > 0) {
            $query->setLimit($limit);
        }

        return $query->execute();
    }

    /**
     * Der Termin, auf den der große Countdown zählt – sonst der nächste anstehende.
     */
    public function findCountdownTarget(): ?Milestone
    {
        $query = $this->createQuery();
        $query->matching($query->equals('isCountdownTarget', true));
        $query->setLimit(1);

        $target = $query->execute()->getFirst();
        if ($target instanceof Milestone) {
            return $target;
        }

        return $this->findUpcoming(1)->getFirst();
    }
}
