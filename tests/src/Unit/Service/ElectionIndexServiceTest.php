<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\DTO\ElectionIndexData;
use App\ResponseObject\Election\ElectionIndex;
use App\Service\Back\GetElectionIndexService;
use App\Service\ElectionIndexService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * @internal
 */
#[CoversClass(ElectionIndexService::class)]
final class ElectionIndexServiceTest extends TestCase
{
    #[Test]
    public function get(): void
    {
        $dexSlug = 'test-dex';
        $electionSlug = 'test-election';
        $filters = ['type' => 'bug'];

        $electionIndexMock = new ElectionIndex(
            'pick',
            [],
            null,
            [],
            [
                'view_count' => ['sum' => 10, 'max' => 15],
                'win_count' => ['sum' => 5, 'max' => 8],
                'completion' => ['under_max_count' => 3, 'at_max_count' => 20],
                'dex_total_count' => 150,
                'round_count' => 2,
                'winner_average' => 0.5,
                'total_round_count' => 5,
            ],
            1,
            true,
            false
        );

        $apiServiceMock = $this->createMock(GetElectionIndexService::class);
        $apiServiceMock
            ->expects($this->once())
            ->method('get')
            ->with($dexSlug, $electionSlug, $filters)
            ->willReturn($electionIndexMock)
        ;

        $service = new ElectionIndexService($apiServiceMock);

        $result = $service->get($dexSlug, $electionSlug, $filters);

        $this->assertInstanceOf(ElectionIndexData::class, $result);

        $this->assertSame('pick', $result->listType);
        $this->assertSame([], $result->pokemons);
        $this->assertNull($result->pokedex);
        $this->assertSame(1, $result->detachedCount);
        $this->assertTrue($result->isTheLastOne);
        $this->assertFalse($result->isTheLastPage);
    }

    #[Test]
    public function getHttpException(): void
    {
        $dexSlug = 'test-dex';
        $electionSlug = 'test-election';
        $filters = ['type' => 'bug'];

        $exception = $this->createStub(HttpExceptionInterface::class);

        $apiServiceMock = $this->createMock(GetElectionIndexService::class);
        $apiServiceMock
            ->expects($this->once())
            ->method('get')
            ->with($dexSlug, $electionSlug, $filters)
            ->willThrowException($exception)
        ;

        $service = new ElectionIndexService($apiServiceMock);

        $result = $service->get($dexSlug, $electionSlug, $filters);

        $this->assertNull($result);
    }

    #[Test]
    public function getTransportException(): void
    {
        $dexSlug = 'test-dex';
        $electionSlug = 'test-election';
        $filters = ['type' => 'bug'];

        $exception = $this->createStub(TransportExceptionInterface::class);

        $apiServiceMock = $this->createMock(GetElectionIndexService::class);
        $apiServiceMock
            ->expects($this->once())
            ->method('get')
            ->with($dexSlug, $electionSlug, $filters)
            ->willThrowException($exception)
        ;

        $service = new ElectionIndexService($apiServiceMock);

        $result = $service->get($dexSlug, $electionSlug, $filters);

        $this->assertNull($result);
    }
}
