<?php

declare(strict_types=1);

namespace Marian\Shop\Controller;

use Marian\Shop\Domain\Model\Address;
use Marian\Shop\Domain\Model\Order;
use Marian\Shop\Domain\Repository\AddressRepository;
use Marian\Shop\Domain\Repository\OrderRepository;
use Marian\Shop\Domain\Repository\PaymentMethodRepository;
use Marian\Shop\Service\CustomerService;
use Marian\Shop\Service\OrderMailService;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Security\RequestToken;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Extbase\Annotation as Extbase;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

/**
 * Das Kundenkonto: Übersicht, Bestellungen, Adressbuch, Profil, Registrierung.
 *
 * Die Anmeldung selbst übernimmt TYPO3: Das Formular schickt logintype, user
 * und pass an eine beliebige Seite, die Authentifizierung passiert in der
 * Middleware des Kerns. Selbstgebaute Anmeldelogik wäre hier genau die
 * Stelle, an der man sich eine Lücke einhandelt.
 */
class AccountController extends AbstractShopController
{
    private const MIN_PASSWORD_LENGTH = 8;

    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly AddressRepository $addressRepository,
        private readonly PaymentMethodRepository $paymentMethodRepository,
        private readonly CustomerService $customerService,
        private readonly OrderMailService $mailService,
        private readonly PersistenceManagerInterface $persistenceManager,
    ) {}

    public function overviewAction(): ResponseInterface
    {
        if (!$this->isLoggedIn()) {
            return new ForwardResponse('login');
        }

        $customer = $this->getCurrentCustomer();
        $orders = $this->orderRepository->findByCustomer($this->getCurrentUserUid());

        $this->view->assignMultiple([
            'customer' => $customer,
            'orders' => $orders,
            'orderCount' => $orders->count(),
            'recentOrders' => array_slice($orders->toArray(), 0, 3),
            'addresses' => $customer?->getActiveAddresses() ?? [],
        ]);

        return $this->htmlResponse();
    }

    public function ordersAction(): ResponseInterface
    {
        if (!$this->isLoggedIn()) {
            return new ForwardResponse('login');
        }

        $this->view->assign('orders', $this->orderRepository->findByCustomer($this->getCurrentUserUid()));

        return $this->htmlResponse();
    }

    #[Extbase\IgnoreValidation(['value' => 'order'])]
    public function orderAction(?Order $order = null): ResponseInterface
    {
        if (!$this->isLoggedIn()) {
            return new ForwardResponse('login');
        }

        // Fremde Bestellungen gehören niemandem außer ihrem Besteller.
        if ($order === null || $order->getFeUser() !== $this->getCurrentUserUid()) {
            $this->pageNotFound('Diese Bestellung gibt es nicht.');
        }

        $this->view->assignMultiple([
            'order' => $order,
            'payment' => $this->paymentMethodRepository->findByUid($order->getPaymentMethod()),
        ]);

        return $this->htmlResponse();
    }

    public function addressesAction(): ResponseInterface
    {
        if (!$this->isLoggedIn()) {
            return new ForwardResponse('login');
        }

        $this->view->assignMultiple([
            'addresses' => $this->addressRepository->findByCustomer($this->getCurrentUserUid()),
            'salutations' => Address::SALUTATIONS,
            'countries' => Address::COUNTRIES,
        ]);

        return $this->htmlResponse();
    }

    /**
     * Legt eine Adresse an oder ändert eine vorhandene.
     */
    public function saveAddressAction(): ResponseInterface
    {
        $customer = $this->getCurrentCustomer();
        if ($customer === null) {
            return new ForwardResponse('login');
        }

        $arguments = $this->request->getArguments();
        $data = is_array($arguments['address'] ?? null) ? $arguments['address'] : [];
        $uid = (int)($arguments['uid'] ?? 0);

        $address = $uid > 0
            ? $this->addressRepository->findOwnedBy($uid, $customer->getUid() ?? 0)
            : null;

        $isNew = $address === null;
        if ($isNew) {
            $address = new Address();
            $address->setPid($this->getStoragePid());
            $address->setFeUser($customer->getUid() ?? 0);
        }

        $address->setSalutation((string)($data['salutation'] ?? ''));
        $address->setFirstName((string)($data['firstName'] ?? ''));
        $address->setLastName((string)($data['lastName'] ?? ''));
        $address->setCompany((string)($data['company'] ?? ''));
        $address->setStreet((string)($data['street'] ?? ''));
        $address->setHouseNumber((string)($data['houseNumber'] ?? ''));
        $address->setZip((string)($data['zip'] ?? ''));
        $address->setCity((string)($data['city'] ?? ''));
        $address->setCountry((string)($data['country'] ?? 'DE'));

        $missing = $this->missingAddressFields($address);
        if ($missing !== []) {
            $this->addFlashMessage(
                'Bitte fülle noch aus: ' . implode(', ', $missing),
                '',
                ContextualFeedbackSeverity::ERROR
            );

            return $this->redirect('addresses');
        }

        if ((bool)($data['isDefault'] ?? false)) {
            foreach ($this->addressRepository->findByCustomer($customer->getUid() ?? 0) as $other) {
                $other->setIsDefault(false);
                $this->addressRepository->update($other);
            }
            $address->setIsDefault(true);
        }

        $isNew ? $this->addressRepository->add($address) : $this->addressRepository->update($address);
        $this->persistenceManager->persistAll();

        $this->addFlashMessage($isNew ? 'Adresse gespeichert.' : 'Adresse geändert.');

        return $this->redirect('addresses');
    }

    public function deleteAddressAction(int $uid): ResponseInterface
    {
        $customer = $this->getCurrentCustomer();
        if ($customer === null) {
            return new ForwardResponse('login');
        }

        $address = $this->addressRepository->findOwnedBy($uid, $customer->getUid() ?? 0);
        if ($address !== null) {
            // Nicht löschen, sondern archivieren: alte Bestellungen sollen
            // ihre Anschrift behalten.
            $address->setIsArchived(true);
            $address->setIsDefault(false);
            $this->addressRepository->update($address);
            $this->persistenceManager->persistAll();
            $this->addFlashMessage('Adresse entfernt.');
        }

        return $this->redirect('addresses');
    }

    public function profileAction(): ResponseInterface
    {
        if (!$this->isLoggedIn()) {
            return new ForwardResponse('login');
        }

        $this->view->assign('customer', $this->getCurrentCustomer());

        return $this->htmlResponse();
    }

    public function saveProfileAction(): ResponseInterface
    {
        $customer = $this->getCurrentCustomer();
        if ($customer === null) {
            return new ForwardResponse('login');
        }

        $arguments = $this->request->getArguments();

        $this->customerService->updateProfile(
            $customer,
            trim((string)($arguments['firstName'] ?? '')),
            trim((string)($arguments['lastName'] ?? '')),
            trim((string)($arguments['phone'] ?? '')),
        );

        $newPassword = (string)($arguments['password'] ?? '');
        if ($newPassword !== '') {
            if (strlen($newPassword) < self::MIN_PASSWORD_LENGTH) {
                $this->addFlashMessage(
                    sprintf('Das Passwort braucht mindestens %d Zeichen. Die übrigen Angaben wurden gespeichert.', self::MIN_PASSWORD_LENGTH),
                    '',
                    ContextualFeedbackSeverity::ERROR
                );

                return $this->redirect('profile');
            }

            if ($newPassword !== (string)($arguments['passwordRepeat'] ?? '')) {
                $this->addFlashMessage(
                    'Die beiden Passwörter stimmen nicht überein. Die übrigen Angaben wurden gespeichert.',
                    '',
                    ContextualFeedbackSeverity::ERROR
                );

                return $this->redirect('profile');
            }

            $this->customerService->changePassword($customer, $newPassword);
            $this->addFlashMessage('Profil und Passwort gespeichert.');

            return $this->redirect('profile');
        }

        $this->addFlashMessage('Profil gespeichert.');

        return $this->redirect('profile');
    }

    /**
     * Anmeldeformular.
     *
     * Die Zugangsdaten prüft TYPO3 selbst. Der Ordner, in dem nach dem Konto
     * gesucht wird, reist in einem signierten Request-Token mit – als reines
     * Formularfeld ließe er sich manipulieren, und TYPO3 ignoriert ihn dann
     * auch bewusst.
     */
    public function loginAction(): ResponseInterface
    {
        if ($this->isLoggedIn()) {
            return new ForwardResponse('overview');
        }

        $storagePid = $this->getStoragePid();

        $this->view->assignMultiple([
            'failed' => ($this->request->getQueryParams()['logintype'] ?? '') === 'login'
                || ($this->request->getParsedBody()['logintype'] ?? '') === 'login',
            'registerPid' => $this->intSetting('registerPid'),
            'requestToken' => RequestToken::create('core/user-auth/fe')
                ->withMergedParams(['pid' => (string)$storagePid]),
        ]);

        return $this->htmlResponse();
    }

    /**
     * @param array<string, string> $errors
     * @param array<string, mixed> $values
     */
    public function registerAction(array $errors = [], array $values = []): ResponseInterface
    {
        if ($this->isLoggedIn()) {
            return new ForwardResponse('overview');
        }

        $this->view->assignMultiple([
            'errors' => $errors,
            'values' => $values,
            'privacyPid' => $this->intSetting('privacyPid'),
        ]);

        return $this->htmlResponse();
    }

    public function createAccountAction(): ResponseInterface
    {
        $arguments = $this->request->getArguments();
        $email = trim((string)($arguments['email'] ?? ''));
        $password = (string)($arguments['password'] ?? '');
        $repeat = (string)($arguments['passwordRepeat'] ?? '');

        $errors = [];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Diese E-Mail-Adresse sieht nicht richtig aus.';
        } elseif ($this->customerRepository?->findOneByEmailIncludingDisabled($email) !== null) {
            $errors['email'] = 'Zu dieser Adresse gibt es bereits ein Konto.';
        }

        if (strlen($password) < self::MIN_PASSWORD_LENGTH) {
            $errors['password'] = sprintf('Das Passwort braucht mindestens %d Zeichen.', self::MIN_PASSWORD_LENGTH);
        } elseif ($password !== $repeat) {
            $errors['passwordRepeat'] = 'Die beiden Passwörter stimmen nicht überein.';
        }

        if (!(bool)($arguments['acceptPrivacy'] ?? false)) {
            $errors['acceptPrivacy'] = 'Bitte bestätige die Datenschutzerklärung.';
        }

        if ($errors !== []) {
            return (new ForwardResponse('register'))
                ->withArguments(['errors' => $errors, 'values' => $arguments]);
        }

        $customer = $this->customerService->register(
            $email,
            $password,
            $this->getStoragePid(),
            $this->intSetting('customerGroup'),
            trim((string)($arguments['firstName'] ?? '')),
            trim((string)($arguments['lastName'] ?? '')),
            trim((string)($arguments['phone'] ?? '')),
        );

        $this->mailService->sendRegistrationConfirmation(
            $customer,
            $this->uriBuilder->reset()->setCreateAbsoluteUri(true)
                ->uriFor('confirm', ['token' => $customer->getConfirmationToken()])
        );

        return $this->redirect('registered');
    }

    public function registeredAction(): ResponseInterface
    {
        return $this->htmlResponse();
    }

    /**
     * Bestätigungslink aus der Registrierungsmail.
     */
    public function confirmAction(string $token = ''): ResponseInterface
    {
        $customer = $token !== '' ? $this->customerService->confirm($token) : null;

        $this->view->assignMultiple([
            'confirmed' => $customer !== null,
            'customer' => $customer,
            'loginPid' => $this->intSetting('loginPid'),
        ]);

        return $this->htmlResponse();
    }

    /**
     * @return string[]
     */
    private function missingAddressFields(Address $address): array
    {
        $missing = [];
        $required = [
            'Vorname' => $address->getFirstName(),
            'Nachname' => $address->getLastName(),
            'Straße' => $address->getStreet(),
            'Hausnummer' => $address->getHouseNumber(),
            'PLZ' => $address->getZip(),
            'Ort' => $address->getCity(),
        ];

        foreach ($required as $label => $value) {
            if ($value === '') {
                $missing[] = $label;
            }
        }

        return $missing;
    }
}
