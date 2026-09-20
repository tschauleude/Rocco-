<?php

declare(strict_types=1);

namespace Marian\Hub\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
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
     * Leitet auf die im Plugin gewählte Ansicht um.
     *
     * Die Standardaktion eines Plugins ist immer dieselbe; welche Ansicht ein
     * Redakteur sehen will, steht im FlexForm. Alles außer den erlaubten Werten
     * wird ignoriert, damit die Einstellung keine fremden Aktionen aufrufen kann.
     *
     * @param string[] $allowed Aktionen, auf die umgeleitet werden darf
     */
    protected function forwardToSelectedView(array $allowed, string $current): ?ResponseInterface
    {
        $view = (string)($this->settings['view'] ?? '');
        if ($view === '' || $view === $current || !in_array($view, $allowed, true)) {
            return null;
        }

        return new ForwardResponse($view);
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
