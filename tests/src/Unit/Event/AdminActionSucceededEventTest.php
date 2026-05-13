<?php

declare(strict_types=1);

namespace App\Tests\Unit\Event;

use App\Event\AdminActionSucceededEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(AdminActionSucceededEvent::class)]
final class AdminActionSucceededEventTest extends TestCase
{
    public function testProperties(): void
    {
        $event = new AdminActionSucceededEvent('labels');

        $this->assertSame('labels', $event->name);
    }
}
