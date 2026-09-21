<?php

declare(strict_types=1);

namespace Marian\Shop\Service;

use Marian\Shop\Domain\Model\Address;
use Marian\Shop\Domain\Model\Customer;
use Marian\Shop\Domain\Repository\CustomerRepository;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

/**
 * Anlegen und Bestätigen von Kundenkonten.
 *
 * Passwörter werden mit dem Hash-Verfahren der Installation gehasht – nie
 * selbst gebaut. Neue Konten sind bis zur Bestätigung per E-Mail deaktiviert,
 * damit niemand ein Konto auf eine fremde Adresse anlegen kann.
 */
class CustomerService
{
    private const TOKEN_LIFETIME = 172800; // 48 Stunden

    public function __construct(
        private readonly CustomerRepository $customerRepository,
        private readonly PersistenceManagerInterface $persistenceManager,
        private readonly PasswordHashFactory $passwordHashFactory,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Legt ein noch nicht freigeschaltetes Konto an und gibt es samt Token zurück.
     */
    public function register(
        string $email,
        string $plainPassword,
        int $storagePid,
        int $userGroupUid,
        string $firstName = '',
        string $lastName = '',
        string $phone = '',
    ): Customer {
        $customer = new Customer();
        $customer->setPid($storagePid);
        $customer->setUsername($email);
        $customer->setEmail($email);
        $customer->setPassword($this->hash($plainPassword));
        $customer->setFirstName($firstName);
        $customer->setLastName($lastName);
        $customer->setName(trim($firstName . ' ' . $lastName));
        $customer->setPhone($phone);
        $customer->setDisable(true);
        $customer->setConfirmationToken(bin2hex(random_bytes(24)));
        $customer->setConfirmationTokenExpires(time() + self::TOKEN_LIFETIME);

        // fe_users führt Gruppen als Kommaliste von uids.
        if ($userGroupUid > 0) {
            $customer->setUsergroup((string)$userGroupUid);
        }

        $this->customerRepository->add($customer);
        $this->persistenceManager->persistAll();

        $this->logger->info('Kundenkonto angelegt', ['email' => $email]);

        return $customer;
    }

    /**
     * Schaltet ein Konto frei. Gibt null zurück, wenn der Link ungültig oder
     * abgelaufen ist.
     */
    public function confirm(string $token): ?Customer
    {
        $customer = $this->customerRepository->findOneByConfirmationToken($token);
        if ($customer === null || !$customer->isConfirmationTokenValid($token)) {
            return null;
        }

        $customer->setDisable(false);
        $customer->setConfirmationToken('');
        $customer->setConfirmationTokenExpires(0);

        $this->customerRepository->update($customer);
        $this->persistenceManager->persistAll();

        $this->logger->info('Kundenkonto bestätigt', ['uid' => $customer->getUid()]);

        return $customer;
    }

    /**
     * Übernimmt eine Anschrift aus der Kasse ins Adressbuch des Kunden.
     */
    public function addAddress(Customer $customer, Address $address, int $storagePid, bool $asDefault = true): Address
    {
        $stored = $address->copyForOrder($address->getKind());
        $stored->setPid($storagePid);
        $stored->setFeUser($customer->getUid() ?? 0);
        $stored->setIsArchived(false);
        $stored->setIsDefault($asDefault);

        if ($asDefault) {
            foreach ($customer->getActiveAddresses() as $existing) {
                $existing->setIsDefault(false);
            }
        }

        $customer->addAddress($stored);
        $this->customerRepository->update($customer);
        $this->persistenceManager->persistAll();

        return $stored;
    }

    public function changePassword(Customer $customer, string $plainPassword): void
    {
        $customer->setPassword($this->hash($plainPassword));
        $this->customerRepository->update($customer);
        $this->persistenceManager->persistAll();

        $this->logger->info('Passwort geändert', ['uid' => $customer->getUid()]);
    }

    public function updateProfile(Customer $customer, string $firstName, string $lastName, string $phone): void
    {
        $customer->setFirstName($firstName);
        $customer->setLastName($lastName);
        $customer->setName(trim($firstName . ' ' . $lastName));
        $customer->setPhone($phone);

        $this->customerRepository->update($customer);
        $this->persistenceManager->persistAll();
    }

    private function hash(string $plainPassword): string
    {
        return $this->passwordHashFactory
            ->getDefaultHashInstance('FE')
            ->getHashedPassword($plainPassword);
    }
}
