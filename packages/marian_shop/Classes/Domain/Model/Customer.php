<?php

declare(strict_types=1);

namespace Marian\Shop\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Ein Kundenkonto – abgebildet auf fe_users.
 *
 * Extbase brachte früher ein eigenes FrontendUser-Modell mit; seit TYPO3 v13
 * gibt es das nicht mehr, Extensions definieren ihr Modell selbst. Die
 * Zuordnung zur Tabelle steht in Configuration/Extbase/Persistence/Classes.php.
 *
 * Anmeldung, Passwortprüfung und Sitzung bleiben Sache des TYPO3-Kerns; dieses
 * Modell ist nur die Sicht der Extension auf den Datensatz.
 */
class Customer extends AbstractEntity
{
    protected string $username = '';

    protected string $password = '';

    protected string $email = '';

    protected string $name = '';

    protected string $firstName = '';

    protected string $lastName = '';

    /**
     * Kommaliste der Benutzergruppen, wie fe_users sie speichert.
     */
    protected string $usergroup = '';

    /**
     * Solange gesetzt, ist das Konto nicht freigeschaltet.
     */
    protected bool $disable = false;

    protected string $phone = '';

    /**
     * @var ObjectStorage<Address>
     */
    protected ObjectStorage $addresses;

    protected string $confirmationToken = '';

    protected int $confirmationTokenExpires = 0;

    public function __construct()
    {
        $this->addresses = new ObjectStorage();
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }

    public function getUsergroup(): string
    {
        return $this->usergroup;
    }

    public function setUsergroup(string $usergroup): void
    {
        $this->usergroup = $usergroup;
    }

    public function getDisable(): bool
    {
        return $this->disable;
    }

    public function isDisabled(): bool
    {
        return $this->disable;
    }

    public function setDisable(bool $disable): void
    {
        $this->disable = $disable;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): void
    {
        $this->phone = $phone;
    }

    /**
     * @return ObjectStorage<Address>
     */
    public function getAddresses(): ObjectStorage
    {
        return $this->addresses;
    }

    /**
     * @param ObjectStorage<Address> $addresses
     */
    public function setAddresses(ObjectStorage $addresses): void
    {
        $this->addresses = $addresses;
    }

    public function addAddress(Address $address): void
    {
        $this->addresses->attach($address);
    }

    public function removeAddress(Address $address): void
    {
        $this->addresses->detach($address);
    }

    /**
     * Sichtbare Adressen des Adressbuchs – ohne die an Bestellungen
     * eingefrorenen Kopien.
     *
     * @return array<int, Address>
     */
    public function getActiveAddresses(): array
    {
        $addresses = [];
        foreach ($this->addresses as $address) {
            if (!$address->getIsArchived()) {
                $addresses[] = $address;
            }
        }

        return $addresses;
    }

    public function getDefaultAddress(): ?Address
    {
        $active = $this->getActiveAddresses();
        foreach ($active as $address) {
            if ($address->getIsDefault()) {
                return $address;
            }
        }

        return $active[0] ?? null;
    }

    public function getConfirmationToken(): string
    {
        return $this->confirmationToken;
    }

    public function setConfirmationToken(string $confirmationToken): void
    {
        $this->confirmationToken = $confirmationToken;
    }

    public function getConfirmationTokenExpires(): int
    {
        return $this->confirmationTokenExpires;
    }

    public function setConfirmationTokenExpires(int $confirmationTokenExpires): void
    {
        $this->confirmationTokenExpires = $confirmationTokenExpires;
    }

    public function isConfirmationTokenValid(string $token): bool
    {
        return $this->confirmationToken !== ''
            && hash_equals($this->confirmationToken, $token)
            && $this->confirmationTokenExpires > time();
    }

    public function getDisplayName(): string
    {
        $name = trim($this->firstName . ' ' . $this->lastName);
        if ($name !== '') {
            return $name;
        }

        return $this->name !== '' ? $this->name : $this->username;
    }
}
