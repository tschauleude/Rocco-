<?php

declare(strict_types=1);

namespace Marian\Hub\Tests\Unit\Domain\Model;

use Marian\Hub\Domain\Model\Article;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ArticleTest extends UnitTestCase
{
    /**
     * @test
     */
    public function readingTimeIsAtLeastOneMinute(): void
    {
        $article = new Article();
        $article->setBodytext('<p>Kurz.</p>');

        self::assertSame(1, $article->getReadingTime());
    }

    /**
     * @test
     */
    public function readingTimeGrowsWithTheText(): void
    {
        $article = new Article();
        $article->setBodytext('<p>' . str_repeat('Wort ', 600) . '</p>');

        // 600 Wörter bei 200 Wörtern pro Minute.
        self::assertSame(3, $article->getReadingTime());
    }

    /**
     * @test
     */
    public function wordCountIgnoresMarkup(): void
    {
        $article = new Article();
        $article->setBodytext('<p>Ein <strong>kurzer</strong> Satz</p>');

        self::assertSame(3, $article->getWordCount());
    }

    /**
     * @test
     */
    public function initialUsesTheFirstLetterOfTheTitle(): void
    {
        $article = new Article();
        $article->setTitle('  Überstunden im Abi-Komitee');

        self::assertSame('Ü', $article->getInitial());
    }

    /**
     * @test
     */
    public function titlesWithoutLetterLandUnderHash(): void
    {
        $article = new Article();
        $article->setTitle('3D-Druck');

        self::assertSame('#', $article->getInitial());
    }

    /**
     * @test
     */
    public function onlyPublishedArticlesCountAsPublished(): void
    {
        $article = new Article();
        self::assertFalse($article->isPublished());

        $article->setStatus(Article::STATUS_PUBLISHED);
        self::assertTrue($article->isPublished());
        self::assertSame('Veröffentlicht', $article->getStatusLabel());
    }
}
