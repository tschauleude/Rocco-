<?php

declare(strict_types=1);

namespace Marian\Shop\Payment;

/**
 * Sammelt alle Zahlungsarten ein. Die Liste kommt über den Service-Container,
 * siehe Configuration/Services.yaml.
 */
class PaymentProviderRegistry
{
    /**
     * @var array<string, PaymentProviderInterface>
     */
    private array $providers = [];

    /**
     * @param iterable<PaymentProviderInterface> $providers
     */
    public function __construct(iterable $providers = [])
    {
        foreach ($providers as $provider) {
            $this->providers[$provider->getIdentifier()] = $provider;
        }
    }

    public function get(string $identifier): ?PaymentProviderInterface
    {
        return $this->providers[$identifier] ?? null;
    }

    public function has(string $identifier): bool
    {
        return isset($this->providers[$identifier]);
    }

    /**
     * @return array<string, PaymentProviderInterface>
     */
    public function getAvailable(): array
    {
        return array_filter(
            $this->providers,
            static fn (PaymentProviderInterface $provider): bool => $provider->isAvailable()
        );
    }

    /**
     * @return array<string, PaymentProviderInterface>
     */
    public function all(): array
    {
        return $this->providers;
    }
}
