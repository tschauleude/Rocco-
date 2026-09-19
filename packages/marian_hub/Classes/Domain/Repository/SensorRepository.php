<?php

declare(strict_types=1);

namespace Marian\Hub\Domain\Repository;

use Marian\Hub\Domain\Model\Sensor;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<Sensor>
 */
class SensorRepository extends Repository
{
    protected $defaultOrderings = [
        'location' => QueryInterface::ORDER_ASCENDING,
        'title' => QueryInterface::ORDER_ASCENDING,
    ];

    public function findOneByIdentifier(string $identifier): ?Sensor
    {
        $query = $this->createQuery();
        $query->matching($query->equals('identifier', $identifier));
        $query->setLimit(1);

        return $query->execute()->getFirst();
    }

    /**
     * @return QueryResultInterface<Sensor>
     */
    public function findActive(): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->matching($query->equals('active', true));

        return $query->execute();
    }

    /**
     * @return QueryResultInterface<Sensor>
     */
    public function findByProjectUid(int $projectUid): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->matching($query->equals('project', $projectUid));

        return $query->execute();
    }
}
