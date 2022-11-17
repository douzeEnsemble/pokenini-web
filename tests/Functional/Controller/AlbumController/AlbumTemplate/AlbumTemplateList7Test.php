<?php

namespace App\Tests\Functional\Controller\AlbumController\AlbumTemplate;

use App\Security\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AlbumTemplateList7Test extends WebTestCase
{
    public function testDexList7Template(): void
    {
        $client = static::createClient();

        $user = new User('12');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/r/demolist7?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertCount(
            1738,
            $crawler
                ->filter('.album-case.col')
        );
        $this->assertCount(
            7,
            $crawler
                ->filter('div.row.album-line')
                ->eq(0)
                ->filter('.album-case.col')
        );
        $this->assertCount(
            7,
            $crawler
                ->filter('div.row.album-line')
                ->eq(12)
                ->filter('.album-case.col')
        );
        $this->assertCount(
            249,
            $crawler
                ->filter('div.row.album-line')
        );
        $this->assertCount(
            0,
            $crawler
                ->filter('.box')
        );
    }

    public function testFilterDexList7Template(): void
    {
        $client = static::createClient();

        $user = new User('12');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/r/demolist7/no?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertCount(
            1732,
            $crawler
                ->filter('.album-case.col')
        );
        $this->assertCount(
            7,
            $crawler
                ->filter('div.row.album-line')
                ->eq(0)
                ->filter('.album-case.col')
        );
        $this->assertCount(
            7,
            $crawler
                ->filter('div.row.album-line')
                ->eq(12)
                ->filter('.album-case.col')
        );
        $this->assertCount(
            248,
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
