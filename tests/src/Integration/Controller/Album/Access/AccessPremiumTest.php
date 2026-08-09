<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Album\Access;

use App\Controller\AlbumIndexController;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(AlbumIndexController::class)]
#[Group('api-mocked-testing')]
final class AccessPremiumTest extends WebTestCase
{
    use TestNavTrait;

    #[Test]
    public function accessPremiumAlbum(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $user->addTrainerRole();
        $user->addCollectorRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/homepokemongo');

        $this->assertResponseIsSuccessful();

        $this->assertSame(
            'Pokénini Home Pokémon Go',
            $crawler->filter('title')->text()
        );

        $this->assertSame('Home', $crawler->filter('#album-title')->text());
        $this->assertSame('Pokémon Go', $crawler->filter('#album-subtitle')->text());

        $this->assertCountFilter($crawler, 0, '.navbar-nav #share-link');
        $this->assertCountFilter($crawler, 0, '.navbar-nav #private-tag');
    }

    #[Test]
    public function trainerAccessPremiumAlbum(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/homepokemongo');

        $this->assertResponseIsSuccessful();

        $this->assertSame(
            'Pokénini Home Pokémon Go',
            $crawler->filter('title')->text()
        );

        $this->assertSame('Home', $crawler->filter('#album-title')->text());
        $this->assertSame('Pokémon Go', $crawler->filter('#album-subtitle')->text());

        $this->assertCountFilter($crawler, 0, '.navbar-nav #share-link');
        $this->assertCountFilter($crawler, 0, '.navbar-nav #private-tag');
    }

    #[Test]
    public function adminAccessPremiumAlbum(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/homepokemongo');

        $this->assertResponseIsSuccessful();

        $this->assertSame(
            'Pokénini Home Pokémon Go',
            $crawler->filter('title')->text()
        );

        $this->assertSame('Home', $crawler->filter('#album-title')->text());
        $this->assertSame('Pokémon Go', $crawler->filter('#album-subtitle')->text());

        $this->assertCountFilter($crawler, 0, '.navbar-nav #share-link');
        $this->assertCountFilter($crawler, 0, '.navbar-nav #private-tag');
    }

    #[Test]
    public function accessAnotherPremiumAlbum(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/homepokemongo?t=159bb9b6d090a313087d2f26135970c2db49ee72');

        $this->assertResponseIsSuccessful();

        $this->assertSame(
            'Pokénini Home Pokémon Go',
            $crawler->filter('title')->text()
        );

        $this->assertSame('Home', $crawler->filter('#album-title')->text());
        $this->assertSame('Pokémon Go', $crawler->filter('#album-subtitle')->text());

        $this->assertCountFilter($crawler, 0, '.navbar-nav #share-link');
        $this->assertCountFilter($crawler, 0, '.navbar-nav #private-tag');
    }
}
