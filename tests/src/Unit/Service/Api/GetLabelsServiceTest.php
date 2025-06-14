<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Api;

use App\Service\Api\GetCatchStatesService;
use App\Service\Api\GetCollectionsService;
use App\Service\Api\GetFormsService;
use App\Service\Api\GetGameBundlesService;
use App\Service\Api\GetLabelsService;
use App\Service\Api\GetTypesService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(GetLabelsService::class)]
class GetLabelsServiceTest extends TestCase
{
    public function testGetCatchStates(): void
    {
        $this->getService('catch_states')->getCatchStates();
    }

    public function testGetTypes(): void
    {
        $this->getService('types')->getTypes();
    }

    public function testGetFormsCategory(): void
    {
        $this->getService('forms_category')->getFormsCategory();
    }

    public function testGetFormsRegional(): void
    {
        $this->getService('forms_regional')->getFormsRegional();
    }

    public function testGetFormsSpecial(): void
    {
        $this->getService('forms_special')->getFormsSpecial();
    }

    public function testGetFormsVariant(): void
    {
        $this->getService('forms_variant')->getFormsVariant();
    }

    public function testGetGameBundles(): void
    {
        $this->getService('game_bundles')->getGameBundles();
    }

    public function testGetCollections(): void
    {
        $this->getService('collections')->getCollections();
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function getService(string $type): GetLabelsService
    {
        $getCatchStatesService = $this->createMock(GetCatchStatesService::class);
        $getCatchStatesService
            ->expects($this->exactly('catch_states' === $type ? 1 : 0))
            ->method('get')
            ->willReturn([])
        ;

        $getTypesService = $this->createMock(GetTypesService::class);
        $getTypesService
            ->expects($this->exactly('types' === $type ? 1 : 0))
            ->method('get')
            ->willReturn([])
        ;

        $getFormsService = $this->createMock(GetFormsService::class);
        $getFormsService
            ->expects($this->exactly('forms_category' === $type ? 1 : 0))
            ->method('getFormsCategory')
            ->willReturn([])
        ;
        $getFormsService
            ->expects($this->exactly('forms_regional' === $type ? 1 : 0))
            ->method('getFormsRegional')
            ->willReturn([])
        ;
        $getFormsService
            ->expects($this->exactly('forms_special' === $type ? 1 : 0))
            ->method('getFormsSpecial')
            ->willReturn([])
        ;
        $getFormsService
            ->expects($this->exactly('forms_variant' === $type ? 1 : 0))
            ->method('getFormsVariant')
            ->willReturn([])
        ;

        $getGameBundlesService = $this->createMock(GetGameBundlesService::class);
        $getGameBundlesService
            ->expects($this->exactly('game_bundles' === $type ? 1 : 0))
            ->method('get')
            ->willReturn([])
        ;

        $getCollectionsService = $this->createMock(GetCollectionsService::class);
        $getCollectionsService
            ->expects($this->exactly('collections' === $type ? 1 : 0))
            ->method('get')
            ->willReturn([])
        ;

        return new GetLabelsService(
            $getCatchStatesService,
            $getTypesService,
            $getFormsService,
            $getGameBundlesService,
            $getCollectionsService,
        );
    }
}
