<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\ResponseObject\Common\PokemonCreditRow;
use App\Service\Back\GetCreditsService as BackGetCreditsService;
use App\Service\GetCreditsService;
use App\Service\LocaleService;
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
#[CoversClass(GetCreditsService::class)]
final class GetCreditsServiceTest extends TestCase
{
    #[Test]
    public function get(): void
    {
        $rows = [new PokemonCreditRow(
            pokemonSlug: 'bulbasaur',
            pokemonName: 'Bulbasaur',
            pokemonFrenchName: 'Bulbizarre',
            pokemonIcon: 'bulbasaur',
            smallRegularCredit: null,
            smallShinyCredit: null,
            bigRegularCredit: null,
            bigShinyCredit: null,
        )];

        $backService = $this->createMock(BackGetCreditsService::class);
        $backService
            ->expects($this->once())
            ->method('get')
            ->with('fr')
            ->willReturn($rows)
        ;

        $service = new GetCreditsService($backService, $this->localeService('fr'), new TagAwareAdapter(new ArrayAdapter()));

        $this->assertSame($rows, $service->get());
    }

    #[Test]
    public function cacheIsInvalidatedByCreditsTag(): void
    {
        $rows = [new PokemonCreditRow(
            pokemonSlug: 'bulbasaur',
            pokemonName: 'Bulbasaur',
            pokemonFrenchName: 'Bulbizarre',
            pokemonIcon: 'bulbasaur',
            smallRegularCredit: null,
            smallShinyCredit: null,
            bigRegularCredit: null,
            bigShinyCredit: null,
        )];

        $backService = $this->createMock(BackGetCreditsService::class);
        $backService
            ->expects($this->exactly(2))
            ->method('get')
            ->with('fr')
            ->willReturn($rows)
        ;

        $cache = new TagAwareAdapter(new ArrayAdapter());
        $service = new GetCreditsService($backService, $this->localeService('fr'), $cache);

        $service->get();
        $cache->invalidateTags(['credits_v3']);
        $service->get();
    }

    #[Test]
    public function eachLanguageHasItsOwnCacheEntry(): void
    {
        $rows = [new PokemonCreditRow(
            pokemonSlug: 'bulbasaur',
            pokemonName: 'Bulbizarre',
            pokemonFrenchName: null,
            pokemonIcon: 'bulbasaur',
            smallRegularCredit: null,
            smallShinyCredit: null,
            bigRegularCredit: null,
            bigShinyCredit: null,
        )];

        $backService = $this->createMock(BackGetCreditsService::class);
        $backService
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturn($rows)
        ;

        $cache = new TagAwareAdapter(new ArrayAdapter());
        $french = new GetCreditsService($backService, $this->localeService('fr'), $cache);
        $english = new GetCreditsService($backService, $this->localeService('en'), $cache);

        $french->get();
        $english->get();
        // Both are cached now: no more call to the back
        $french->get();
        $english->get();

        $this->assertTrue($cache->hasItem('credits_v3_fr'));
        $this->assertTrue($cache->hasItem('credits_v3_en'));
    }

    #[Test]
    public function oneInvalidationEmptiesEveryLanguage(): void
    {
        $backService = $this->createMock(BackGetCreditsService::class);
        $backService
            ->expects($this->exactly(4))
            ->method('get')
            ->willReturn([])
        ;

        $cache = new TagAwareAdapter(new ArrayAdapter());
        $french = new GetCreditsService($backService, $this->localeService('fr'), $cache);
        $english = new GetCreditsService($backService, $this->localeService('en'), $cache);

        $french->get();
        $english->get();
        $cache->invalidateTags(['credits_v3']);
        $french->get();
        $english->get();
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
