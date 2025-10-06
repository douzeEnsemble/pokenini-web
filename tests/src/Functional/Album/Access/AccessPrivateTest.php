<?php

declare(strict_types=1);

namespace App\Tests\Functional\Album\Access;

use App\Controller\AlbumIndexController;
use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(AlbumIndexController::class)]
class AccessPrivateTest extends WebTestCase
{
    use TestNavTrait;

    public function testAccessOwnPublicAlbum(): void
    {
        $client = static::createClient();

        $user = new User('789465465489', 'TestProvider', new AccessToken(['access_token' => sha1('789465465489')]));
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/home');

        $this->assertResponseIsSuccessful();

        $this->assertSame('Home', $crawler->filter('#album-title')->text());
        $this->assertCountFilter($crawler, 0, '#album-subtitle');

        $this->assertCountFilter($crawler, 0, '.navbar-nav #share-link');
        $this->assertCountFilter($crawler, 0, '.navbar-nav #private-tag');
        $this->assertSame('', $crawler->filter('input[name="t"]')->attr('value'));
    }

    public function testAccessOwnPrivateAlbum(): void
    {
        $client = static::createClient();

        $user = new User('789465465489', 'TestProvider', new AccessToken(['access_token' => sha1('789465465489')]));
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/demo');

        $this->assertResponseIsSuccessful();

        $this->assertSame('Démo', $crawler->filter('#album-title')->text());
        $this->assertCountFilter($crawler, 0, '#album-subtitle');

        $this->assertCountFilter($crawler, 0, '.navbar-nav #share-link');
        $this->assertCountFilter($crawler, 0, '.navbar-nav #private-tag');
        $this->assertSame('', $crawler->filter('input[name="t"]')->attr('value'));
    }

    public function testAccessAnotherPublicAlbum(): void
    {
        $client = static::createClient();

        $user = new User('789465465489', 'TestProvider', new AccessToken(['access_token' => sha1('789465465489')]));
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/demo?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertResponseIsSuccessful();

        $this->assertSame('Démo', $crawler->filter('#album-title')->text());
        $this->assertCountFilter($crawler, 0, '#album-subtitle');

        $this->assertCountFilter($crawler, 0, '.navbar-nav #share-link');
        $this->assertCountFilter($crawler, 0, '.navbar-nav #private-tag');
        $this->assertSame('7b52009b64fd0a2a49e6d8a939753077792b0554', $crawler->filter('input[name="t"]')->attr('value'));
    }

    public function testAccessAnotherPrivateAlbum(): void
    {
        $client = static::createClient();

        $user = new User('789465465489', 'TestProvider', new AccessToken(['access_token' => sha1('789465465489')]));
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $client->request('GET', '/fr/album/home?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        file_put_contents('tests/last.html', $client->getCrawler()->html());

        $this->assertResponseStatusCodeSame(404);
    }

    public function testAccessNonExistingAlbum(): void
    {
        $client = static::createClient();

        $user = new User('789465465489', 'TestProvider', new AccessToken(['access_token' => sha1('789465465489')]));
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $client->request('GET', '/fr/album/douze');

        $this->assertResponseStatusCodeSame(404);
    }
}
