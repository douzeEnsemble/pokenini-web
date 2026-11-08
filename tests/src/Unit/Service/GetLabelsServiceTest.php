<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\Back\GetLabelsService as BackGetLabelsService;
use App\Service\GetLabelsService;
use App\Service\LocaleService;
use App\Tests\Common\Traits\ResponseObjectTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @internal
 */
#[CoversClass(GetLabelsService::class)]
final class GetLabelsServiceTest extends TestCase
{
    use ResponseObjectTrait;

    #[Test]
    public function getCatchStates(): void
    {
        $this->getService()->getCatchStates();
    }

    #[Test]
    public function getAllLabels(): void
    {
        $backService = $this->createMock(BackGetLabelsService::class);
        $backService
            ->expects($this->once())
            ->method('get')
            ->with('fr')
            ->willReturn($this->getStubLabels())
        ;

        $service = new GetLabelsService($backService, $this->localeService('fr'), new TagAwareAdapter(new ArrayAdapter()));
        $labels = $service->getAllLabels();

        $this->assertNotEmpty($labels->getCatchStates());
        $this->assertNotEmpty($labels->getTypes());
    }

    #[Test]
    public function cacheIsInvalidatedByLabelsTag(): void
    {
        $backService = $this->createMock(BackGetLabelsService::class);
        $backService
            ->expects($this->exactly(2))
            ->method('get')
            ->with('fr')
            ->willReturn($this->getStubLabels())
        ;

        $cache = new TagAwareAdapter(new ArrayAdapter());
        $service = new GetLabelsService($backService, $this->localeService('fr'), $cache);

        $service->getCatchStates();
        $cache->invalidateTags(['labels']);
        $service->getCatchStates();
    }

    #[Test]
    public function eachLanguageHasItsOwnCacheEntry(): void
    {
        $backService = $this->createMock(BackGetLabelsService::class);
        $backService
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturn($this->getStubLabels())
        ;

        $cache = new TagAwareAdapter(new ArrayAdapter());

        (new GetLabelsService($backService, $this->localeService('fr'), $cache))->getCatchStates();
        (new GetLabelsService($backService, $this->localeService('en'), $cache))->getCatchStates();
        // Both are cached now: no more call to the back
        (new GetLabelsService($backService, $this->localeService('fr'), $cache))->getCatchStates();
        (new GetLabelsService($backService, $this->localeService('en'), $cache))->getCatchStates();

        $this->assertTrue($cache->hasItem('labels_fr'));
        $this->assertTrue($cache->hasItem('labels_en'));
    }

    #[Test]
    public function oneInvalidationEmptiesEveryLanguage(): void
    {
        $backService = $this->createMock(BackGetLabelsService::class);
        $backService
            ->expects($this->exactly(4))
            ->method('get')
            ->willReturn($this->getStubLabels())
        ;

        $cache = new TagAwareAdapter(new ArrayAdapter());
        $frenchLabels = new GetLabelsService($backService, $this->localeService('fr'), $cache);
        $englishLabels = new GetLabelsService($backService, $this->localeService('en'), $cache);

        $frenchLabels->getCatchStates();
        $englishLabels->getCatchStates();
        $cache->invalidateTags(['labels']);
        $frenchLabels->getCatchStates();
        $englishLabels->getCatchStates();
    }

    private function getService(): GetLabelsService
    {
        $getLabelsService = $this->createMock(BackGetLabelsService::class);
        $getLabelsService
            ->expects($this->once())
            ->method('get')
            ->with('fr')
            ->willReturn($this->getStubLabels())
        ;

        return new GetLabelsService(
            $getLabelsService,
            $this->localeService('fr'),
            new TagAwareAdapter(new ArrayAdapter()),
        );
    }

    private function localeService(string $locale): LocaleService
    {
        $request = new Request();
        $request->setLocale($locale);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        return new LocaleService($requestStack, ['en', 'fr'], 'en');
    }
}
