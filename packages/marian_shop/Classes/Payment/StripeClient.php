<?php

declare(strict_types=1);

namespace Marian\Shop\Payment;

use Marian\Shop\Exception\PaymentException;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Http\RequestFactory;

/**
 * Schmaler Zugang zur Stripe-API.
 *
 * Bewusst ohne das offizielle SDK: gebraucht werden zwei Endpunkte und eine
 * Signaturprüfung. Dafür lohnt keine weitere Abhängigkeit, die mitgepflegt
 * und mitaktualisiert werden müsste.
 */
class StripeClient
{
    private const API_BASE = 'https://api.stripe.com/v1/';

    /**
     * Stripe-Version festnageln: so ändert ein Update auf deren Seite nicht
     * unangekündigt das Antwortformat.
     */
    private const API_VERSION = '2024-06-20';

    /**
     * Webhooks, die älter sind als fünf Minuten, werden abgelehnt –
     * das ist Stripes eigene Empfehlung gegen Replay-Angriffe.
     */
    private const SIGNATURE_TOLERANCE = 300;

    public function __construct(
        private readonly RequestFactory $requestFactory,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     * @throws PaymentException
     */
    public function post(string $path, array $params, string $secretKey, string $idempotencyKey = ''): array
    {
        if ($secretKey === '') {
            throw new PaymentException('Für Stripe ist kein Schlüssel hinterlegt.');
        }

        $headers = [
            'Authorization' => 'Bearer ' . $secretKey,
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Stripe-Version' => self::API_VERSION,
        ];

        // Verhindert, dass ein wiederholter Klick zwei Zahlungen anlegt.
        if ($idempotencyKey !== '') {
            $headers['Idempotency-Key'] = $idempotencyKey;
        }

        try {
            $response = $this->requestFactory->request(
                self::API_BASE . $path,
                'POST',
                [
                    'headers' => $headers,
                    'body' => http_build_query($params, '', '&', PHP_QUERY_RFC3986),
                    'timeout' => 20,
                ]
            );
        } catch (\Throwable $exception) {
            $this->logger->error('Stripe nicht erreichbar', ['exception' => $exception->getMessage()]);

            throw new PaymentException('Der Zahlungsdienst ist gerade nicht erreichbar.', 0, $exception);
        }

        $body = (string)$response->getBody();
        $data = json_decode($body, true);
        if (!is_array($data)) {
            throw new PaymentException('Unverständliche Antwort vom Zahlungsdienst.');
        }

        if ($response->getStatusCode() >= 400) {
            $message = (string)($data['error']['message'] ?? 'Unbekannter Fehler');
            $this->logger->error('Stripe meldet einen Fehler', [
                'status' => $response->getStatusCode(),
                'message' => $message,
            ]);

            throw new PaymentException('Die Zahlung konnte nicht gestartet werden: ' . $message);
        }

        return $data;
    }

    /**
     * Prüft die Signatur eines Webhooks.
     *
     * Stripe signiert "<zeitstempel>.<rohkörper>" mit HMAC-SHA256. Ohne diese
     * Prüfung könnte jeder eine Bestellung als bezahlt melden, indem er den
     * Endpunkt selbst aufruft.
     */
    public function verifyWebhookSignature(string $payload, string $signatureHeader, string $secret): bool
    {
        if ($secret === '' || $signatureHeader === '') {
            return false;
        }

        $timestamp = null;
        $signatures = [];

        foreach (explode(',', $signatureHeader) as $part) {
            $pair = explode('=', trim($part), 2);
            if (count($pair) !== 2) {
                continue;
            }
            if ($pair[0] === 't') {
                $timestamp = (int)$pair[1];
            } elseif ($pair[0] === 'v1') {
                $signatures[] = $pair[1];
            }
        }

        if ($timestamp === null || $signatures === []) {
            return false;
        }

        if (abs(time() - $timestamp) > self::SIGNATURE_TOLERANCE) {
            $this->logger->warning('Webhook-Signatur zu alt', ['timestamp' => $timestamp]);

            return false;
        }

        $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);

        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        $this->logger->warning('Webhook-Signatur stimmt nicht');

        return false;
    }
}
