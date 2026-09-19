<?php

declare(strict_types=1);

namespace Marian\Hub\Tests\Unit\Domain\Model;

use Marian\Hub\Domain\Model\Sensor;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SensorTest extends UnitTestCase
{
    /**
     * @test
     */
    public function valuesAreFormattedWithUnitAndGermanSeparators(): void
    {
        $sensor = new Sensor();
        $sensor->setUnit('°C');
        $sensor->setDecimals(1);

        self::assertSame('21,4 °C', $sensor->format(21.44));
    }

    /**
     * @test
     */
    public function formattingWorksWithoutAUnit(): void
    {
        $sensor = new Sensor();
        $sensor->setDecimals(0);

        self::assertSame('1.013', $sensor->format(1013.2));
    }

    /**
     * @test
     */
    public function rangeCheckHonoursBothThresholds(): void
    {
        $sensor = new Sensor();
        $sensor->setWarnMin(18.0);
        $sensor->setWarnMax(24.0);

        self::assertTrue($sensor->isOutOfRange(17.9));
        self::assertTrue($sensor->isOutOfRange(24.1));
        self::assertFalse($sensor->isOutOfRange(18.0));
        self::assertFalse($sensor->isOutOfRange(24.0));
    }

    /**
     * @test
     */
    public function withoutThresholdsNothingIsOutOfRange(): void
    {
        self::assertFalse((new Sensor())->isOutOfRange(-273.15));
    }

    /**
     * @test
     */
    public function aSensorThatNeverReportedIsOffline(): void
    {
        self::assertFalse((new Sensor())->isOnline());
    }

    /**
     * @test
     */
    public function aSensorThatReportedRecentlyIsOnline(): void
    {
        $sensor = new Sensor();
        $sensor->setLastSeen(new \DateTime('-5 minutes'));

        self::assertTrue($sensor->isOnline());
    }

    /**
     * @test
     */
    public function aSensorThatWentSilentForAnHourIsOffline(): void
    {
        $sensor = new Sensor();
        $sensor->setLastSeen(new \DateTime('-70 minutes'));

        self::assertFalse($sensor->isOnline());
    }
}
