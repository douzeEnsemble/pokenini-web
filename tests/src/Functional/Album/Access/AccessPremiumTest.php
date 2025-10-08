<?php

declare(strict_types=1);

namespace App\Tests\Functional\Album\Access;

use App\Controller\AlbumIndexController;
use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(AlbumIndexController::class)]
#[Group('api-mocked-testing')]
class AccessPremiumTest extends WebTestCase
{
    use TestNavTrait;

    public function testAccessPremiumAlbum(): void
    {
        $client = static::createClient();

        $user = new User('8764532', 'TestProvider', new AccessToken(['access_token' => sha1('8764532')]));
        $user->addTrainerRole();
        $user->addCollectorRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/homepokemongo');

        $this->assertResponseIsSuccessful();

        $this->assertSame('Home', $crawler->filter('#album-title')->text());
        $this->assertSame('Pokémon Go', $crawler->filter('#album-subtitle')->text());

        $this->assertCountFilter($crawler, 0, '.navbar-nav #share-link');
        $this->assertCountFilter($crawler, 0, '.navbar-nav #private-tag');
    }

    public function testTrainerAccessPremiumAlbum(): void
    {
        $client = static::createClient();

        $user = new User('8764532', 'TestProvider', new AccessToken(['access_token' => sha1('8764532')]));
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/homepokemongo');

        $this->assertResponseIsSuccessful();

        $this->assertSame('Home', $crawler->filter('#album-title')->text());
        $this->assertSame('Pokémon Go', $crawler->filter('#album-subtitle')->text());

        $this->assertCountFilter($crawler, 0, '.navbar-nav #share-link');
        $this->assertCountFilter($crawler, 0, '.navbar-nav #private-tag');
    }

    public function testAdminAccessPremiumAlbum(): void
    {
        $client = static::createClient();

        $user = new User('8764532', 'TestProvider', new AccessToken(['access_token' => sha1('8764532')]));
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/homepokemongo');

        $this->assertResponseIsSuccessful();

        $this->assertSame('Home', $crawler->filter('#album-title')->text());
        $this->assertSame('Pokémon Go', $crawler->filter('#album-subtitle')->text());

        $this->assertCountFilter($crawler, 0, '.navbar-nav #share-link');
        $this->assertCountFilter($crawler, 0, '.navbar-nav #private-tag');
    }

    public function testAccessAnotherPremiumAlbum(): void
    {
        $client = static::createClient();

        $user = new User('8764532', 'TestProvider', new AccessToken(['access_token' => sha1('8764532')]));
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/homepokemongo?t=159bb9b6d090a313087d2f26135970c2db49ee72');

        $this->assertResponseIsSuccessful();

        $this->assertSame('Home', $crawler->filter('#album-title')->text());
        $this->assertSame('Pokémon Go', $crawler->filter('#album-subtitle')->text());

        $this->assertCountFilter($crawler, 0, '.navbar-nav #share-link');
        $this->assertCountFilter($crawler, 0, '.navbar-nav #private-tag');
    }
}
