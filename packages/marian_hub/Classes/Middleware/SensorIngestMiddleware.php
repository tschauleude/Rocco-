<?php

declare(strict_types=1);

namespace Marian\Hub\Middleware;

use Marian\Hub\Exception\IngestException;
use Marian\Hub\Service\SensorIngestService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\JsonResponse;

/**
 * Nimmt die HTTP-Requests der Sensoren entgegen, bevor TYPO3 anfängt, eine Seite zu bauen.
 *
 * POST /api/sensor/ingest  – Messwerte abliefern
 * GET  /api/sensor/ping    – Erreichbarkeit und Serverzeit prüfen
 */
class SensorIngestMiddleware implements MiddlewareInterface
{
    private const INGEST_PATH = '/api/sensor/ingest';
    private const PING_PATH = '/api/sensor/ping';

    /**
     * 64 KB reichen für 200 Messwerte bei weitem; alles darüber lehnen wir ab.
     */
    private const MAX_BODY_BYTES = 65536;

    public function __construct(private readonly SensorIngestService $ingestService) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = rtrim($request->getUri()->getPath(), '/');

        if ($path === self::PING_PATH) {
            return new JsonResponse(['status' => 'ok', 'time' => time()]);
        }

        if ($path !== self::INGEST_PATH) {
            return $handler->handle($request);
        }

        if ($request->getMethod() !== 'POST') {
            return $this->error('Nur POST.', 405)->withHeader('Allow', 'POST');
        }

        try {
            $payload = $this->readPayload($request);
            $result = $this->ingestService->ingest($payload, trim($request->getHeaderLine('X-Sensor-Token')));
        } catch (IngestException $exception) {
            return $this->error($exception->getMessage(), $exception->getStatus());
        }

        return new JsonResponse(['status' => 'ok'] + $result, 201);
    }

    /**
     * @return array<string, mixed>
     * @throws IngestException
     */
    private function readPayload(ServerRequestInterface $request): array
    {
        $contentLength = (int)$request->getHeaderLine('Content-Length');
        if ($contentLength > self::MAX_BODY_BYTES) {
            throw new IngestException('Anfrage zu groß.', 413);
        }

        $body = $request->getBody();
        $body->rewind();
        $raw = $body->read(self::MAX_BODY_BYTES + 1);

        if (strlen($raw) > self::MAX_BODY_BYTES) {
            throw new IngestException('Anfrage zu groß.', 413);
        }

        if (trim($raw) === '') {
            throw new IngestException('Leerer Request-Body.', 400);
        }

        try {
            $payload = json_decode($raw, true, 8, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new IngestException('Ungültiges JSON: ' . $exception->getMessage(), 400);
        }

        if (!is_array($payload)) {
            throw new IngestException('JSON-Objekt erwartet.', 400);
        }

        return $payload;
    }

    private function error(string $message, int $status): JsonResponse
    {
        return new JsonResponse(['status' => 'error', 'message' => $message], $status);
    }
}
