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
final class TrainerTest extends WebTestCase
{
    use TestNavTrait;

    #[Test]
    public function albumTrainerLogged(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/demo');

        $this->assertResponseIsSuccessful();

        $this->assertCountFilter($crawler, 1741, '.album-case');

        $this->assertCountFilter($crawler, 0, '.another-trainer-album');

        $this->assertSame('', $crawler->filter('input[name="t"]')->attr('value'));
    }

    #[Test]
    public function albumTrainerGiven(): void
    {
        $client = self::createClient();

        $crawler = $client->request('GET', '/fr/album/demo?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertResponseIsSuccessful();

        $this->assertCountFilter($crawler, 25, '.album-case');

        $this->assertCountFilter($crawler, 1, '.another-trainer-album');

        $this->assertSame('7b52009b64fd0a2a49e6d8a939753077792b0554', $crawler->filter('input[name="t"]')->attr('value'));
    }

    #[Test]
    public function albumTrainerLoggedAndGiven(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/demo?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertResponseIsSuccessful();

        $this->assertCountFilter($crawler, 25, '.album-case');

        $this->assertCountFilter($crawler, 1, '.another-trainer-album');

        $this->assertSame('7b52009b64fd0a2a49e6d8a939753077792b0554', $crawler->filter('input[name="t"]')->attr('value'));
    }
}
