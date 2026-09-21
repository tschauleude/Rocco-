<?php

declare(strict_types=1);

namespace Marian\Shop\Domain\Repository;

use Marian\Shop\Domain\Model\Customer;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<Customer>
 */
class CustomerRepository extends Repository
{
    public function findOneByUsername(string $username): ?Customer
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->getQuerySettings()->setIgnoreEnableFields(true);
        $query->matching($query->equals('username', $username));
        $query->setLimit(1);

        return $query->execute()->getFirst();
    }

    /**
     * Prüft, ob eine E-Mail-Adresse bereits vergeben ist. Deaktivierte Konten
     * zählen mit: sonst könnte man eine fremde, noch nicht bestätigte
     * Registrierung überschreiben.
     */
    public function findOneByEmailIncludingDisabled(string $email): ?Customer
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->getQuerySettings()->setIgnoreEnableFields(true);
        $query->matching($query->equals('email', $email));
        $query->setLimit(1);

        return $query->execute()->getFirst();
    }

    /**
     * Findet ein Konto über den Bestätigungslink. Das Konto ist zu diesem
     * Zeitpunkt noch deaktiviert, deshalb ohne enable fields.
     */
    public function findOneByConfirmationToken(string $token): ?Customer
    {
        if (strlen($token) < 32) {
            return null;
        }

        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->getQuerySettings()->setIgnoreEnableFields(true);
        $query->matching($query->equals('confirmationToken', $token));
        $query->setLimit(1);

        return $query->execute()->getFirst();
    }

    /**
     * Wie findByUid, aber ohne Rücksicht auf den Speicherordner – Kunden
     * liegen in einem eigenen Ordner, die Sitzung kennt nur die uid.
     */
    public function findCustomerByUid(int $uid): ?Customer
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->matching($query->equals('uid', $uid));
        $query->setLimit(1);

        return $query->execute()->getFirst();
    }
}
