<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Album\Template;

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
final class NoTemplateTest extends WebTestCase
{
    use TestNavTrait;

    #[Test]
    public function dexNoDefinedTemplate(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('12', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/demonotemplate?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertCountFilter($crawler, 41, '.album-case.col');
        $this->assertCountFilter($crawler, 2, 'div.row.album-line');
        $this->assertCountFilter($crawler, 30, 'div.row.album-line', 0, '.album-case.col');
        $this->assertCountFilter($crawler, 2, '.box');
        $this->assertCountFilter($crawler, 2, '.box h2');
    }

    #[Test]
    public function filterDexNoDefinedTemplate(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('12', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/demonotemplate?cs=no&t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertCountFilter($crawler, 36, '.album-case.col');
        $this->assertCountFilter($crawler, 36, 'div.row.album-line', 0, '.album-case.col');
        $this->assertCountFilter($crawler, 1, 'div.row.album-line');

        $this->assertCountFilter($crawler, 0, '.box');
        $this->assertCountFilter($crawler, 0, '.box h2');
    }
}
