<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Album\Display;

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
final class FormTest extends WebTestCase
{
    use TestNavTrait;

    public function testDisplayForm(): void
    {
        $client = self::createClient();

        $user = new User('12', 'TestProvider', new AccessToken(['access_token' => sha1('12')]));
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $client->request('GET', '/fr/album/home?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $crawler = $client->getCrawler();

        $this->assertEquals('♂️', $crawler->filter('#venusaur .album-case-forms')->text());
    }

    public function testNonDisplayForm(): void
    {
        $client = self::createClient();

        $user = new User('12', 'TestProvider', new AccessToken(['access_token' => sha1('12')]));
        $user->addTrainerRole();
        $user->addCollectorRole();
        $client->loginUser($user, 'web');

        $client->request('GET', '/fr/album/homepokemongo?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $crawler = $client->getCrawler();

        $this->assertEquals(' ', $crawler->filter('#venusaur .album-case-forms')->text());
    }
}
