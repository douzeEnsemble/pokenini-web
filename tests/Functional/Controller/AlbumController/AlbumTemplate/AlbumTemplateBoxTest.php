<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\AlbumController\AlbumTemplate;

use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AlbumTemplateBoxTest extends WebTestCase
{
    use TestNavTrait;

    public function testDexBoxTemplate(): void
    {
        $client = static::createClient();

        $user = new User('12');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/r/demo?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertCountFilter($crawler, 1738, '.album-case.col');
        $this->assertCountFilter($crawler, 5, '#box-1 .album-line');
        $this->assertCountFilter($crawler, 6, 'div.row.album-line', 0, '.album-case.col');
        $this->assertCountFilter($crawler, 6, 'div.row.album-line', 12, '.album-case.col');
        $this->assertCountFilter($crawler, 5, '#box-12 .album-line');
        $this->assertCountFilter($crawler, 290, 'div.row.album-line');
        $this->assertCountFilter($crawler, 58, '.box');
        $this->assertCountFilter($crawler, 58, '.box .box-title h2');
        $this->assertCountFilter($crawler, 58, '.box .box-title a');

        $this->assertEquals(
            '#box-1',
            $crawler
                ->filter('.box .box-title a')
                ->eq(0)
                ->attr('href')
        );
        $this->assertEquals(
            '#box-11',
            $crawler
                ->filter('.box .box-title a')
                ->eq(10)
                ->attr('href')
        );
    }

    public function testFrenchDexBoxTemplate(): void
    {
        $client = static::createClient();

        $user = new User('12');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/r/demo?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $titleBox1 = $crawler->filter('#box-1 h2');
        $this->assertEquals(
            'Boite 1',
            $titleBox1->text()
        );
        $titleBox58 = $crawler->filter('#box-58 h2');
        $this->assertEquals(
            'Boite 58',
            $titleBox58->text()
        );
    }

    public function testEnglishDexBoxTemplate(): void
    {
        $client = static::createClient();

        $user = new User('12');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/en/album/r/demo?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $titleBox1 = $crawler->filter('#box-1 h2');
        $this->assertEquals(
            'Box 1',
            $titleBox1->text()
        );
        $titleBox58 = $crawler->filter('#box-58 h2');
        $this->assertEquals(
            'Box 58',
            $titleBox58->text()
        );
    }

    public function testFilterDexBoxTemplate(): void
    {
        $client = static::createClient();

        $user = new User('12');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/r/demo/no?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertCountFilter($crawler, 1718, '.album-case.col');
        $this->assertCountFilter($crawler, 1, 'div.row.album-line');
        $this->assertCountFilter($crawler, 0, '.box');
        $this->assertCountFilter($crawler, 1, '.album-container h2');
        $this->assertEquals(' ', $crawler->filter('.album-container h2')->text());
    }
}
