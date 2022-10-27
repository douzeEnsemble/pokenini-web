<?php

namespace App\Tests\Functional\Controller\AlbumController\AlbumTemplate;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AlbumUnkownTemplateTest extends WebTestCase
{
    public function testDexUnknownTemplate(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr/album/r/demounknowntemplate');

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
            8,
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

    public function testFilterDexUnknownTemplate(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr/album/r/demounknowntemplate/no');

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
