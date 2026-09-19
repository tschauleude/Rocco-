<?php

declare(strict_types=1);

namespace Marian\Hub\Domain\Repository;

use Marian\Hub\Domain\Model\Article;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<Article>
 */
class ArticleRepository extends Repository
{
    protected $defaultOrderings = [
        'publishedAt' => QueryInterface::ORDER_DESCENDING,
        'title' => QueryInterface::ORDER_ASCENDING,
    ];

    /**
     * Veröffentlichte Artikel, optional auf eine Kategorie eingegrenzt.
     *
     * @return QueryResultInterface<Article>
     */
    public function findPublished(int $limit = 0, int $categoryUid = 0): QueryResultInterface
    {
        $query = $this->createQuery();
        $constraints = [$query->equals('status', Article::STATUS_PUBLISHED)];
        if ($categoryUid > 0) {
            $constraints[] = $query->contains('categories', $categoryUid);
        }

        $query->matching($query->logicalAnd(...$constraints));
        if ($limit > 0) {
            $query->setLimit($limit);
        }

        return $query->execute();
    }

    public function findOneBySlug(string $slug): ?Article
    {
        $query = $this->createQuery();
        $query->matching($query->equals('slug', $slug));
        $query->setLimit(1);

        return $query->execute()->getFirst();
    }

    /**
     * Volltextsuche über Titel, Untertitel, Teaser und Fließtext.
     *
     * @return QueryResultInterface<Article>
     */
    public function search(string $term): QueryResultInterface
    {
        $query = $this->createQuery();
        $needle = '%' . $this->escapeLike($term) . '%';

        $query->matching(
            $query->logicalAnd(
                $query->equals('status', Article::STATUS_PUBLISHED),
                $query->logicalOr(
                    $query->like('title', $needle),
                    $query->like('subtitle', $needle),
                    $query->like('teaser', $needle),
                    $query->like('bodytext', $needle),
                    $query->like('author', $needle),
                ),
            )
        );

        return $query->execute();
    }

    /**
     * Alphabetisches Register: Artikel nach Anfangsbuchstaben gruppiert.
     *
     * @return array<string, array<int, Article>>
     */
    public function findGroupedByInitial(): array
    {
        $query = $this->createQuery();
        $query->matching($query->equals('status', Article::STATUS_PUBLISHED));
        $query->setOrderings(['title' => QueryInterface::ORDER_ASCENDING]);

        $groups = [];
        foreach ($query->execute() as $article) {
            $groups[$article->getInitial()][] = $article;
        }

        ksort($groups, SORT_LOCALE_STRING);

        return $groups;
    }

    /**
     * Artikel in Arbeit – die Redaktionsliste für Marian selbst.
     *
     * @return QueryResultInterface<Article>
     */
    public function findInProgress(): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->matching($query->logicalNot($query->equals('status', Article::STATUS_PUBLISHED)));
        $query->setOrderings(['tstamp' => QueryInterface::ORDER_DESCENDING]);

        return $query->execute();
    }

    /**
     * Artikel, die im Fließtext auf den gegebenen Slug verlinken ([[slug]]).
     *
     * @return QueryResultInterface<Article>
     */
    public function findLinkingTo(string $slug): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->matching(
            $query->logicalAnd(
                $query->equals('status', Article::STATUS_PUBLISHED),
                $query->logicalNot($query->equals('slug', $slug)),
                $query->logicalOr(
                    $query->like('bodytext', '%[[' . $this->escapeLike($slug) . ']]%'),
                    $query->like('bodytext', '%[[' . $this->escapeLike($slug) . '|%'),
                ),
            )
        );

        return $query->execute();
    }

    /**
     * Maskiert LIKE-Sonderzeichen, damit Nutzereingaben keine Wildcards werden.
     */
    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }
}
