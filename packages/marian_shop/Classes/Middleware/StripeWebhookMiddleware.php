<?php

declare(strict_types=1);

namespace Marian\Shop\Middleware;

use Marian\Shop\Payment\StripeClient;
use Marian\Shop\Payment\StripeWebhookHandler;
use Marian\Shop\Service\ShopSettings;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Http\JsonResponse;

/**
 * Nimmt Stripe-Ereignisse entgegen.
 *
 * POST /api/shop/stripe-webhook
 *
 * Die Wahrheit über eine Zahlung kommt von hier, nicht von der Rückkehr des
 * Kunden im Browser: diese Anfrage ist signiert, jene kann jeder aufrufen.
 */
class StripeWebhookMiddleware implements MiddlewareInterface
{
    private const PATH = '/api/shop/stripe-webhook';

    private const MAX_BODY_BYTES = 262144;

    public function __construct(
        private readonly StripeClient $client,
        private readonly StripeWebhookHandler $handler,
        private readonly ShopSettings $settings,
        private readonly LoggerInterface $logger,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (rtrim($request->getUri()->getPath(), '/') !== self::PATH) {
            return $handler->handle($request);
        }

        if ($request->getMethod() !== 'POST') {
            return (new JsonResponse(['status' => 'error', 'message' => 'Nur POST.'], 405))
                ->withHeader('Allow', 'POST');
        }

        $secret = $this->settings->getStripeWebhookSecret();
        if ($secret === '') {
            $this->logger->error('Webhook aufgerufen, aber kein Webhook-Secret hinterlegt');

            return new JsonResponse(['status' => 'error', 'message' => 'Nicht konfiguriert.'], 503);
        }

        $body = $request->getBody();
        $body->rewind();
        $payload = $body->read(self::MAX_BODY_BYTES + 1);

        if (strlen($payload) > self::MAX_BODY_BYTES) {
            return new JsonResponse(['status' => 'error', 'message' => 'Anfrage zu groß.'], 413);
        }

        if (!$this->client->verifyWebhookSignature($payload, $request->getHeaderLine('Stripe-Signature'), $secret)) {
            return new JsonResponse(['status' => 'error', 'message' => 'Signatur ungültig.'], 400);
        }

        try {
            $event = json_decode($payload, true, 16, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return new JsonResponse(['status' => 'error', 'message' => 'Ungültiges JSON.'], 400);
        }

        if (!is_array($event)) {
            return new JsonResponse(['status' => 'error', 'message' => 'JSON-Objekt erwartet.'], 400);
        }

        $result = $this->handler->handle($event);

        // Auch nicht ausgewertete Ereignisse mit 200 quittieren: sonst stellt
        // Stripe sie tagelang erneut zu.
        return new JsonResponse([
            'status' => 'ok',
            'handled' => $result['handled'],
            'message' => $result['message'],
        ]);
    }
}
