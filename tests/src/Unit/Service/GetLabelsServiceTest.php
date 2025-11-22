<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\Back\GetLabelsService as BackGetLabelsService;
use App\Service\GetLabelsService;
use App\Tests\Common\Traits\ResponseObjectTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(GetLabelsService::class)]
class GetLabelsServiceTest extends TestCase
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

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
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
        );
    }
}
