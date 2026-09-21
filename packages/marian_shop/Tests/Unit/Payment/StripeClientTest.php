<?php

declare(strict_types=1);

namespace Marian\Shop\Tests\Unit\Payment;

use Marian\Shop\Payment\StripeClient;
use Psr\Log\NullLogger;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Die Signaturprüfung entscheidet, ob eine fremde Anfrage eine Bestellung als
 * bezahlt markieren kann. Entsprechend gründlich wird sie geprüft.
 */
final class StripeClientTest extends UnitTestCase
{
    private const SECRET = 'whsec_beispielgeheimnis';
    private const PAYLOAD = '{"id":"evt_1","type":"checkout.session.completed"}';

    private StripeClient $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new StripeClient(
            $this->createMock(RequestFactory::class),
            new NullLogger()
        );
    }

    private function header(string $payload, string $secret, int $timestamp): string
    {
        return 't=' . $timestamp . ',v1=' . hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
    }

    /**
     * @test
     */
    public function aCorrectSignatureIsAccepted(): void
    {
        $header = $this->header(self::PAYLOAD, self::SECRET, time());

        self::assertTrue($this->subject->verifyWebhookSignature(self::PAYLOAD, $header, self::SECRET));
    }

    /**
     * @test
     */
    public function aChangedPayloadIsRejected(): void
    {
        $header = $this->header(self::PAYLOAD, self::SECRET, time());

        self::assertFalse($this->subject->verifyWebhookSignature(self::PAYLOAD . ' ', $header, self::SECRET));
    }

    /**
     * @test
     */
    public function aForeignSecretIsRejected(): void
    {
        $header = $this->header(self::PAYLOAD, 'whsec_falsch', time());

        self::assertFalse($this->subject->verifyWebhookSignature(self::PAYLOAD, $header, self::SECRET));
    }

    /**
     * @test
     */
    public function anOldSignatureIsRejected(): void
    {
        $header = $this->header(self::PAYLOAD, self::SECRET, time() - 600);

        self::assertFalse(
            $this->subject->verifyWebhookSignature(self::PAYLOAD, $header, self::SECRET),
            'Alte Signaturen müssen abgelehnt werden, sonst ließe sich eine Anfrage wiederholen.'
        );
    }

    /**
     * @test
     */
    public function aRecentSignatureIsStillAccepted(): void
    {
        $header = $this->header(self::PAYLOAD, self::SECRET, time() - 240);

        self::assertTrue($this->subject->verifyWebhookSignature(self::PAYLOAD, $header, self::SECRET));
    }

    /**
     * @test
     */
    public function withoutAConfiguredSecretNothingIsAccepted(): void
    {
        $header = $this->header(self::PAYLOAD, self::SECRET, time());

        self::assertFalse($this->subject->verifyWebhookSignature(self::PAYLOAD, $header, ''));
    }

    /**
     * @test
     * @dataProvider brokenHeaders
     */
    public function brokenHeadersAreRejected(string $header): void
    {
        self::assertFalse($this->subject->verifyWebhookSignature(self::PAYLOAD, $header, self::SECRET));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function brokenHeaders(): array
    {
        return [
            'leer' => [''],
            'ohne Signatur' => ['t=' . time()],
            'ohne Zeitstempel' => ['v1=abc'],
            'Unsinn' => ['quatsch'],
        ];
    }

    /**
     * @test
     */
    public function oneMatchingSignatureAmongSeveralIsEnough(): void
    {
        // Stripe schickt während eines Schlüsselwechsels mehrere Signaturen mit.
        $header = $this->header(self::PAYLOAD, self::SECRET, time()) . ',v1=deadbeef';

        self::assertTrue($this->subject->verifyWebhookSignature(self::PAYLOAD, $header, self::SECRET));
    }
}
