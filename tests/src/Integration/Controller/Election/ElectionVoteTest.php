<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Election;

use App\Controller\ElectionVoteController;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @internal
 */
#[CoversClass(ElectionVoteController::class)]
#[Group('api-mocked-testing')]
final class ElectionVoteTest extends WebTestCase
{
    use TestNavTrait;

    #[Test]
    public function vote(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/election/mega/vote');

        $this->assertResponseIsSuccessful();

        $this->assertSame("C'est quoi ton préféré ?", $crawler->filter('h1')->text());
    }

    #[Test]
    public function voteBis(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $user->addTrainerRole();
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $client->request(
            'POST',
            '/fr/election/demolite',
            [
                'winners_slugs' => ['pichu'],
                'losers_slugs' => ['pikachu', 'raichu'],
            ],
        );

        $this->assertResponseRedirects('/fr/election/demolite');

        $crawler = $client->followRedirect();

        $this->assertSame("C'est quoi ton préféré ?", $crawler->filter('h1')->text());
    }

    #[Test]
    public function voteWithElectionSlug(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $user->addTrainerRole();
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $client->request(
            'POST',
            '/fr/election/demolite/favorite',
            [
                'winners_slugs' => ['pichu'],
                'losers_slugs' => ['pikachu', 'raichu'],
            ],
        );

        $this->assertResponseRedirects('/fr/election/demolite/favorite');

        $crawler = $client->followRedirect();

        $this->assertSame("C'est quoi ton préféré ?", $crawler->filter('h1')->text());
    }

    #[Test]
    public function voteWithFilters(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $user->addTrainerRole();
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $client->request(
            'POST',
            '/fr/election/demolite/favorite?at[]=poison&at[]=fire&t1[]=&t2[]=&fc[]=&fr[]=&fs[]=&fv[]=&ogb[]=&gba[]=&gbsa[]',
            [
                'winners_slugs' => ['pichu'],
                'losers_slugs' => ['pikachu', 'raichu'],
            ],
        );

        $this->assertResponseRedirects('/fr/election/demolite/favorite?at%5B0%5D=poison&at%5B1%5D=fire');

        $crawler = $client->followRedirect();

        $this->assertSame("C'est quoi ton préféré ?", $crawler->filter('h1')->text());
    }

    #[Test]
    public function emptyVote(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $user->addTrainerRole();
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $client->request(
            'POST',
            '/fr/election/demolite',
            [],
            [],
            [],
            '',
        );

        $this->assertResponseStatusCodeSame(400);

        $content = (string) $client->getResponse()->getContent();
        $this->assertStringContainsString('Data cannot be empty', $content);
    }

    #[Test]
    public function badVote(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $user->addTrainerRole();
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $client->request(
            'POST',
            '/fr/election/demolite',
            [],
            [],
            [],
            http_build_query([
                'electionSlug' => '',
                'winnersSlugs' => ['pichu'],
                'losersSlugs' => ['pikachu', 'raichu'],
            ]),
        );

        $this->assertResponseStatusCodeSame(400);
    }

    #[Test]
    public function voteNonTrainer(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $client->loginUser($user, 'web');

        $client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);

        $client->request(
            'POST',
            '/fr/election/demolite',
            [],
            [],
            [],
            '{"winners_slugs": ["pichu"], "losers_slugs": ["pikachu", "raich"]}'
        );
    }
}
