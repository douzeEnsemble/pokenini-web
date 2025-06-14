<?php

declare(strict_types=1);

namespace App\Tests\Unit\DTO;

use App\DTO\ActionLog;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ActionLog::class)]
class ActionLogTest extends TestCase
{
    public function testFullCreateFromArray(): void
    {
        $actionLog = ActionLog::createFromArray([
            'created_at' => '2023-03-21 08:34:47+00',
            'done_at' => '2023-03-22 08:34:47+00',
            'execution_time' => 12,
            'details' => [
                'truc' => 1,
                'bidule' => 2,
            ],
            'error_trace' => 'Something bad happen',
        ]);

        $this->assertEquals(
            new \DateTime('2023-03-21 08:34:47+00'),
            $actionLog->createdAt,
        );
        $this->assertEquals(
            new \DateTime('2023-03-22 08:34:47+00'),
            $actionLog->doneAt,
        );
        $this->assertEquals(
            12,
            $actionLog->executionTime,
        );
        $this->assertEquals(
            [
                'truc' => 1,
                'bidule' => 2,
            ],
            $actionLog->details,
        );
        $this->assertEquals(
            'Something bad happen',
            $actionLog->errorTrace,
        );
    }

    public function testMinimalCreateFromArray(): void
    {
        $actionLog = ActionLog::createFromArray([
            'created_at' => '2023-03-21 08:34:47+00',
        ]);

        $this->assertEquals(
            new \DateTime('2023-03-21 08:34:47+00'),
            $actionLog->createdAt,
        );
        $this->assertNull(
            $actionLog->doneAt,
        );
        $this->assertNull(
            $actionLog->executionTime,
        );
        $this->assertEmpty(
            $actionLog->details,
        );
        $this->assertNull(
            $actionLog->errorTrace,
        );
    }

    public function testExecutionTimeCasting(): void
    {
        $actionLog = ActionLog::createFromArray([
            'created_at' => '2023-03-21 08:34:47+00',
            'execution_time' => '2',
        ]);

        $this->assertEquals(
            2,
            $actionLog->executionTime,
        );
    }
}
