<?php

declare(strict_types=1);

namespace Marian\Shop\Service;

use Marian\Shop\Domain\Cart\Cart;
use Marian\Shop\Domain\Checkout\CheckoutForm;
use Marian\Shop\Domain\Model\Address;
use Marian\Shop\Domain\Repository\CustomerRepository;

/**
 * Prüft die Eingaben der Kasse und liefert Fehlermeldungen je Feld.
 *
 * Die Feldnamen entsprechen den Formularnamen, damit das Template den Fehler
 * direkt neben dem Feld anzeigen kann.
 */
class CheckoutValidator
{
    private const MIN_PASSWORD_LENGTH = 8;

    public function __construct(private readonly CustomerRepository $customerRepository) {}

    /**
     * Feldschlüssel sind mit Unterstrich zusammengesetzt (billing_zip), nicht
     * mit Punkt: Fluid könnte einen Punkt sonst nicht als Variablennamen lesen.
     *
     * @return array<string, string> Feldname => Meldung
     */
    public function validate(CheckoutForm $form, Cart $cart, bool $isLoggedIn): array
    {
        $errors = [];

        if ($cart->isEmpty()) {
            $errors['cart'] = 'Der Warenkorb ist leer.';
        }

        foreach ($cart->getItemsExceedingStock() as $item) {
            $errors['cart'] = sprintf(
                'Von „%s" sind nur noch %d Stück verfügbar.',
                $item->getFullTitle(),
                $item->getAvailableStock()
            );
        }

        if ($form->email === '') {
            $errors['email'] = 'Bitte gib eine E-Mail-Adresse an – dorthin geht die Bestellbestätigung.';
        } elseif (!filter_var($form->email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Diese E-Mail-Adresse sieht nicht richtig aus.';
        }

        $errors += $this->validateAddress($form->getBillingAddress(), 'billing');

        if ($form->shippingDiffers) {
            $shipping = $form->getShippingAddress();
            if ($shipping !== null) {
                $errors += $this->validateAddress($shipping, 'shipping');
            }
        }

        if ($form->shippingMethod <= 0) {
            $errors['shippingMethod'] = 'Bitte wähle eine Versandart.';
        }

        if ($form->paymentMethod <= 0) {
            $errors['paymentMethod'] = 'Bitte wähle eine Zahlungsart.';
        }

        if (!$form->acceptTerms) {
            $errors['acceptTerms'] = 'Ohne Zustimmung zu den AGB können wir die Bestellung nicht annehmen.';
        }

        if (!$form->acceptWithdrawal) {
            $errors['acceptWithdrawal'] = 'Bitte bestätige, dass du die Widerrufsbelehrung gelesen hast.';
        }

        if ($form->createAccount && !$isLoggedIn) {
            if (strlen($form->password) < self::MIN_PASSWORD_LENGTH) {
                $errors['password'] = sprintf(
                    'Das Passwort braucht mindestens %d Zeichen.',
                    self::MIN_PASSWORD_LENGTH
                );
            }

            if ($form->email !== '' && $this->customerRepository->findOneByEmailIncludingDisabled($form->email) !== null) {
                $errors['createAccount'] = 'Zu dieser E-Mail-Adresse gibt es schon ein Konto. Melde dich an oder bestelle ohne Konto.';
            }
        }

        return $errors;
    }

    /**
     * @return array<string, string>
     */
    private function validateAddress(Address $address, string $prefix): array
    {
        $errors = [];

        $required = [
            'firstName' => ['Vorname', $address->getFirstName()],
            'lastName' => ['Nachname', $address->getLastName()],
            'street' => ['Straße', $address->getStreet()],
            'houseNumber' => ['Hausnummer', $address->getHouseNumber()],
            'zip' => ['Postleitzahl', $address->getZip()],
            'city' => ['Ort', $address->getCity()],
        ];

        foreach ($required as $field => [$label, $value]) {
            if ($value === '') {
                $errors[$prefix . '_' . $field] = $label . ' fehlt.';
            }
        }

        $zip = $address->getZip();
        if ($zip !== '' && $address->getCountry() === 'DE' && preg_match('/^\d{5}$/', $zip) !== 1) {
            $errors[$prefix . '_zip'] = 'Eine deutsche Postleitzahl hat fünf Ziffern.';
        }

        return $errors;
    }
}
