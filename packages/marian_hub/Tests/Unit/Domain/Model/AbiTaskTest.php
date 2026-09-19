<?php

declare(strict_types=1);

namespace Marian\Hub\Tests\Unit\Domain\Model;

use Marian\Hub\Domain\Model\AbiTask;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class AbiTaskTest extends UnitTestCase
{
    /**
     * @test
     */
    public function unknownPhasesFallBackToTheIdeaColumn(): void
    {
        $task = new AbiTask();
        $task->setPhase('irgendwas');

        self::assertSame(AbiTask::PHASE_IDEA, $task->getPhase());
    }

    /**
     * @test
     */
    public function taskWithoutDueDateHasNoCountdown(): void
    {
        $task = new AbiTask();

        self::assertNull($task->getDaysLeft());
        self::assertFalse($task->isOverdue());
    }

    /**
     * @test
     */
    public function futureDueDatesCountForward(): void
    {
        $task = new AbiTask();
        $task->setDueDate(new \DateTime('+3 days'));

        self::assertSame(3, $task->getDaysLeft());
        self::assertFalse($task->isOverdue());
    }

    /**
     * @test
     */
    public function pastDueDatesMarkTheTaskAsOverdue(): void
    {
        $task = new AbiTask();
        $task->setDueDate(new \DateTime('-2 days'));

        self::assertSame(-2, $task->getDaysLeft());
        self::assertTrue($task->isOverdue());
    }

    /**
     * @test
     */
    public function finishedTasksAreNeverOverdue(): void
    {
        $task = new AbiTask();
        $task->setDueDate(new \DateTime('-10 days'));
        $task->setPhase(AbiTask::PHASE_DONE);

        self::assertTrue($task->isDone());
        self::assertFalse($task->isOverdue());
    }

    /**
     * @test
     */
    public function priorityIsClampedToTheScale(): void
    {
        $task = new AbiTask();

        $task->setPriority(9);
        self::assertSame(3, $task->getPriority());

        $task->setPriority(-1);
        self::assertSame(0, $task->getPriority());
    }
}
