<?php

declare(strict_types=1);

namespace Marian\Shop\Domain\Repository;

use Marian\Shop\Domain\Model\Product;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<Product>
 */
class ProductRepository extends Repository
{
    protected $defaultOrderings = [
        'featured' => QueryInterface::ORDER_DESCENDING,
        'title' => QueryInterface::ORDER_ASCENDING,
    ];

    public function findOneBySlug(string $slug): ?Product
    {
        $query = $this->createQuery();
        $query->matching($query->equals('slug', $slug));
        $query->setLimit(1);

        return $query->execute()->getFirst();
    }

    /**
     * @return QueryResultInterface<Product>
     */
    public function findForShop(int $limit = 0, int $categoryUid = 0): QueryResultInterface
    {
        $query = $this->createQuery();
        if ($categoryUid > 0) {
            $query->matching($query->contains('categories', $categoryUid));
        }
        if ($limit > 0) {
            $query->setLimit($limit);
        }

        return $query->execute();
    }

    /**
     * @return QueryResultInterface<Product>
     */
    public function findFeatured(int $limit = 3): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->matching($query->equals('featured', true));
        $query->setLimit($limit);

        return $query->execute();
    }

    /**
     * Mehrere Artikel auf einmal – der Warenkorb braucht nicht pro Position
     * eine eigene Abfrage.
     *
     * @param int[] $uids
     * @return array<int, Product>
     */
    public function findByUids(array $uids): array
    {
        $uids = array_values(array_unique(array_filter($uids)));
        if ($uids === []) {
            return [];
        }

        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->matching($query->in('uid', $uids));

        $products = [];
        foreach ($query->execute() as $product) {
            $products[$product->getUid()] = $product;
        }

        return $products;
    }
}
