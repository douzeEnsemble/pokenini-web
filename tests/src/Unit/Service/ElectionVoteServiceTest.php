<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\DTO\ElectionVote;
use App\Exception\ModifyFailedException;
use App\Service\Back\PostElectionVoteService;
use App\Service\ElectionVoteService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * @internal
 */
#[CoversClass(ElectionVoteService::class)]
final class ElectionVoteServiceTest extends TestCase
{
    public function testVote(): void
    {
        $electionVote = ElectionVote::createFromArray([
            'dex_slug' => 'demo',
            'election_slug' => 'whatever',
            'winners_slugs' => ['pichu'],
            'losers_slugs' => ['pikachu', 'raichu'],
        ]);

        $apiService = $this->createMock(PostElectionVoteService::class);
        $apiService
            ->expects($this->once())
            ->method('vote')
            ->with(
                $electionVote,
            )
        ;

        $service = new ElectionVoteService($apiService);
        $service->vote($electionVote);
    }

    public function testVoteWinnerAsLoser(): void
    {
        $electionVote = ElectionVote::createFromArray([
            'dex_slug' => 'demo',
            'election_slug' => 'whatever',
            'winners_slugs' => ['pichu'],
            'losers_slugs' => ['pikachu', 'pichu', 'raichu'],
        ]);

        $apiService = $this->createMock(PostElectionVoteService::class);
        $apiService
            ->expects($this->once())
            ->method('vote')
            ->with(
                $electionVote,
            )
        ;

        $service = new ElectionVoteService($apiService);
        $service->vote($electionVote);
    }

    public function testVoteAllLosers(): void
    {
        $electionVote = ElectionVote::createFromArray([
            'dex_slug' => 'demo',
            'election_slug' => 'whatever',
            'winners_slugs' => [],
            'losers_slugs' => ['pikachu', 'pichu', 'raichu'],
        ]);

        $apiService = $this->createMock(PostElectionVoteService::class);
        $apiService
            ->expects($this->once())
            ->method('vote')
            ->with(
                $electionVote,
            )
        ;

        $service = new ElectionVoteService($apiService);
        $service->vote($electionVote);
    }

    public function testVoteAllWinners(): void
    {
        $electionVote = ElectionVote::createFromArray([
            'dex_slug' => 'demo',
            'election_slug' => 'whatever',
            'winners_slugs' => ['pikachu', 'pichu', 'raichu'],
            'losers_slugs' => [],
        ]);

        $apiService = $this->createMock(PostElectionVoteService::class);
        $apiService
            ->expects($this->once())
            ->method('vote')
            ->with(
                $electionVote,
            )
        ;

        $service = new ElectionVoteService($apiService);
        $service->vote($electionVote);
    }

    public function testVoteWithHttpException(): void
    {
        $electionVote = ElectionVote::createFromArray([
            'dex_slug' => 'demo',
            'election_slug' => 'whatever',
            'winners_slugs' => ['pichu'],
            'losers_slugs' => ['pikachu'],
        ]);

        $apiService = $this->createMock(PostElectionVoteService::class);
        $apiService
            ->expects($this->once())
            ->method('vote')
            ->willThrowException($this->createStub(HttpExceptionInterface::class))
        ;

        $this->expectException(ModifyFailedException::class);

        $service = new ElectionVoteService($apiService);
        $service->vote($electionVote);
    }

    public function testVoteWithTransportException(): void
    {
        $electionVote = ElectionVote::createFromArray([
            'dex_slug' => 'demo',
            'election_slug' => 'whatever',
            'winners_slugs' => ['pichu'],
            'losers_slugs' => ['pikachu'],
        ]);

        $apiService = $this->createMock(PostElectionVoteService::class);
        $apiService
            ->expects($this->once())
            ->method('vote')
            ->willThrowException($this->createStub(TransportExceptionInterface::class))
        ;

        $this->expectException(ModifyFailedException::class);

        $service = new ElectionVoteService($apiService);
        $service->vote($electionVote);
    }
}
