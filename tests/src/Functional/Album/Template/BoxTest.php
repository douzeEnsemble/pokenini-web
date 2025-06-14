<?php

declare(strict_types=1);

namespace App\Tests\Functional\Album\Template;

use App\Controller\AlbumIndexController;
use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(AlbumIndexController::class)]
class BoxTest extends WebTestCase
{
    use TestNavTrait;

    public function testDexBoxTemplate(): void
    {
        $client = static::createClient();

        $user = new User('12', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/demolite?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertCountFilter($crawler, 41, '.album-case.col');
        $this->assertCountFilter($crawler, 5, '#box-1 .album-line');
        $this->assertCountFilter($crawler, 6, 'div.row.album-line', 0, '.album-case.col');
        $this->assertCountFilter($crawler, 6, 'div.row.album-line', 2, '.album-case.col');
        $this->assertCountFilter($crawler, 2, '#box-2 .album-line');
        $this->assertCountFilter($crawler, 7, 'div.row.album-line');
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

    public function testFrenchDexBoxTemplate(): void
    {
        $client = static::createClient();

        $user = new User('12', 'TestProvider');
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

    public function testEnglishDexBoxTemplate(): void
    {
        $client = static::createClient();

        $user = new User('12', 'TestProvider');
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

    public function testFilterDexBoxTemplate(): void
    {
        $client = static::createClient();

        $user = new User('12', 'TestProvider');
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
