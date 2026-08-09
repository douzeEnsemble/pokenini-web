<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventListener;

use App\Event\AdminActionSucceededEvent;
use App\EventListener\LabelsCacheInvalidationListener;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * @internal
 */
#[CoversClass(LabelsCacheInvalidationListener::class)]
final class LabelsCacheInvalidationListenerTest extends TestCase
{
    #[Test]
    public function invalidatesLabelsCacheOnLabelsEvent(): void
    {
        $cache = $this->createMock(TagAwareCacheInterface::class);
        $cache
            ->expects($this->once())
            ->method('invalidateTags')
            ->with(['labels'])
        ;

        $listener = new LabelsCacheInvalidationListener($cache);
        $listener(new AdminActionSucceededEvent('labels'));
    }

    #[Test]
    public function doesNotInvalidateForOtherNames(): void
    {
        $cache = $this->createMock(TagAwareCacheInterface::class);
        $cache
            ->expects($this->never())
            ->method('invalidateTags')
        ;

        $listener = new LabelsCacheInvalidationListener($cache);
        $listener(new AdminActionSucceededEvent('dex'));
    }
}
