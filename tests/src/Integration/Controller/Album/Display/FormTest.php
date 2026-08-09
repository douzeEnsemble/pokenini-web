<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Album\Display;

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
final class FormTest extends WebTestCase
{
    use TestNavTrait;

    #[Test]
    public function displayForm(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('12', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $client->request('GET', '/fr/album/home?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $crawler = $client->getCrawler();

        $this->assertEquals('♂️', $crawler->filter('#venusaur .album-case-forms')->text());
    }

    #[Test]
    public function nonDisplayForm(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('12', 'TestProvider');
        $user->addTrainerRole();
        $user->addCollectorRole();
        $client->loginUser($user, 'web');

        $client->request('GET', '/fr/album/homepokemongo?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $crawler = $client->getCrawler();

        $this->assertEquals(' ', $crawler->filter('#venusaur .album-case-forms')->text());
    }
}
