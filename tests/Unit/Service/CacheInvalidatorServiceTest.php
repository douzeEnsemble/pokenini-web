<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\ApiService;
use App\Service\CacheInvalidatorService;
use PHPUnit\Framework\TestCase;

class CacheInvalidatorServiceTest extends TestCase
{
    public function testInvalidateLabels(): void
    {
        $apiService = $this->createMock(ApiService::class);

        $apiService
            ->expects($this->once())
            ->method('invalidateCacheCatchStates')
        ;

        $cacheInvalidator = new CacheInvalidatorService($apiService);

        $cacheInvalidator->invalidate('labels');
    }

    public function testInvalidateGamesAndDexes(): void
    {
        $apiService = $this->createMock(ApiService::class);

        $apiService
            ->expects($this->once())
            ->method('invalidateCacheDexes')
        ;

        $cacheInvalidator = new CacheInvalidatorService($apiService);

        $cacheInvalidator->invalidate('games_and_dexes');
    }

    public function testInvalidatePokemons(): void
    {
        $apiService = $this->createMock(ApiService::class);

        $cacheInvalidator = new CacheInvalidatorService($apiService);

        $cacheInvalidator->invalidate('pokemons');

        // There is no action, but no exception either
        $this->assertTrue(true);
    }

    public function testInvalidateGameBundleAvailability(): void
    {
        $apiService = $this->createMock(ApiService::class);

        $apiService
            ->expects($this->once())
            ->method('invalidateCacheAlbums')
        ;

        $cacheInvalidator = new CacheInvalidatorService($apiService);

        $cacheInvalidator->invalidate('game_bundle_availability');
    }

    public function testInvalidateDexAvailability(): void
    {
        $apiService = $this->createMock(ApiService::class);

        $apiService
            ->expects($this->once())
            ->method('invalidateCacheAlbums')
        ;
        $apiService
            ->expects($this->once())
            ->method('invalidateCacheDexes')
        ;

        $cacheInvalidator = new CacheInvalidatorService($apiService);

        $cacheInvalidator->invalidate('dex_availability');
    }

    public function testInvalidateUnknown(): void
    {
        $apiService = $this->createMock(ApiService::class);

        $cacheInvalidator = new CacheInvalidatorService($apiService);

        $this->expectException(\InvalidArgumentException::class);

        $cacheInvalidator->invalidate('douze');
    }
}
