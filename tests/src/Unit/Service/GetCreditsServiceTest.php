<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\ResponseObject\Common\CreditGroup;
use App\Service\Back\GetCreditsService as BackGetCreditsService;
use App\Service\GetCreditsService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;

/**
 * @internal
 */
#[CoversClass(GetCreditsService::class)]
final class GetCreditsServiceTest extends TestCase
{
    public function testGet(): void
    {
        $credits = [new CreditGroup(credit: 'PokéSprite - https://github.com/msikma/pokesprite', images: [])];

        $backService = $this->createMock(BackGetCreditsService::class);
        $backService
            ->expects($this->once())
            ->method('get')
            ->willReturn($credits)
        ;

        $service = new GetCreditsService($backService, new TagAwareAdapter(new ArrayAdapter()));

        $this->assertSame($credits, $service->get());
    }

    public function testCacheIsInvalidatedByCreditsTag(): void
    {
        $credits = [new CreditGroup(credit: 'PokéSprite - https://github.com/msikma/pokesprite', images: [])];

        $backService = $this->createMock(BackGetCreditsService::class);
        $backService
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturn($credits)
        ;

        $cache = new TagAwareAdapter(new ArrayAdapter());
        $service = new GetCreditsService($backService, $cache);

        $service->get();
        $cache->invalidateTags(['credits_v2']);
        $service->get();
    }
}
