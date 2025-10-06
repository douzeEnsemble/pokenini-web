<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\DTO\ElectionVote;
use App\Service\Back\PostElectionVoteService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(PostElectionVoteService::class)]
class PostElectionVoteServiceTest extends TestCase
{
    use BackServiceTrait;

    public const ENDPOINT = 'election/vote';

    public function testVote(): void
    {
        $electionVote = new ElectionVote([
            'dex_slug' => 'demo',
            'election_slug' => 'whatever',
            'winners_slugs' => ['pichu'],
            'losers_slugs' => ['pikachu', 'raichu'],
        ]);

        $this
            ->getService('demo', 'whatever', ['pichu'], ['pikachu', 'raichu'])
            ->vote(
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
            ->getService('demo', 'whatever', [], ['pikachu', 'pichu', 'raichu'])
            ->vote(
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
            ->getService('demo', 'whatever', ['pikachu', 'pichu', 'raichu'], [])
            ->vote(
                $electionVote,
            )
        ;
    }

    public function testVoteWithoutLoggedUser(): void
    {
        $electionVote = new ElectionVote([
            'dex_slug' => 'demo',
            'election_slug' => 'whatever',
            'winners_slugs' => ['pichu'],
            'losers_slugs' => ['pikachu', 'raichu'],
        ]);

        $filename = '/var/www/html/tests/resources/unit/service/back/election_vote_demo_whatever.json';

        /** @var PostElectionVoteService $service */
        $service = $this->getServiceWithoutLoggedUser(
            PostElectionVoteService::class,
            'POST',
            (string) file_get_contents($filename),
            self::ENDPOINT,
            [
                'body' => (string) json_encode([
                    'dex_slug' => 'demo',
                    'election_slug' => 'whatever',
                    'winners_slugs' => ['pichu'],
                    'losers_slugs' => ['pikachu', 'raichu'],
                ]),
            ]
        );

        $service->vote($electionVote);
    }

    /**
     * @param string[] $winnersSlugs
     * @param string[] $losersSlugs
     */
    private function getService(
        string $dexSlug,
        string $electionSlug,
        array $winnersSlugs,
        array $losersSlugs,
    ): PostElectionVoteService {
        $filename = "/var/www/html/tests/resources/unit/service/back/election_vote_{$dexSlug}_{$electionSlug}.json";

        /** @var PostElectionVoteService */
        return $this->getServiceWithLoggedUser(
            PostElectionVoteService::class,
            'POST',
            (string) file_get_contents($filename),
            self::ENDPOINT,
            [
                'body' => (string) json_encode([
                    'dex_slug' => $dexSlug,
                    'election_slug' => $electionSlug,
                    'winners_slugs' => $winnersSlugs,
                    'losers_slugs' => $losersSlugs,
                ]),
            ]
        );
    }
}
