<?php

namespace App\Tests\Functional\Controller\AlbumController\AlbumTemplate;

use App\Security\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AlbumTemplateList5Test extends WebTestCase
{
    public function testDexList5Template(): void
    {
        $client = static::createClient();

        $user = new User('12');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/r/demolist5?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertCount(
            1738,
            $crawler
                ->filter('.album-case.col')
        );
        $this->assertCount(
            5,
            $crawler
                ->filter('div.row.album-line')
                ->eq(0)
                ->filter('.album-case.col')
        );
        $this->assertCount(
            5,
            $crawler
                ->filter('div.row.album-line')
                ->eq(12)
                ->filter('.album-case.col')
        );
        $this->assertCount(
            348,
            $crawler
                ->filter('div.row.album-line')
        );
        $this->assertCount(
            0,
            $crawler
                ->filter('.box')
        );
    }

    public function testFilterDexList5Template(): void
    {
        $client = static::createClient();

        $user = new User('12');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/r/demolist5/no?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertCount(
            1732,
            $crawler
                ->filter('.album-case.col')
        );
        $this->assertCount(
            5,
            $crawler
                ->filter('div.row.album-line')
                ->eq(0)
                ->filter('.album-case.col')
        );
        $this->assertCount(
            5,
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
            0,
            $crawler
                ->filter('.box')
        );
    }
}
