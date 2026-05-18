<?php

declare(strict_types=1);

namespace App\Tests\Unit\DTO;

use App\DTO\ActionLogData;
use App\ResponseObject\ActionLog;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ActionLogData::class)]
final class ActionLogDataTest extends TestCase
{
    public function testConstructorWithoutLast(): void
    {
        $actionLog = new ActionLog(
            new \DateTime('2023-03-21 08:34:47+00'),
            new \DateTime('2023-03-22 08:34:47+00'),
            12,
            ['truc' => 1, 'bidule' => 2],
            'Something bad happen',
        );

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
        $actionLogCurrent = new ActionLog(
            new \DateTime('2023-03-21 08:34:47+00'),
            new \DateTime('2023-03-22 08:34:47+00'),
            12,
            ['truc' => 1, 'bidule' => 2],
            'Something bad happen',
        );

        $actionLogLast = new ActionLog(
            new \DateTime('2022-03-21 08:34:47+00'),
            new \DateTime('2022-03-22 08:34:47+00'),
            12,
            ['truc' => 1, 'bidule' => 2],
            '',
        );

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
