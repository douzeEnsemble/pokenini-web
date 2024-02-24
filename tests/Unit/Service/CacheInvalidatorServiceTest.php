<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\Api\GetCatchStatesService;
use App\Service\ApiService;
use App\Service\CacheInvalidator\AlbumsCacheInvalidatorService;
use App\Service\CacheInvalidator\CatchStatesCacheInvalidatorService;
use App\Service\CacheInvalidator\DexCacheInvalidatorService;
use App\Service\CacheInvalidator\FormsCacheInvalidatorService;
use App\Service\CacheInvalidator\ReportsCacheInvalidatorService;
use App\Service\CacheInvalidator\TypesCacheInvalidatorService;
use App\Service\CacheInvalidatorService;
use PHPUnit\Framework\TestCase;

class CacheInvalidatorServiceTest extends TestCase
{
    public function testInvalidateLabels(): void
    {
        $catchStatesCacheInvalidatorService = $this->createMock(CatchStatesCacheInvalidatorService::class);
        $typesCacheInvalidatorService = $this->createMock(TypesCacheInvalidatorService::class);
        $formsCacheInvalidatorService = $this->createMock(FormsCacheInvalidatorService::class);
        $dexCacheInvalidatorService = $this->createMock(DexCacheInvalidatorService::class);
        $albumsCacheInvalidatorService = $this->createMock(AlbumsCacheInvalidatorService::class);
        $reportCacheInvalidatorService = $this->createMock(ReportsCacheInvalidatorService::class);

        $catchStatesCacheInvalidatorService
            ->expects($this->once())
            ->method('invalidate')
        ;
        $typesCacheInvalidatorService
            ->expects($this->once())
            ->method('invalidate')
        ;
        $formsCacheInvalidatorService
            ->expects($this->once())
            ->method('invalidate')
        ;
        $dexCacheInvalidatorService
            ->expects($this->never())
            ->method('invalidate')
        ;
        $albumsCacheInvalidatorService
            ->expects($this->never())
            ->method('invalidate')
        ;
        $reportCacheInvalidatorService
            ->expects($this->never())
            ->method('invalidate')
        ;

        $cacheInvalidator = new CacheInvalidatorService(
            $catchStatesCacheInvalidatorService,
            $typesCacheInvalidatorService,
            $formsCacheInvalidatorService,
            $dexCacheInvalidatorService,
            $albumsCacheInvalidatorService,
            $reportCacheInvalidatorService,
        );

        $cacheInvalidator->invalidate('labels');
    }

    public function testInvalidateCatchStates(): void
    {
        $catchStatesCacheInvalidatorService = $this->createMock(CatchStatesCacheInvalidatorService::class);
        $typesCacheInvalidatorService = $this->createMock(TypesCacheInvalidatorService::class);
        $formsCacheInvalidatorService = $this->createMock(FormsCacheInvalidatorService::class);
        $dexCacheInvalidatorService = $this->createMock(DexCacheInvalidatorService::class);
        $albumsCacheInvalidatorService = $this->createMock(AlbumsCacheInvalidatorService::class);
        $reportCacheInvalidatorService = $this->createMock(ReportsCacheInvalidatorService::class);

        $cacheInvalidator = new CacheInvalidatorService(
            $catchStatesCacheInvalidatorService,
            $typesCacheInvalidatorService,
            $formsCacheInvalidatorService,
            $dexCacheInvalidatorService,
            $albumsCacheInvalidatorService,
            $reportCacheInvalidatorService,
        );

        $this->expectException(\InvalidArgumentException::class);

        $cacheInvalidator->invalidate('catch_states');
    }

    public function testInvalidateTypes(): void
    {
        $catchStatesCacheInvalidatorService = $this->createMock(CatchStatesCacheInvalidatorService::class);
        $typesCacheInvalidatorService = $this->createMock(TypesCacheInvalidatorService::class);
        $formsCacheInvalidatorService = $this->createMock(FormsCacheInvalidatorService::class);
        $dexCacheInvalidatorService = $this->createMock(DexCacheInvalidatorService::class);
        $albumsCacheInvalidatorService = $this->createMock(AlbumsCacheInvalidatorService::class);
        $reportCacheInvalidatorService = $this->createMock(ReportsCacheInvalidatorService::class);

        $cacheInvalidator = new CacheInvalidatorService(
            $catchStatesCacheInvalidatorService,
            $typesCacheInvalidatorService,
            $formsCacheInvalidatorService,
            $dexCacheInvalidatorService,
            $albumsCacheInvalidatorService,
            $reportCacheInvalidatorService,
        );

        $this->expectException(\InvalidArgumentException::class);

        $cacheInvalidator->invalidate('types');
    }

