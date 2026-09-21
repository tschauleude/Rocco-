<?php

declare(strict_types=1);

namespace Marian\Shop\Domain\Model;

use TYPO3\CMS\Extbase\Annotation as Extbase;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Eine Liefer- oder Rechnungsanschrift.
 */
class Address extends AbstractEntity
{
    public const KIND_BILLING = 'billing';
    public const KIND_SHIPPING = 'shipping';

    public const SALUTATIONS = [
        '' => 'keine Angabe',
        'mrs' => 'Frau',
        'mr' => 'Herr',
        'divers' => 'Divers',
    ];

    public const COUNTRIES = [
        'DE' => 'Deutschland',
        'AT' => 'Österreich',
        'CH' => 'Schweiz',
    ];

    protected int $feUser = 0;

    protected string $kind = self::KIND_BILLING;

    protected string $salutation = '';

    #[Extbase\Validate(['validator' => 'NotEmpty'])]
    #[Extbase\Validate(['validator' => 'StringLength', 'options' => ['maximum' => 120]])]
    protected string $firstName = '';

    #[Extbase\Validate(['validator' => 'NotEmpty'])]
    #[Extbase\Validate(['validator' => 'StringLength', 'options' => ['maximum' => 120]])]
    protected string $lastName = '';

    #[Extbase\Validate(['validator' => 'StringLength', 'options' => ['maximum' => 180]])]
    protected string $company = '';

    #[Extbase\Validate(['validator' => 'NotEmpty'])]
    #[Extbase\Validate(['validator' => 'StringLength', 'options' => ['maximum' => 180]])]
    protected string $street = '';

    #[Extbase\Validate(['validator' => 'NotEmpty'])]
    #[Extbase\Validate(['validator' => 'StringLength', 'options' => ['maximum' => 30]])]
    protected string $houseNumber = '';

    #[Extbase\Validate(['validator' => 'NotEmpty'])]
    #[Extbase\Validate(['validator' => 'StringLength', 'options' => ['minimum' => 4, 'maximum' => 20]])]
    protected string $zip = '';

    #[Extbase\Validate(['validator' => 'NotEmpty'])]
    #[Extbase\Validate(['validator' => 'StringLength', 'options' => ['maximum' => 120]])]
    protected string $city = '';

    protected string $country = 'DE';

    protected bool $isDefault = false;

    /**
     * Archivierte Adressen bleiben an alten Bestellungen hängen, tauchen aber
     * im Adressbuch des Kunden nicht mehr auf.
     */
    protected bool $isArchived = false;

    public function getFeUser(): int
    {
        return $this->feUser;
    }

    public function setFeUser(int $feUser): void
    {
        $this->feUser = $feUser;
    }

    public function getKind(): string
    {
        return $this->kind;
    }

    public function setKind(string $kind): void
    {
        $this->kind = $kind;
    }

    public function getSalutation(): string
    {
        return $this->salutation;
    }

    public function setSalutation(string $salutation): void
    {
        $this->salutation = isset(self::SALUTATIONS[$salutation]) ? $salutation : '';
    }

    /**
     * Ohne gewählte Anrede bleibt die Zeile leer – "keine Angabe" ist ein
     * Text für das Auswahlfeld, nicht für den Adressaufdruck.
     */
    public function getSalutationLabel(): string
    {
        if ($this->salutation === '') {
            return '';
        }

        return self::SALUTATIONS[$this->salutation] ?? '';
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): void
    {
        $this->firstName = trim($firstName);
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): void
    {
        $this->lastName = trim($lastName);
    }

    public function getCompany(): string
    {
        return $this->company;
    }

    public function setCompany(string $company): void
    {
        $this->company = trim($company);
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function setStreet(string $street): void
    {
        $this->street = trim($street);
    }

    public function getHouseNumber(): string
    {
        return $this->houseNumber;
    }

    public function setHouseNumber(string $houseNumber): void
    {
        $this->houseNumber = trim($houseNumber);
    }

    public function getZip(): string
    {
        return $this->zip;
    }

    public function setZip(string $zip): void
    {
        $this->zip = trim($zip);
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCity(string $city): void
    {
        $this->city = trim($city);
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function setCountry(string $country): void
    {
        $country = strtoupper(trim($country));
        $this->country = isset(self::COUNTRIES[$country]) ? $country : 'DE';
    }

    public function getCountryLabel(): string
    {
        return self::COUNTRIES[$this->country] ?? $this->country;
    }

    public function getIsDefault(): bool
    {
        return $this->isDefault;
    }

    public function setIsDefault(bool $isDefault): void
    {
        $this->isDefault = $isDefault;
    }

    public function getIsArchived(): bool
    {
        return $this->isArchived;
    }

    public function setIsArchived(bool $isArchived): void
    {
        $this->isArchived = $isArchived;
    }

    public function getFullName(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }

    /**
     * Einzeilige Darstellung für Listen und Auswahlfelder.
     */
    public function getOneLine(): string
    {
        return sprintf(
            '%s, %s %s, %s %s',
            $this->getFullName(),
            $this->street,
            $this->houseNumber,
            $this->zip,
            $this->city
        );
    }

    /**
     * Erzeugt eine unabhängige Kopie – so friert eine Bestellung die Anschrift
     * zum Bestellzeitpunkt ein.
     */
    public function copyForOrder(string $kind): self
    {
        $copy = new self();
        $copy->setKind($kind);
        $copy->setSalutation($this->salutation);
        $copy->setFirstName($this->firstName);
        $copy->setLastName($this->lastName);
        $copy->setCompany($this->company);
        $copy->setStreet($this->street);
        $copy->setHouseNumber($this->houseNumber);
        $copy->setZip($this->zip);
        $copy->setCity($this->city);
        $copy->setCountry($this->country);
        $copy->setFeUser(0);
        $copy->setIsArchived(true);

        return $copy;
    }
}
