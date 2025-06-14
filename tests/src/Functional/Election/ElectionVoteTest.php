<?php

declare(strict_types=1);

namespace App\Tests\Functional\Election;

use App\Controller\ElectionVoteController;
use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @internal
 */
#[CoversClass(ElectionVoteController::class)]
class ElectionVoteTest extends WebTestCase
{
    use TestNavTrait;

    public function testVote(): void
    {
        $client = static::createClient();

        $user = new User('789465465489', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/election/mega/vote');

        $this->assertResponseIsSuccessful();

        $this->assertSame("C'est quoi ton préféré ?", $crawler->filter('h1')->text());
    }

    public function testVoteBis(): void
    {
        $client = static::createClient();

        $user = new User('8764532', 'TestProvider');
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

    public function testVoteWithElectionSlug(): void
    {
        $client = static::createClient();

        $user = new User('8764532', 'TestProvider');
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

    public function testVoteWithFilters(): void
    {
        $client = static::createClient();

        $user = new User('8764532', 'TestProvider');
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

    public function testEmptyVote(): void
    {
        $client = static::createClient();

        $user = new User('8764532', 'TestProvider');
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

    public function testBadVote(): void
    {
        $client = static::createClient();

        $user = new User('8764532', 'TestProvider');
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

    public function testVoteNonTrainer(): void
    {
        $client = static::createClient();

        $user = new User('8764532', 'TestProvider');
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
