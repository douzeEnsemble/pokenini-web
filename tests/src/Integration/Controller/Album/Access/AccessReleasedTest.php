<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Album\Access;

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
final class AccessReleasedTest extends WebTestCase
{
    use TestNavTrait;

    public function testAccessOwnReleasedAlbum(): void
    {
        $client = self::createClient();

        $user = new User('8764532', 'TestProvider', new AccessToken(['access_token' => sha1('8764532')]));
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/home');

        $this->assertResponseIsSuccessful();

        $this->assertSame('Home', $crawler->filter('#album-title')->text());
        $this->assertCountFilter($crawler, 0, '#album-subtitle');

        $this->assertCountFilter($crawler, 0, '.navbar-nav #share-link');
        $this->assertCountFilter($crawler, 0, '.navbar-nav #private-tag');
    }

    public function testTrainerAccessUnreleasedAlbum(): void
    {
        $client = self::createClient();

        $user = new User('8764532', 'TestProvider', new AccessToken(['access_token' => sha1('8764532')]));
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $client->request('GET', '/fr/album/goldsilvercrystal');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testAdminAccessUnreleasedAlbum(): void
    {
        $client = self::createClient();

        $user = new User('8764532', 'TestProvider', new AccessToken(['access_token' => sha1('8764532')]));
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/goldsilvercrystal');

        $this->assertCountFilter($crawler, 0, '.navbar-nav #share-link');
        $this->assertCountFilter($crawler, 0, '.navbar-nav #private-tag');
    }

    public function testAccessAnotherUnreleasedAlbum(): void
    {
        $client = self::createClient();

        $user = new User('8764532', 'TestProvider', new AccessToken(['access_token' => sha1('8764532')]));
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/goldsilvercrystal?t=159bb9b6d090a313087d2f26135970c2db49ee72');

        $this->assertCountFilter($crawler, 0, '.navbar-nav #share-link');
        $this->assertCountFilter($crawler, 0, '.navbar-nav #private-tag');
    }
}
