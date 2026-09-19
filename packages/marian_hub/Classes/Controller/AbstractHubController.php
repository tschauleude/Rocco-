<?php

declare(strict_types=1);

namespace Marian\Hub\Controller;

use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Frontend\Controller\ErrorController;

/**
 * Gemeinsame Basis der Frontend-Controller dieser Extension.
 */
abstract class AbstractHubController extends ActionController
{
    /**
     * Beendet die Anfrage mit einer regulären 404-Seite statt mit einer Exception-Seite.
     */
    protected function pageNotFound(string $message): never
    {
        $response = GeneralUtility::makeInstance(ErrorController::class)
            ->pageNotFoundAction($this->request, $message);

        throw new PropagateResponseException($response, 1_726_000_001);
    }

    /**
     * Liest eine Zahl aus den Plugin-Einstellungen.
     */
    protected function intSetting(string $key, int $default = 0): int
    {
        $value = $this->settings[$key] ?? null;

        return is_numeric($value) ? (int)$value : $default;
    }
}
