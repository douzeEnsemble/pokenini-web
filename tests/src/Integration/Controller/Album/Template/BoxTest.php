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
final class BoxTest extends WebTestCase
{
    use TestNavTrait;

    #[Test]
    public function dexBoxTemplate(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('12', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/demolite?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertCountFilter($crawler, 41, '.album-case.col');
        $this->assertCountFilter($crawler, 2, 'div.row.album-line');
        $this->assertCountFilter($crawler, 1, '#box-1 .album-line');
        $this->assertCountFilter($crawler, 30, 'div.row.album-line', 0, '.album-case.col');
        $this->assertCountFilter($crawler, 1, '#box-2 .album-line');
        $this->assertCountFilter($crawler, 2, '.box');
        $this->assertCountFilter($crawler, 2, '.box .box-title h2');
        $this->assertCountFilter($crawler, 4, '.box .box-title a');

        $this->assertEquals(
            '#box-1',
            $crawler
                ->filter('.box .box-title a')
                ->eq(0)
                ->attr('href')
        );
        $this->assertEquals(
            '#',
            $crawler
                ->filter('.box .box-title a')
                ->eq(1)
                ->attr('href')
        );
        $this->assertEquals(
            '#box-2',
            $crawler
                ->filter('.box .box-title a')
                ->eq(2)
                ->attr('href')
        );
        $this->assertEquals(
            '#',
            $crawler
                ->filter('.box .box-title a')
                ->eq(3)
                ->attr('href')
        );
    }

    #[Test]
    public function frenchDexBoxTemplate(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('12', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/demolite?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertEquals(
            'Boite 1',
            $crawler->filter('#box-1 h2')->text()
        );
        $this->assertEquals(
            'Boite 2',
            $crawler->filter('#box-2 h2')->text()
        );
    }

    #[Test]
    public function englishDexBoxTemplate(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('12', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/en/album/demolite?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertEquals(
            'Box 1',
            $crawler->filter('#box-1 h2')->text()
        );
        $this->assertEquals(
            'Box 2',
            $crawler->filter('#box-2 h2')->text()
        );
    }

    #[Test]
    public function filterDexBoxTemplate(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('12', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/demolite?cs=no&t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertCountFilter($crawler, 35, '.album-case.col');
        $this->assertCountFilter($crawler, 1, 'div.row.album-line');
        $this->assertCountFilter($crawler, 0, '.box');
        $this->assertCountFilter($crawler, 1, '.album-container h2');
        $this->assertEquals(' ', $crawler->filter('.album-container h2')->text());
    }
}
