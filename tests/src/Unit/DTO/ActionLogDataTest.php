<?php

declare(strict_types=1);

namespace App\Tests\Unit\DTO;

use App\DTO\ActionLog;
use App\DTO\ActionLogData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ActionLogData::class)]
class ActionLogDataTest extends TestCase
{
    public function testConstructorWithoutLast(): void
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

        $actionLogData = new ActionLogData(
            'truc',
            $actionLog,
            null
        );

        $this->assertEquals('truc', $actionLogData->item);
        $this->assertEquals($actionLog, $actionLogData->current);
        $this->assertNull($actionLogData->last);
    }

    public function testConstructorWithLast(): void
    {
        $actionLogCurrent = ActionLog::createFromArray([
            'created_at' => '2023-03-21 08:34:47+00',
            'done_at' => '2023-03-22 08:34:47+00',
            'execution_time' => 12,
            'details' => [
                'truc' => 1,
                'bidule' => 2,
            ],
            'error_trace' => 'Something bad happen',
        ]);

        $actionLogLast = ActionLog::createFromArray([
            'created_at' => '2022-03-21 08:34:47+00',
            'done_at' => '2022-03-22 08:34:47+00',
            'execution_time' => 12,
            'details' => [
                'truc' => 1,
                'bidule' => 2,
            ],
            'error_trace' => '',
        ]);

        $actionLogData = new ActionLogData(
            'truc',
            $actionLogCurrent,
            $actionLogLast,
        );

        $this->assertEquals('truc', $actionLogData->item);
        $this->assertEquals($actionLogCurrent, $actionLogData->current);
        $this->assertEquals($actionLogLast, $actionLogData->last);
    }
}
