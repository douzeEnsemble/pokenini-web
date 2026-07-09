<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Election;

use App\ResponseObject\Election\ElectionReportCompletion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ElectionReportCompletion::class)]
final class ElectionReportCompletionTest extends TestCase
{
    public function testGetters(): void
    {
        $completion = new ElectionReportCompletion(atMaxCount: 5, underMaxCount: 1);

        $this->assertSame(5, $completion->getAtMaxCount());
        $this->assertSame(1, $completion->getUnderMaxCount());
    }
}
