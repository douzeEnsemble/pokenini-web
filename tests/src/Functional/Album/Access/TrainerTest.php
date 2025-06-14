<?php

declare(strict_types=1);

namespace App\Tests\Functional\Album\Access;

use App\Controller\AlbumIndexController;
use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(AlbumIndexController::class)]
class TrainerTest extends WebTestCase
{
    use TestNavTrait;

    public function testAlbumTrainerLogged(): void
    {
        $client = static::createClient();

        $user = new User('789465465489', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/demo');

        $this->assertResponseIsSuccessful();

        $this->assertCountFilter($crawler, 12, '.album-case');

        $this->assertCountFilter($crawler, 0, '.another-trainer-album');

        $this->assertSame('', $crawler->filter('input[name="t"]')->attr('value'));
    }

    public function testAlbumTrainerGiven(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr/album/demo?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertResponseIsSuccessful();

        $this->assertCountFilter($crawler, 25, '.album-case');

        $this->assertCountFilter($crawler, 1, '.another-trainer-album');

        $this->assertSame('7b52009b64fd0a2a49e6d8a939753077792b0554', $crawler->filter('input[name="t"]')->attr('value'));
    }

    public function testAlbumTrainerLoggedAndGiven(): void
    {
        $client = static::createClient();

        $user = new User('789465465489', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/demo?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertResponseIsSuccessful();

        $this->assertCountFilter($crawler, 25, '.album-case');

        $this->assertCountFilter($crawler, 1, '.another-trainer-album');

        $this->assertSame('7b52009b64fd0a2a49e6d8a939753077792b0554', $crawler->filter('input[name="t"]')->attr('value'));
    }
}
