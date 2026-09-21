<?php

declare(strict_types=1);

namespace Marian\Shop\Service;

use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

/**
 * Zugriff auf die Shop-Einstellungen.
 *
 * Zugangsdaten werden zuerst in der Umgebung gesucht und erst danach in der
 * Extension-Konfiguration. So kann ein Schlüssel im Server gesetzt werden,
 * ohne je in Datenbank oder Repository zu landen.
 */
class ShopSettings
{
    /**
     * @var array<string, mixed>|null
     */
    private ?array $configuration = null;

    public function __construct(private readonly ExtensionConfiguration $extensionConfiguration) {}

    public function getStripeSecretKey(): string
    {
        return $this->value('STRIPE_SECRET_KEY', 'stripeSecretKey');
    }

    public function getStripeWebhookSecret(): string
    {
        return $this->value('STRIPE_WEBHOOK_SECRET', 'stripeWebhookSecret');
    }

    public function getShopName(): string
    {
        $name = $this->value('SHOP_NAME', 'shopName');

        return $name !== '' ? $name : 'Shop';
    }

    /**
     * Adresse, an die der Bestelleingang gemeldet wird. Ohne eigene Angabe
     * die allgemeine Absenderadresse der Installation.
     */
    public function getNotificationEmail(): string
    {
        $email = $this->value('SHOP_NOTIFICATION_EMAIL', 'notificationEmail');
        if ($email !== '') {
            return $email;
        }

        return (string)($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'] ?? '');
    }

    public function getSenderEmail(): string
    {
        $configured = (string)($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'] ?? '');

        return $configured !== '' ? $configured : $this->getNotificationEmail();
    }

    public function isStripeConfigured(): bool
    {
        return $this->getStripeSecretKey() !== '';
    }

    private function value(string $envName, string $configKey): string
    {
        $fromEnv = getenv($envName);
        if (is_string($fromEnv) && trim($fromEnv) !== '') {
            return trim($fromEnv);
        }

        return trim((string)($this->getConfiguration()[$configKey] ?? ''));
    }

    /**
     * @return array<string, mixed>
     */
    private function getConfiguration(): array
    {
        if ($this->configuration !== null) {
            return $this->configuration;
        }

        try {
            $configuration = $this->extensionConfiguration->get('marian_shop');
            $this->configuration = is_array($configuration) ? $configuration : [];
        } catch (ExtensionConfigurationExtensionNotConfiguredException | ExtensionConfigurationPathDoesNotExistException) {
            // Extension noch nie konfiguriert: alles leer, Stripe gilt als aus.
            $this->configuration = [];
        }

        return $this->configuration;
    }
}
