<?php

declare(strict_types=1);

namespace Marian\Shop\Domain\Checkout;

use Marian\Shop\Domain\Model\Address;

/**
 * Die Eingaben der Kasse, aus dem Request eingelesen und aufbereitet.
 *
 * Bewusst ein eigenes Objekt statt direkter Extbase-Formularbindung: die
 * Lieferanschrift ist nur pflicht, wenn sie von der Rechnungsanschrift
 * abweicht, und solche Wenn-dann-Regeln lassen sich hier klarer ausdrücken
 * als in Validator-Annotationen.
 */
final class CheckoutForm
{
    /**
     * @param array<string, string> $billing
     * @param array<string, string> $shipping
     */
    public function __construct(
        public readonly string $email = '',
        public readonly string $phone = '',
        public readonly array $billing = [],
        public readonly bool $shippingDiffers = false,
        public readonly array $shipping = [],
        public readonly int $shippingMethod = 0,
        public readonly int $paymentMethod = 0,
        public readonly string $note = '',
        public readonly bool $acceptTerms = false,
        public readonly bool $acceptWithdrawal = false,
        public readonly bool $createAccount = false,
        public readonly string $password = '',
        public readonly int $savedAddress = 0,
    ) {}

    /**
     * Liest die Eingaben aus dem Plugin-Namensraum des Requests.
     *
     * @param array<string, mixed> $parameters
     */
    public static function fromParameters(array $parameters): self
    {
        $strings = static function (mixed $value): array {
            if (!is_array($value)) {
                return [];
            }

            $clean = [];
            foreach ($value as $key => $item) {
                if (is_string($key) && (is_string($item) || is_numeric($item))) {
                    $clean[$key] = trim((string)$item);
                }
            }

            return $clean;
        };

        return new self(
            email: trim((string)($parameters['email'] ?? '')),
            phone: trim((string)($parameters['phone'] ?? '')),
            billing: $strings($parameters['billing'] ?? []),
            shippingDiffers: (bool)($parameters['shippingDiffers'] ?? false),
            shipping: $strings($parameters['shipping'] ?? []),
            shippingMethod: (int)($parameters['shippingMethod'] ?? 0),
            paymentMethod: (int)($parameters['paymentMethod'] ?? 0),
            note: trim((string)($parameters['note'] ?? '')),
            acceptTerms: (bool)($parameters['acceptTerms'] ?? false),
            acceptWithdrawal: (bool)($parameters['acceptWithdrawal'] ?? false),
            createAccount: (bool)($parameters['createAccount'] ?? false),
            password: (string)($parameters['password'] ?? ''),
            savedAddress: (int)($parameters['savedAddress'] ?? 0),
        );
    }

    public function getBillingAddress(): Address
    {
        return $this->toAddress($this->billing, Address::KIND_BILLING);
    }

    public function getShippingAddress(): ?Address
    {
        if (!$this->shippingDiffers) {
            return null;
        }

        return $this->toAddress($this->shipping, Address::KIND_SHIPPING);
    }

    /**
     * @param array<string, string> $data
     */
    private function toAddress(array $data, string $kind): Address
    {
        $address = new Address();
        $address->setKind($kind);
        $address->setSalutation($data['salutation'] ?? '');
        $address->setFirstName($data['firstName'] ?? '');
        $address->setLastName($data['lastName'] ?? '');
        $address->setCompany($data['company'] ?? '');
        $address->setStreet($data['street'] ?? '');
        $address->setHouseNumber($data['houseNumber'] ?? '');
        $address->setZip($data['zip'] ?? '');
        $address->setCity($data['city'] ?? '');
        $address->setCountry($data['country'] ?? 'DE');

        return $address;
    }
}
