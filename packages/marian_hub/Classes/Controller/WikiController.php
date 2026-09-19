<?php

declare(strict_types=1);

namespace Marian\Hub\Controller;

use Marian\Hub\Domain\Model\Article;
use Marian\Hub\Domain\Repository\ArticleRepository;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Annotation as Extbase;

/**
 * Wiki und journalistische Arbeit: Artikelliste, Register, Suche, Einzelansicht.
 */
class WikiController extends AbstractHubController
{
    public function __construct(private readonly ArticleRepository $articleRepository) {}

    /**
     * Aktuelle Artikel, optional auf eine Kategorie gefiltert.
     */
    public function listAction(int $category = 0): ResponseInterface
    {
        $this->view->assignMultiple([
            'articles' => $this->articleRepository->findPublished(
                $this->intSetting('articleLimit', 0),
                $category > 0 ? $category : $this->intSetting('category', 0),
            ),
            'activeCategory' => $category,
            'showDesk' => (bool)($this->settings['showDesk'] ?? false),
            'desk' => ($this->settings['showDesk'] ?? false) ? $this->articleRepository->findInProgress() : null,
        ]);

        return $this->htmlResponse();
    }

    /**
     * Alphabetisches Register aller Artikel.
     */
    public function indexAction(): ResponseInterface
    {
        $groups = $this->articleRepository->findGroupedByInitial();

        $this->view->assignMultiple([
            'groups' => $groups,
            'initials' => array_keys($groups),
            'total' => array_sum(array_map('count', $groups)),
        ]);

        return $this->htmlResponse();
    }

    /**
     * Volltextsuche im Wiki.
     */
    public function searchAction(string $q = ''): ResponseInterface
    {
        $term = trim($q);
        $results = null;
        if (mb_strlen($term) >= 2) {
            $results = $this->articleRepository->search($term);
        }

        $this->view->assignMultiple([
            'term' => $term,
            'results' => $results,
            'resultCount' => $results?->count() ?? 0,
            'tooShort' => $term !== '' && mb_strlen($term) < 2,
        ]);

        return $this->htmlResponse();
    }

    /**
     * Einzelner Artikel samt Quellenapparat und Rückverweisen.
     */
    #[Extbase\IgnoreValidation(['value' => 'article'])]
    public function showAction(?Article $article = null): ResponseInterface
    {
        if ($article === null) {
            $this->pageNotFound('Diesen Wiki-Artikel gibt es nicht.');
        }

        if (!$article->isPublished() && !$this->isPreviewAllowed()) {
            $this->pageNotFound('Dieser Artikel ist noch nicht veröffentlicht.');
        }

        $this->view->assignMultiple([
            'article' => $article,
            'backlinks' => $this->articleRepository->findLinkingTo($article->getSlug()),
            'isPreview' => !$article->isPublished(),
        ]);

        return $this->htmlResponse();
    }

    /**
     * Unveröffentlichte Artikel sind nur für angemeldete Redakteure sichtbar.
     */
    private function isPreviewAllowed(): bool
    {
        $context = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Context\Context::class);

        return (bool)$context->getPropertyFromAspect('backend.user', 'isLoggedIn', false);
    }
}
