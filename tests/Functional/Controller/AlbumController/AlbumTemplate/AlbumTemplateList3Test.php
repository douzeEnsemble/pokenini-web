<?php

namespace App\Tests\Functional\Controller\AlbumController\AlbumTemplate;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AlbumTemplateList3Test extends WebTestCase
{
    public function testDexList3Template(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr/album/r/demolist3?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertCount(
            1738,
            $crawler
                ->filter('.album-case.col')
        );
        $this->assertCount(
            3,
            $crawler
                ->filter('div.row.album-line')
                ->eq(0)
                ->filter('.album-case.col')
        );
        $this->assertCount(
            3,
            $crawler
                ->filter('div.row.album-line')
                ->eq(12)
                ->filter('.album-case.col')
        );
        $this->assertCount(
            580,
            $crawler
                ->filter('div.row.album-line')
        );
        $this->assertCount(
            0,
            $crawler
                ->filter('.box')
        );
    }

    public function testFilterDexList3Template(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr/album/r/demolist3/no?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertCount(
            1732,
            $crawler
                ->filter('.album-case.col')
        );
        $this->assertCount(
            3,
            $crawler
                ->filter('div.row.album-line')
                ->eq(0)
                ->filter('.album-case.col')
        );
        $this->assertCount(
            3,
            $crawler
                ->filter('div.row.album-line')
                ->eq(12)
                ->filter('.album-case.col')
        );
        $this->assertCount(
            578,
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
