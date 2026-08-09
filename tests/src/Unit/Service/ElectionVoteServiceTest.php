<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\DTO\ElectionVote;
use App\Exception\ModifyFailedException;
use App\Service\Back\PostElectionVoteService;
use App\Service\ElectionVoteService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * @internal
 */
#[CoversClass(ElectionVoteService::class)]
final class ElectionVoteServiceTest extends TestCase
{
    #[Test]
    public function vote(): void
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

    #[Test]
    public function voteWinnerAsLoser(): void
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

    #[Test]
    public function voteAllLosers(): void
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

    #[Test]
    public function voteAllWinners(): void
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

    #[Test]
    public function voteWithHttpException(): void
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

    #[Test]
    public function voteWithTransportException(): void
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