    public function testInvalidateGamesAndDex(): void
    {
        $catchStatesCacheInvalidatorService = $this->createMock(CatchStatesCacheInvalidatorService::class);
        $typesCacheInvalidatorService = $this->createMock(TypesCacheInvalidatorService::class);
        $formsCacheInvalidatorService = $this->createMock(FormsCacheInvalidatorService::class);
        $dexCacheInvalidatorService = $this->createMock(DexCacheInvalidatorService::class);
        $albumsCacheInvalidatorService = $this->createMock(AlbumsCacheInvalidatorService::class);
        $reportCacheInvalidatorService = $this->createMock(ReportsCacheInvalidatorService::class);

        $catchStatesCacheInvalidatorService
            ->expects($this->never())
            ->method('invalidate')
        ;
        $typesCacheInvalidatorService
            ->expects($this->never())
            ->method('invalidate')
        ;
        $formsCacheInvalidatorService
            ->expects($this->never())
            ->method('invalidate')
        ;
        $dexCacheInvalidatorService
            ->expects($this->once())
            ->method('invalidate')
        ;
        $albumsCacheInvalidatorService
            ->expects($this->never())
            ->method('invalidate')
        ;
        $reportCacheInvalidatorService
            ->expects($this->never())
            ->method('invalidate')
        ;

        $cacheInvalidator = new CacheInvalidatorService(
            $catchStatesCacheInvalidatorService,
            $typesCacheInvalidatorService,
            $formsCacheInvalidatorService,
            $dexCacheInvalidatorService,
            $albumsCacheInvalidatorService,
            $reportCacheInvalidatorService,
        );

        $cacheInvalidator->invalidate('games_and_dex');
    }

    public function testInvalidatePokemons(): void
    {
        $catchStatesCacheInvalidatorService = $this->createMock(CatchStatesCacheInvalidatorService::class);
        $typesCacheInvalidatorService = $this->createMock(TypesCacheInvalidatorService::class);
        $formsCacheInvalidatorService = $this->createMock(FormsCacheInvalidatorService::class);
        $dexCacheInvalidatorService = $this->createMock(DexCacheInvalidatorService::class);
        $albumsCacheInvalidatorService = $this->createMock(AlbumsCacheInvalidatorService::class);
        $reportCacheInvalidatorService = $this->createMock(ReportsCacheInvalidatorService::class);

        $catchStatesCacheInvalidatorService
            ->expects($this->never())
            ->method('invalidate')
        ;
        $typesCacheInvalidatorService
            ->expects($this->never())
            ->method('invalidate')
        ;
        $formsCacheInvalidatorService
            ->expects($this->never())
            ->method('invalidate')
        ;
        $dexCacheInvalidatorService
            ->expects($this->never())
            ->method('invalidate')
        ;
        $albumsCacheInvalidatorService
            ->expects($this->never())
            ->method('invalidate')
        ;
        $reportCacheInvalidatorService
            ->expects($this->never())
            ->method('invalidate')
        ;

        $cacheInvalidator = new CacheInvalidatorService(
            $catchStatesCacheInvalidatorService,
            $typesCacheInvalidatorService,
            $formsCacheInvalidatorService,
            $dexCacheInvalidatorService,
            $albumsCacheInvalidatorService,
            $reportCacheInvalidatorService,
        );

        $cacheInvalidator->invalidate('pokemons');

        // There is no action, but no exception either
        $this->assertTrue(true);
    }

    /**
     * @dataProvider providerInvalidateAlbums
     */
    public function testInvalidateAlbums(string $type): void
    {
        $catchStatesCacheInvalidatorService = $this->createMock(CatchStatesCacheInvalidatorService::class);
        $typesCacheInvalidatorService = $this->createMock(TypesCacheInvalidatorService::class);
        $formsCacheInvalidatorService = $this->createMock(FormsCacheInvalidatorService::class);
        $dexCacheInvalidatorService = $this->createMock(DexCacheInvalidatorService::class);
        $albumsCacheInvalidatorService = $this->createMock(AlbumsCacheInvalidatorService::class);
        $reportCacheInvalidatorService = $this->createMock(ReportsCacheInvalidatorService::class);

        $catchStatesCacheInvalidatorService
            ->expects($this->never())
            ->method('invalidate')
        ;
        $typesCacheInvalidatorService
            ->expects($this->never())
            ->method('invalidate')
        ;
        $formsCacheInvalidatorService
            ->expects($this->never())
            ->method('invalidate')
        ;
        $dexCacheInvalidatorService
            ->expects($this->never())
            ->method('invalidate')
        ;
        $albumsCacheInvalidatorService
            ->expects($this->once())
            ->method('invalidate')
        ;
        $reportCacheInvalidatorService
            ->expects($this->never())
            ->method('invalidate')
        ;

        $cacheInvalidator = new CacheInvalidatorService(
            $catchStatesCacheInvalidatorService,
            $typesCacheInvalidatorService,
            $formsCacheInvalidatorService,
            $dexCacheInvalidatorService,
            $albumsCacheInvalidatorService,
            $reportCacheInvalidatorService,
        );

        $cacheInvalidator->invalidate($type);
    }

