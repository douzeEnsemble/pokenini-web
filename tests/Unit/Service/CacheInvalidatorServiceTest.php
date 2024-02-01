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
        $apiService
            ->expects($this->once())
            ->method('invalidateCacheTypes')
        ;
        $apiService
            ->expects($this->once())
            ->method('invalidateCacheForms')
        ;

        $cacheInvalidator = new CacheInvalidatorService($apiService);

        $cacheInvalidator->invalidate('labels');
    }

    public function testInvalidateCatchStates(): void
    {
        $apiService = $this->createMock(ApiService::class);

        $cacheInvalidator = new CacheInvalidatorService($apiService);

        $this->expectException(\InvalidArgumentException::class);

        $cacheInvalidator->invalidate('catch_states');
    }

    public function testInvalidateTypes(): void
    {
        $apiService = $this->createMock(ApiService::class);

        $cacheInvalidator = new CacheInvalidatorService($apiService);

        $this->expectException(\InvalidArgumentException::class);

        $cacheInvalidator->invalidate('types');
    }

    public function testInvalidateGamesAndDex(): void
    {
        $apiService = $this->createMock(ApiService::class);

        $apiService
            ->expects($this->once())
            ->method('invalidateCacheDex')
        ;

        $cacheInvalidator = new CacheInvalidatorService($apiService);

        $cacheInvalidator->invalidate('games_and_dex');
    }

    public function testInvalidatePokemons(): void
    {
        $apiService = $this->createMock(ApiService::class);


        $cacheInvalidator = new CacheInvalidatorService($apiService);

        $cacheInvalidator->invalidate('pokemons');

        // There is no action, but no exception either
        $this->assertTrue(true);
    }

    public function testInvalidateGameAvailability(): void
    {
        $apiService = $this->createMock(ApiService::class);

        $apiService
            ->expects($this->once())
            ->method('invalidateCacheAlbums')
        ;

        $cacheInvalidator = new CacheInvalidatorService($apiService);

        $cacheInvalidator->invalidate('games_availabilities');
    }

    public function testInvalidateGameShinyAvailability(): void
    {
        $apiService = $this->createMock(ApiService::class);

        $apiService
            ->expects($this->once())
            ->method('invalidateCacheAlbums')
        ;

        $cacheInvalidator = new CacheInvalidatorService($apiService);

        $cacheInvalidator->invalidate('games_shinies_availabilities');
    }

    public function testInvalidateGameBundleAvailability(): void
    {
        $apiService = $this->createMock(ApiService::class);

        $apiService
            ->expects($this->once())
            ->method('invalidateCacheAlbums')
        ;

        $cacheInvalidator = new CacheInvalidatorService($apiService);

        $cacheInvalidator->invalidate('game_bundles_availabilities');
    }

    public function testInvalidateGameBundleShinyAvailability(): void
    {
        $apiService = $this->createMock(ApiService::class);

        $apiService
            ->expects($this->once())
            ->method('invalidateCacheAlbums')
        ;

        $cacheInvalidator = new CacheInvalidatorService($apiService);

        $cacheInvalidator->invalidate('game_bundles_shinies_availabilities');
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
            ->method('invalidateCacheDex')
        ;

        $cacheInvalidator = new CacheInvalidatorService($apiService);

        $cacheInvalidator->invalidate('dex_availabilities');
    }

    public function testInvalidateReports(): void
    {
        $apiService = $this->createMock(ApiService::class);

        $apiService
            ->expects($this->once())
            ->method('invalidateCacheReports')
        ;

        $cacheInvalidator = new CacheInvalidatorService($apiService);

        $cacheInvalidator->invalidate('reports');
    }

    public function testInvalidateUnknown(): void
    {
        $apiService = $this->createMock(ApiService::class);

        $cacheInvalidator = new CacheInvalidatorService($apiService);

        $this->expectException(\InvalidArgumentException::class);

        $cacheInvalidator->invalidate('douze');
    }
}
