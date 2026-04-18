<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\DTO\ElectionVote;
use App\Security\UserTokenService;
use App\Service\Back\BackServiceInterface;
use App\Service\Back\PostElectionVoteService;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @internal
 */
#[CoversClass(PostElectionVoteService::class)]
final class PostElectionVoteServiceTest extends AbstractTestBackService
{
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

        $filename = '/app/tests/resources/unit/service/back/election_vote_demo_whatever.json';

        /** @var PostElectionVoteService $service */
        $service = $this->getServiceWithoutLoggedUser(
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

    #[\Override]
    protected function instanciateService(
        LoggerInterface $logger,
        HttpClientInterface $client,
        string $url,
        string $cafilePath,
        UserTokenService $userTokenService,
        SerializerInterface $serializer,
    ): BackServiceInterface {
        return new PostElectionVoteService(
            $logger,
            $client,
            $url,
            $cafilePath,
            $userTokenService,
            $serializer,
        );
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
        $filename = "/app/tests/resources/unit/service/back/election_vote_{$dexSlug}_{$electionSlug}.json";

        /** @var PostElectionVoteService */
        return $this->getServiceWithLoggedUser(
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