    /**
     * @return string[][]
     */
    public function providerInvalidateAlbums(): array
    {
        return [
            'regional_dex_numbers' => ['regional_dex_numbers'],
            'games_availabilities' => ['games_availabilities'],
            'games_shinies_availabilities' => ['games_shinies_availabilities'],
            'game_bundles_availabilities' => ['game_bundles_availabilities'],
            'game_bundles_shinies_availabilities' => ['game_bundles_shinies_availabilities'],
            'pokemon_availabilities' => ['pokemon_availabilities'],
            'albums' => ['albums'],
        ];
    }

    public function testInvalidateDexAvailability(): void
    {
        $catchStatesCacheInvalidatorService = $this->createMock(CatchStatesCacheInvalidatorService::class);
        $typesCacheInvalidatorService = $this->createMock(TypesCacheInvalidatorService::class);
        $formsCacheInvalidatorService = $this->createMock(FormsCacheInvalidatorService::class);
        $dexCacheInvalidatorService = $this->createMock(DexCacheInvalidatorService::class);
        $albumsCacheInvalidatorService = $this->createMock(AlbumsCacheInvalidatorService::class);
        $reportCacheInvalidatorService = $this->createMock(ReportsCacheInvalidatorService::class);

        $catchStatesCacheInvalidatorService
            ->expects($this->never())
            ->method('invalidate')
        ;
        $typesCacheInvalidatorService
            ->expects($this->never())
            ->method('invalidate')
        ;
        $formsCacheInvalidatorService
            ->expects($this->never())
            ->method('invalidate')
        ;
        $dexCacheInvalidatorService
            ->expects($this->once())
            ->method('invalidate')
        ;
        $albumsCacheInvalidatorService
            ->expects($this->once())
            ->method('invalidate')
        ;
        $reportCacheInvalidatorService
            ->expects($this->never())
            ->method('invalidate')
        ;

        $cacheInvalidator = new CacheInvalidatorService(
            $catchStatesCacheInvalidatorService,
            $typesCacheInvalidatorService,
            $formsCacheInvalidatorService,
            $dexCacheInvalidatorService,
            $albumsCacheInvalidatorService,
            $reportCacheInvalidatorService,
        );

        $cacheInvalidator->invalidate('dex_availabilities');
    }

    public function testInvalidateReports(): void
    {
        $catchStatesCacheInvalidatorService = $this->createMock(CatchStatesCacheInvalidatorService::class);
        $typesCacheInvalidatorService = $this->createMock(TypesCacheInvalidatorService::class);
        $formsCacheInvalidatorService = $this->createMock(FormsCacheInvalidatorService::class);
        $dexCacheInvalidatorService = $this->createMock(DexCacheInvalidatorService::class);
        $albumsCacheInvalidatorService = $this->createMock(AlbumsCacheInvalidatorService::class);
        $reportCacheInvalidatorService = $this->createMock(ReportsCacheInvalidatorService::class);

        $catchStatesCacheInvalidatorService
            ->expects($this->never())
            ->method('invalidate')
        ;
        $typesCacheInvalidatorService
            ->expects($this->never())
            ->method('invalidate')
        ;
        $formsCacheInvalidatorService
            ->expects($this->never())
            ->method('invalidate')
        ;
        $dexCacheInvalidatorService
            ->expects($this->never())
            ->method('invalidate')
        ;
        $albumsCacheInvalidatorService
            ->expects($this->never())
            ->method('invalidate')
        ;
        $reportCacheInvalidatorService
            ->expects($this->once())
            ->method('invalidate')
        ;

        $cacheInvalidator = new CacheInvalidatorService(
            $catchStatesCacheInvalidatorService,
            $typesCacheInvalidatorService,
            $formsCacheInvalidatorService,
            $dexCacheInvalidatorService,
            $albumsCacheInvalidatorService,
            $reportCacheInvalidatorService,
        );

        $cacheInvalidator->invalidate('reports');
    }

    public function testInvalidateUnknown(): void
    {
        $catchStatesCacheInvalidatorService = $this->createMock(CatchStatesCacheInvalidatorService::class);
        $typesCacheInvalidatorService = $this->createMock(TypesCacheInvalidatorService::class);
        $formsCacheInvalidatorService = $this->createMock(FormsCacheInvalidatorService::class);
        $dexCacheInvalidatorService = $this->createMock(DexCacheInvalidatorService::class);
        $albumsCacheInvalidatorService = $this->createMock(AlbumsCacheInvalidatorService::class);
        $reportCacheInvalidatorService = $this->createMock(ReportsCacheInvalidatorService::class);

        $cacheInvalidator = new CacheInvalidatorService(
            $catchStatesCacheInvalidatorService,
            $typesCacheInvalidatorService,
            $formsCacheInvalidatorService,
            $dexCacheInvalidatorService,
            $albumsCacheInvalidatorService,
            $reportCacheInvalidatorService,
        );

        $this->expectException(\InvalidArgumentException::class);

        $cacheInvalidator->invalidate('douze');
    }
}
