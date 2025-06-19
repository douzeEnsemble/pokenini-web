<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Api;

use App\DTO\ElectionVote;
use App\Service\Api\ElectionVoteApiService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal
 */
#[CoversClass(ElectionVoteApiService::class)]
class ElectionVoteApiServiceTest extends TestCase
{
    private ArrayAdapter $cachePool;
    private TagAwareAdapter $cache;

    public function testVote(): void
    {
        $electionVote = new ElectionVote([
            'dex_slug' => 'demo',
            'election_slug' => 'whatever',
            'winners_slugs' => ['pichu'],
            'losers_slugs' => ['pikachu', 'raichu'],
        ]);

        $this
            ->getService('5465465', 'demo', 'whatever', ['pichu'], ['pikachu', 'raichu'])
            ->vote(
                '5465465',
                $electionVote,
            )
        ;
    }

    public function testVoteAllLosers(): void
    {
        $electionVote = new ElectionVote([
            'dex_slug' => 'demo',
            'election_slug' => 'whatever',
            'winners_slugs' => [],
            'losers_slugs' => ['pikachu', 'pichu', 'raichu'],
        ]);

        $this
            ->getService('5465465', 'demo', 'whatever', [], ['pikachu', 'pichu', 'raichu'])
            ->vote(
                '5465465',
                $electionVote,
            )
        ;
    }

    public function testVoteAllWinners(): void
    {
        $electionVote = new ElectionVote([
            'dex_slug' => 'demo',
            'election_slug' => 'whatever',
            'winners_slugs' => ['pikachu', 'pichu', 'raichu'],
            'losers_slugs' => [],
        ]);

        $this
            ->getService('5465465', 'demo', 'whatever', ['pikachu', 'pichu', 'raichu'], [])
            ->vote(
                '5465465',
                $electionVote,
            )
        ;

        $this->assertEmpty($this->cachePool->getValues());
    }

    /**
     * @param string[] $winnersSlugs
     * @param string[] $losersSlugs
     */
    private function getService(
        string $trainerId,
        string $dexSlug,
        string $electionSlug,
        array $winnersSlugs,
        array $losersSlugs,
    ): ElectionVoteApiService {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->exactly(2))
            ->method('info')
        ;

        $client = $this->createMock(HttpClientInterface::class);

        $json = (string) file_get_contents("/var/www/html/tests/resources/unit/service/api/election_vote_{$trainerId}_{$dexSlug}_{$electionSlug}.json");

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->exactly(2))
            ->method('getContent')
            ->willReturn($json)
        ;

        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'https://api.domain/election/vote',
                [
                    'headers' => [
                        'accept' => 'application/json',
                    ],
                    'auth_basic' => [
                        'web',
                        'douze',
                    ],
                    'cafile' => './resources/certificates/cacert.pem',
                    'body' => json_encode([
                        'trainer_external_id' => $trainerId,
                        'dex_slug' => $dexSlug,
                        'election_slug' => $electionSlug,
                        'winners_slugs' => $winnersSlugs,
                        'losers_slugs' => $losersSlugs,
                    ]),
                ],
            )
            ->willReturn($response)
        ;

        $this->cachePool = new ArrayAdapter();
        $this->cache = new TagAwareAdapter($this->cachePool, new ArrayAdapter());

        return new ElectionVoteApiService(
            $logger,
            $client,
            'https://api.domain',
            './resources/certificates/cacert.pem',
            $this->cache,
            'web',
            'douze',
        );
    }
}
