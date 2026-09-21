<?php

declare(strict_types=1);

namespace Marian\Shop\Domain\Model;

use Marian\Shop\Service\Money;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Eine Zahlungsart. "provider" entscheidet, welche Implementierung die
 * Zahlung abwickelt – siehe Marian\Shop\Payment\PaymentProviderRegistry.
 */
class PaymentMethod extends AbstractEntity
{
    protected string $title = '';

    protected string $description = '';

    protected string $provider = 'invoice';

    protected float $surcharge = 0.0;

    /**
     * Text, der nach der Bestellung angezeigt und in die Bestätigungsmail
     * übernommen wird – bei Vorkasse etwa die Bankverbindung.
     */
    protected string $instructions = '';

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function setProvider(string $provider): void
    {
        $this->provider = $provider;
    }

    public function getSurcharge(): float
    {
        return $this->surcharge;
    }

    public function setSurcharge(float $surcharge): void
    {
        $this->surcharge = $surcharge;
    }

    public function getSurchargeInCents(): int
    {
        return Money::toCents($this->surcharge);
    }

    public function getInstructions(): string
    {
        return $this->instructions;
    }

    public function setInstructions(string $instructions): void
    {
        $this->instructions = $instructions;
    }
}
