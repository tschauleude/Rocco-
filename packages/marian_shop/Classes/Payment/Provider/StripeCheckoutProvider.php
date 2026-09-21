<?php

declare(strict_types=1);

namespace Marian\Shop\Payment\Provider;

use Marian\Shop\Domain\Model\Order;
use Marian\Shop\Exception\PaymentException;
use Marian\Shop\Payment\PaymentContext;
use Marian\Shop\Payment\PaymentProviderInterface;
use Marian\Shop\Payment\PaymentResult;
use Marian\Shop\Payment\StripeClient;
use Marian\Shop\Service\ShopSettings;

/**
 * Zahlung über Stripe Checkout: Der Kunde wird auf eine von Stripe gehostete
 * Seite geleitet und kommt nach der Zahlung zurück.
 *
 * Dass die Zahlung geklappt hat, erfährt der Shop vom Webhook, nicht von der
 * Rückkehr des Kunden – die Rückkehr-URL kann jeder aufrufen, den Webhook
 * nur Stripe mit gültiger Signatur.
 */
class StripeCheckoutProvider implements PaymentProviderInterface
{
    public function __construct(
        private readonly StripeClient $client,
        private readonly ShopSettings $settings,
    ) {}

    public function getIdentifier(): string
    {
        return 'stripe';
    }

    public function getLabel(): string
    {
        return 'Kreditkarte, Apple Pay, Google Pay (Stripe)';
    }

    public function isAvailable(): bool
    {
        return $this->settings->isStripeConfigured();
    }

    public function start(Order $order, PaymentContext $context): PaymentResult
    {
        if (!$this->isAvailable()) {
            return PaymentResult::failed('Diese Zahlungsart ist derzeit nicht verfügbar.');
        }

        $params = [
            'mode' => 'payment',
            'success_url' => $context->successUrl,
            'cancel_url' => $context->cancelUrl,
            'client_reference_id' => $order->getOrderNumber(),
            'locale' => $context->locale,
            'line_items' => $this->buildLineItems($order, $context->currency),
            'metadata' => [
                'order_number' => $order->getOrderNumber(),
                'order_uid' => (string)($order->getUid() ?? 0),
            ],
        ];

        if ($order->getEmail() !== '') {
            $params['customer_email'] = $order->getEmail();
        }

        try {
            $session = $this->client->post(
                'checkout/sessions',
                $params,
                $this->settings->getStripeSecretKey(),
                // Zweimal auf "Bestellen" geklickt soll keine zweite Zahlung anlegen.
                'order-' . $order->getOrderNumber()
            );
        } catch (PaymentException $exception) {
            return PaymentResult::failed($exception->getMessage());
        }

        $url = (string)($session['url'] ?? '');
        $id = (string)($session['id'] ?? '');

        if ($url === '' || $id === '') {
            return PaymentResult::failed('Der Zahlungsdienst hat keine Zahlungsseite geliefert.');
        }

        return PaymentResult::redirect($url, $id);
    }

    /**
     * Baut die Positionen für Stripe. Versand und Zahlungsaufschlag werden als
     * eigene Positionen mitgeschickt, damit die Summe bei Stripe exakt der
     * Bestellsumme entspricht.
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildLineItems(Order $order, string $currency): array
    {
        $currency = strtolower($currency);
        $lineItems = [];

        foreach ($order->getItems() as $item) {
            $lineItems[] = [
                'quantity' => $item->getQuantity(),
                'price_data' => [
                    'currency' => $currency,
                    'unit_amount' => $item->getUnitGross(),
                    'product_data' => [
                        'name' => $item->getFullTitle(),
                    ],
                ],
            ];
        }

        if ($order->getShippingGross() > 0) {
            $lineItems[] = [
                'quantity' => 1,
                'price_data' => [
                    'currency' => $currency,
                    'unit_amount' => $order->getShippingGross(),
                    'product_data' => [
                        'name' => 'Versand: ' . $order->getShippingTitle(),
                    ],
                ],
            ];
        }

        if ($order->getPaymentSurchargeGross() > 0) {
            $lineItems[] = [
                'quantity' => 1,
                'price_data' => [
                    'currency' => $currency,
                    'unit_amount' => $order->getPaymentSurchargeGross(),
                    'product_data' => [
                        'name' => 'Zahlungsaufschlag: ' . $order->getPaymentTitle(),
                    ],
                ],
            ];
        }

        return $lineItems;
    }
}
