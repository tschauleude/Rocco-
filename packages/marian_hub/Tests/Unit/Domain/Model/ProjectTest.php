<?php

declare(strict_types=1);

namespace Marian\Hub\Tests\Unit\Domain\Model;

use Marian\Hub\Domain\Model\LogEntry;
use Marian\Hub\Domain\Model\Project;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ProjectTest extends UnitTestCase
{
    /**
     * @test
     */
    public function materialListSplitsRowsIntoColumns(): void
    {
        $project = new Project();
        $project->setBillOfMaterials("ESP32 | 1 | 8,90 €\nDHT22 | 2 | 4,50 €");

        self::assertSame(
            [
                ['part' => 'ESP32', 'amount' => '1', 'price' => '8,90 €'],
                ['part' => 'DHT22', 'amount' => '2', 'price' => '4,50 €'],
            ],
            $project->getMaterialList()
        );
    }

    /**
     * @test
     */
    public function materialListToleratesMissingColumnsAndBlankLines(): void
    {
        $project = new Project();
        $project->setBillOfMaterials("Lötzinn\n\n  Schrumpfschlauch | 3  \n");

        self::assertSame(
            [
                ['part' => 'Lötzinn', 'amount' => '', 'price' => ''],
                ['part' => 'Schrumpfschlauch', 'amount' => '3', 'price' => ''],
            ],
            $project->getMaterialList()
        );
    }

    /**
     * @test
     */
    public function materialListIsEmptyWithoutInput(): void
    {
        self::assertSame([], (new Project())->getMaterialList());
    }

    /**
     * @test
     */
    public function hoursSpentSumsUpTheLogbook(): void
    {
        $project = new Project();
        foreach ([1.5, 2.25, 0.0] as $hours) {
            $entry = new LogEntry();
            $entry->setHoursSpent($hours);
            $project->addLogEntry($entry);
        }

        self::assertSame(3.75, $project->getHoursSpent());
    }

    /**
     * @test
     */
    public function progressStaysWithinItsBounds(): void
    {
        $project = new Project();

        $project->setProgress(150);
        self::assertSame(100, $project->getProgress());

        $project->setProgress(-20);
        self::assertSame(0, $project->getProgress());
    }
}
