<?php

namespace App\Tests\Functional\Controller\AlbumController\AlbumTemplate;

use App\Security\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AlbumTemplateBoxTest extends WebTestCase
{
    public function testDexBoxTemplate(): void
    {
        $client = static::createClient();

        $user = new User('12');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/r/demo?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertCount(
            1738,
            $crawler
                ->filter('.album-case.col')
        );
        $this->assertCount(
            6,
            $crawler
                ->filter('div.row.album-line')
                ->eq(0)
                ->filter('.album-case.col')
        );
        $this->assertCount(
            6,
            $crawler
                ->filter('div.row.album-line')
                ->eq(12)
                ->filter('.album-case.col')
        );
        $this->assertCount(
            347,
            $crawler
                ->filter('div.row.album-line')
        );
        $this->assertCount(
            58,
            $crawler
                ->filter('.box')
        );
        $this->assertCount(
            58,
            $crawler
                ->filter('.box h2')
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

        $this->assertCount(
            1718,
            $crawler
                ->filter('.album-case.col')
        );
        $this->assertCount(
            1,
            $crawler
                ->filter('div.row.album-line')
        );
        $this->assertCount(
            0,
            $crawler
                ->filter('.box')
        );
        $this->assertCount(
            1,
            $crawler
                ->filter('.album-container h2')
        );
        $this->assertEquals(' ', $crawler->filter('.album-container h2')->text());
    }
}
