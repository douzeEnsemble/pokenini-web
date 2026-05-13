<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\Back\GetLabelsService as BackGetLabelsService;
use App\Service\GetLabelsService;
use App\Tests\Common\Traits\ResponseObjectTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;

/**
 * @internal
 */
#[CoversClass(GetLabelsService::class)]
final class GetLabelsServiceTest extends TestCase
{
    use ResponseObjectTrait;

    public function testGetCatchStates(): void
    {
        $this->getService()->getCatchStates();
    }

    public function testGetTypes(): void
    {
        $this->getService()->getTypes();
    }

    public function testGetFormsCategory(): void
    {
        $this->getService()->getFormsCategory();
    }

    public function testGetFormsRegional(): void
    {
        $this->getService()->getFormsRegional();
    }

    public function testGetFormsSpecial(): void
    {
        $this->getService()->getFormsSpecial();
    }

    public function testGetFormsVariant(): void
    {
        $this->getService()->getFormsVariant();
    }

    public function testGetGameBundles(): void
    {
        $this->getService()->getGameBundles();
    }

    public function testGetCollections(): void
    {
        $this->getService()->getCollections();
    }

    public function testCacheIsInvalidatedByLabelsTag(): void
    {
        $backService = $this->createMock(BackGetLabelsService::class);
        $backService
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturn($this->getStubLabels())
        ;

        $cache = new TagAwareAdapter(new ArrayAdapter());
        $service = new GetLabelsService($backService, $cache);

        $service->getCatchStates();
        $cache->invalidateTags(['labels']);
        $service->getCatchStates();
    }

    private function getService(): GetLabelsService
    {
        $getLabelsService = $this->createMock(BackGetLabelsService::class);
        $getLabelsService
            ->expects($this->once())
            ->method('get')
            ->willReturn($this->getStubLabels())
        ;

        return new GetLabelsService(
            $getLabelsService,
            new TagAwareAdapter(new ArrayAdapter()),
        );
    }
}
