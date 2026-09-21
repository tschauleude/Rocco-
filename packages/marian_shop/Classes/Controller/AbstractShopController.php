<?php

declare(strict_types=1);

namespace Marian\Shop\Controller;

use Marian\Shop\Domain\Model\Customer;
use Marian\Shop\Domain\Repository\CustomerRepository;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Frontend\Controller\ErrorController;

/**
 * Gemeinsame Basis der Shop-Controller.
 */
abstract class AbstractShopController extends ActionController
{
    protected ?CustomerRepository $customerRepository = null;

    public function injectCustomerRepository(CustomerRepository $customerRepository): void
    {
        $this->customerRepository = $customerRepository;
    }

    protected function pageNotFound(string $message): never
    {
        $response = GeneralUtility::makeInstance(ErrorController::class)
            ->pageNotFoundAction($this->request, $message);

        throw new PropagateResponseException($response, 1_726_100_001);
    }

    protected function intSetting(string $key, int $default = 0): int
    {
        $value = $this->settings[$key] ?? null;

        return is_numeric($value) ? (int)$value : $default;
    }

    /**
     * Der Ordner, in dem Bestellungen, Adressen und Konten abgelegt werden.
     */
    protected function getStoragePid(): int
    {
        $configured = $this->intSetting('storagePid');
        if ($configured > 0) {
            return $configured;
        }

        $frameworkStorage = $this->configurationManager
            ->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK)['persistence']['storagePid'] ?? '';

        return (int)explode(',', (string)$frameworkStorage)[0];
    }

    protected function getCurrentUserUid(): int
    {
        $context = GeneralUtility::makeInstance(Context::class);

        return (int)$context->getPropertyFromAspect('frontend.user', 'id', 0);
    }

    protected function isLoggedIn(): bool
    {
        return $this->getCurrentUserUid() > 0;
    }

    protected function getCurrentCustomer(): ?Customer
    {
        $uid = $this->getCurrentUserUid();
        if ($uid === 0 || $this->customerRepository === null) {
            return null;
        }

        return $this->customerRepository->findCustomerByUid($uid);
    }

    /**
     * Erzwingt eine Anmeldung: leitet sonst auf die konfigurierte Login-Seite.
     */
    protected function requireLogin(): ?\Psr\Http\Message\ResponseInterface
    {
        if ($this->isLoggedIn()) {
            return null;
        }

        $loginPid = $this->intSetting('loginPid');
        if ($loginPid > 0) {
            return $this->redirectToUri(
                $this->uriBuilder->reset()->setTargetPageUid($loginPid)->setCreateAbsoluteUri(true)->build()
            );
        }

        return $this->redirect('login');
    }
}
