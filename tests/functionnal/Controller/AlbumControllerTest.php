<?php

namespace functionnal\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AlbumControllerTest extends WebTestCase
{
    public function testListPrivate(): void
    {
        $client = static::createClient();

        $client->request('GET', '/album/demo?token=cb19dc668f0c426c8f3e319f9ea36ecc');

        $this->assertAlbum($client);

        $mainCrawler = $client->getCrawler();

        $this->assertEquals(
            1736,
            $mainCrawler
                ->filter('.album-case select')
                ->count()
        );

        $options = $mainCrawler->filter('#bulbasaur select option');
        $this->assertEquals(5, $options->count());
    }

    public function testListPublic(): void
    {
        $client = static::createClient();

        $client->request('GET', '/album/demo');

        $this->assertAlbum($client);

        $mainCrawler = $client->getCrawler();

        $this->assertEquals(
            0,
            $mainCrawler
                ->filter('.album-case select')
                ->count()
        );
    }

    public function testListWrongToken(): void
    {
        $client = static::createClient();

        $client->request('GET', '/album/demo?token=kadkjazpdazpdi');

        $this->assertAlbum($client);

        $mainCrawler = $client->getCrawler();

        $this->assertEquals(
            0,
            $mainCrawler
                ->filter('.album-case select')
                ->count()
        );
    }

    public function testUpdatePrivate(): void
    {
        $client = static::createClient();

        $client->request(
            'PATCH',
            '/album/demo/bulbasaur?token=cb19dc668f0c426c8f3e319f9ea36ecc',
            [
                'body' => 'yes',
            ]
        );

        $this->assertResponseIsSuccessful();
    }

    public function testUpdatePublic(): void
    {
        $client = static::createClient();

        $client->request(
            'PATCH',
            '/album/demo/bulbasaur',
            [
                'body' => 'yes',
            ]
        );


        $this->assertEquals(403, $client->getResponse()->getStatusCode());
    }

    public function testUpdateWrongToken(): void
    {
        $client = static::createClient();

        $client->request(
            'PATCH',
            '/album/demo/bulbasaur?token=kadkjazpdazpdi',
            [
                'body' => 'yes',
            ]
        );

        $this->assertEquals(403, $client->getResponse()->getStatusCode());
    }

    private function assertAlbum(KernelBrowser $client): void
    {
        $this->assertResponseIsSuccessful();
        $this->assertPageTitleSame('Pokénini Demo');

        $mainCrawler = $client->getCrawler();

        $expectedPokemonCount = 1736;

        $this->assertEquals(
            $expectedPokemonCount,
            $mainCrawler->filter('.album-case')->count()
        );

        $icon = $mainCrawler->filter('#bulbasaur .album-case-image img');
        $this->assertEquals(
            'https://raw.githubusercontent.com/msikma/pokesprite/master/pokemon-gen8/regular/bulbasaur.png',
            $icon->attr('src')
        );

        $label = $mainCrawler->filter('#bulbasaur .album-case-name');
        $this->assertEquals(
            'Bulbasaur',
            $label->text()
        );

        $this->assertEquals(
            1,
            $mainCrawler
                ->filter('#bulbasaur.album-case.catch-state-no')->count()
        );
        $this->assertEquals(
            1,
            $mainCrawler
                ->filter('#ivysaur.album-case.catch-state-no')
                ->count()
        );
        $this->assertEquals(
            1,
            $mainCrawler
                ->filter('#venusaur.album-case.catch-state-toevolve')
                ->count()
        );
        $this->assertEquals(
            1,
            $mainCrawler
                ->filter('#venusaur-f.album-case.catch-state-tobreed')
                ->count()
        );
        $this->assertEquals(
            1,
            $mainCrawler
                ->filter('#venusaur-mega.album-case.catch-state-totransfer')
                ->count()
        );
        $this->assertEquals(
            1,
            $mainCrawler
                ->filter('#venusaur-gmax.album-case.catch-state-yes')
                ->count()
        );

        $this->assertStringContainsString('const catchStateClassColors = JSON.parse', $mainCrawler->outerHtml());
        $this->assertStringContainsString('watchCatchStates();', $mainCrawler->outerHtml());

        $this->assertEquals(
            $expectedPokemonCount,
            $mainCrawler
                ->filter('.album-case.col-2')
                ->count()
        );
        $this->assertEquals(
            290,
            $mainCrawler
                ->filter('div.row')
                ->count()
        );
        $this->assertEquals(
            58,
            $mainCrawler
                ->filter('h2')
                ->count()
        );
    }
}
