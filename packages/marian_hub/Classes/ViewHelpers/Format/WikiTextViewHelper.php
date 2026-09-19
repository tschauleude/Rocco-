<?php

declare(strict_types=1);

namespace Marian\Hub\ViewHelpers\Format;

use Marian\Hub\Domain\Model\Article;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Macht aus Wiki-Klammern echte Links: [[slug]] oder [[slug|Anderer Text]].
 *
 * Existiert der Zielartikel noch nicht, entsteht – wie in jedem Wiki – ein
 * "roter Link", der zeigt: hier fehlt noch was.
 *
 * <mh:format.wikiText pageUid="{settings.detailPid}">{article.bodytext}</mh:format.wikiText>
 */
class WikiTextViewHelper extends AbstractViewHelper
{
    private const TABLE = 'tx_marianhub_domain_model_article';

    /**
     * Der Fließtext ist redaktionell gepflegtes HTML und darf weder beim Hinein-
     * noch beim Hinausreichen maskiert werden.
     */
    protected $escapeOutput = false;

    protected $escapeChildren = false;

    /**
     * Slug => [uid, title, published] – spart Abfragen bei vielen Links im Text.
     *
     * @var array<string, array{uid: int, title: string, published: bool}|null>
     */
    private static array $slugCache = [];

    public function initializeArguments(): void
    {
        $this->registerArgument('value', 'string', 'Fließtext mit Wiki-Klammern');
        $this->registerArgument('pageUid', 'int', 'Seite mit dem Wiki-Plugin', false, 0);
    }

    public function render(): string
    {
        $text = (string)($this->arguments['value'] ?? $this->renderChildren());
        if (!str_contains($text, '[[')) {
            return $text;
        }

        $pageUid = (int)$this->arguments['pageUid'];

        return (string)preg_replace_callback(
            '/\[\[([^\]\|]{1,255})(?:\|([^\]]{1,255}))?\]\]/u',
            fn (array $matches): string => $this->renderLink(trim($matches[1]), trim($matches[2] ?? ''), $pageUid),
            $text
        );
    }

    private function renderLink(string $slug, string $label, int $pageUid): string
    {
        $target = $this->lookup($slug);
        $text = htmlspecialchars($label !== '' ? $label : ($target['title'] ?? $slug), ENT_QUOTES);

        if ($target === null) {
            return sprintf(
                '<span class="wiki-link wiki-link--missing" title="Artikel &quot;%s&quot; fehlt noch">%s</span>',
                htmlspecialchars($slug, ENT_QUOTES),
                $text
            );
        }

        $uri = $this->buildUri($target['uid'], $pageUid);
        if ($uri === '') {
            return $text;
        }

        return sprintf('<a class="wiki-link" href="%s">%s</a>', htmlspecialchars($uri, ENT_QUOTES), $text);
    }

    /**
     * @return array{uid: int, title: string, published: bool}|null
     */
    private function lookup(string $slug): ?array
    {
        if (array_key_exists($slug, self::$slugCache)) {
            return self::$slugCache[$slug];
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE);
        $row = $queryBuilder
            ->select('uid', 'title', 'status')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq('slug', $queryBuilder->createNamedParameter($slug)),
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        $result = null;
        if ($row !== false && (string)$row['status'] === Article::STATUS_PUBLISHED) {
            $result = [
                'uid' => (int)$row['uid'],
                'title' => (string)$row['title'],
                'published' => true,
            ];
        }

        return self::$slugCache[$slug] = $result;
    }

    private function buildUri(int $articleUid, int $pageUid): string
    {
        $request = $this->renderingContext->getAttribute(\Psr\Http\Message\ServerRequestInterface::class);
        if (!$request instanceof RequestInterface) {
            return '';
        }

        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $uriBuilder->setRequest($request);
        $uriBuilder->reset();
        if ($pageUid > 0) {
            $uriBuilder->setTargetPageUid($pageUid);
        }

        return $uriBuilder->uriFor('show', ['article' => $articleUid], 'Wiki', 'MarianHub', 'Wiki');
    }

    /**
     * Nur für Tests: leert den Slug-Zwischenspeicher.
     */
    public static function flushCache(): void
    {
        self::$slugCache = [];
    }
}
