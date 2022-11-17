<?php

namespace App\Tests\Functional\Controller\AlbumController\AlbumTemplate;

use App\Security\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AlbumNoTemplateTest extends WebTestCase
{
    public function testDexNoDefinedTemplate(): void
    {
        $client = static::createClient();

        $user = new User('12');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/r/demonotemplate?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertCount(
            37,
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
                ->eq(2)
                ->filter('.album-case.col')
        );
        $this->assertCount(
            7,
            $crawler
                ->filter('div.row.album-line')
        );
        $this->assertCount(
            2,
            $crawler
                ->filter('.box')
        );
        $this->assertCount(
            2,
            $crawler
                ->filter('.box h2')
        );
    }

    public function testFilterDexNoDefinedTemplate(): void
    {
        $client = static::createClient();

        $user = new User('12');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/r/demonotemplate/no?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertCount(
            21,
            $crawler
                ->filter('.album-case.col')
        );
        $this->assertCount(
            21,
            $crawler
                ->filter('div.row.album-line')
                ->eq(0)
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
            0,
            $crawler
                ->filter('.box h2')
        );
    }
}
