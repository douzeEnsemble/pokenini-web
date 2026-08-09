<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\DTO\ElectionVote;
use App\Security\UserTokenServiceInterface;
use App\Service\Back\AbstractBackService;
use App\Service\Back\PostElectionVoteService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @internal
 */
#[CoversClass(PostElectionVoteService::class)]
final class PostElectionVoteServiceTest extends AbstractTestBackService
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

        $this
            ->getService('demo', 'whatever', ['pichu'], ['pikachu', 'raichu'])
            ->vote(
                $electionVote,
            )
        ;
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

        $this
            ->getService('demo', 'whatever', [], ['pikachu', 'pichu', 'raichu'])
            ->vote(
                $electionVote,
            )
        ;
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

        $this
            ->getService('demo', 'whatever', ['pikachu', 'pichu', 'raichu'], [])
            ->vote(
                $electionVote,
            )
        ;
    }

    #[Test]
    public function voteWithoutLoggedUser(): void
    {
        $electionVote = ElectionVote::createFromArray([
            'dex_slug' => 'demo',
            'election_slug' => 'whatever',
            'winners_slugs' => ['pichu'],
            'losers_slugs' => ['pikachu', 'raichu'],
        ]);

        $filename = '/app/tests/resources/unit/service/back/election_vote_demo_whatever.json';

        /** @var PostElectionVoteService $service */
        $service = $this->getServiceWithoutLoggedUser(
            'POST',
            (new Filesystem())->readFile($filename),
            'election/demo/whatever',
            [
                'json' => [
                    'winners_slugs' => ['pichu'],
                    'losers_slugs' => ['pikachu', 'raichu'],
                ],
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
        UserTokenServiceInterface $userTokenService,
        SerializerInterface $serializer,
    ): AbstractBackService {
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
            (new Filesystem())->readFile($filename),
            "election/{$dexSlug}/{$electionSlug}",
            [
                'json' => [
                    'winners_slugs' => $winnersSlugs,
                    'losers_slugs' => $losersSlugs,
                ],
            ]
        );
    }
}
