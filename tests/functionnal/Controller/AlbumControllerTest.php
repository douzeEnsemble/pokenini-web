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
        $this->assertAlbumFrench($client);
        $this->assertWriteMode($client);
        $this->assertAlbumFrenchWriteMode($client);
    }

    public function testListPublic(): void
    {
        $client = static::createClient();

        $client->request('GET', '/album/demo');

        $this->assertAlbum($client);
        $this->assertAlbumFrench($client);
        $this->assertReadMode($client);
        $this->assertAlbumFrenchReadMode($client);
    }

    public function testListWrongToken(): void
    {
        $client = static::createClient();

        $client->request('GET', '/album/demo?token=kadkjazpdazpdi');

        $this->assertAlbum($client);
        $this->assertAlbumFrench($client);
        $this->assertReadMode($client);
        $this->assertAlbumFrenchReadMode($client);
    }

    public function testListLanguageEnglish(): void
    {
        $client = static::createClient();

        $client->request('GET', '/album/demo?lang=en');

        $this->assertAlbum($client);
        $this->assertReadMode($client);
        $this->assertAlbumEnglish($client);
        $this->assertAlbumEnglishReadMode($client);
    }

    public function testListLanguageEnglishWriteMode(): void
    {
        $client = static::createClient();

        $client->request('GET', '/album/demo?lang=en&token=cb19dc668f0c426c8f3e319f9ea36ecc');

        $this->assertAlbum($client);
        $this->assertWriteMode($client);
        $this->assertAlbumEnglish($client);
        $this->assertAlbumEnglishWriteMode($client);
    }

    public function testListLanguageFrench(): void
    {
        $client = static::createClient();

        $client->request('GET', '/album/demo?lang=fr');

        $this->assertAlbum($client);
        $this->assertReadMode($client);
        $this->assertAlbumFrench($client);
        $this->assertAlbumFrenchReadMode($client);
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

        $mainCrawler = $client->getCrawler();

        $expectedPokemonCount = 1736;

        $this->assertCount(
            $expectedPokemonCount,
            $mainCrawler->filter('.album-case')
        );

        $icon = $mainCrawler->filter('#bulbasaur .album-case-image img');
        $this->assertEquals(
            'https://raw.githubusercontent.com/msikma/pokesprite/master/pokemon-gen8/regular/bulbasaur.png',
            $icon->attr('src')
        );

        $this->assertEquals(
            'Bulbizarre / Bulbasaur',
            $mainCrawler->filter('#bulbasaur .album-case-name')->text()
        );

        $this->assertEquals(
            html_entity_decode('&nbsp;'),
            $mainCrawler->filter('#bulbasaur .album-case-forms')->text()
        );
        $this->assertEquals(
            'Gender',
            $mainCrawler->filter('#venusaur-f .album-case-forms')->text()
        );
        $this->assertEquals(
            'Alpha Gender',
            $mainCrawler->filter('#pikachu-alpha-f .album-case-forms')->text()
        );

        $this->assertCount(
            1,
            $mainCrawler
                ->filter('#bulbasaur.album-case.catch-state-no')
        );
        $this->assertCount(
            1,
            $mainCrawler
                ->filter('#ivysaur.album-case.catch-state-no')
        );
        $this->assertCount(
            1,
            $mainCrawler
                ->filter('#venusaur.album-case.catch-state-toevolve')
        );
        $this->assertCount(
            1,
            $mainCrawler
                ->filter('#venusaur-f.album-case.catch-state-tobreed')
        );
        $this->assertCount(
            1,
            $mainCrawler
                ->filter('#venusaur-mega.album-case.catch-state-totransfer')
        );
        $this->assertCount(
            1,
            $mainCrawler
                ->filter('#venusaur-gmax.album-case.catch-state-yes')
        );

        $this->assertStringContainsString('const catchStates = JSON.parse', $mainCrawler->outerHtml());
        $this->assertStringContainsString('watchCatchStates();', $mainCrawler->outerHtml());

        $this->assertCount(
            $expectedPokemonCount,
            $mainCrawler
                ->filter('.album-case.col-2')
        );
        $this->assertCount(
            290,
            $mainCrawler
                ->filter('div.row')
        );
        $this->assertCount(
            58,
            $mainCrawler
                ->filter('h2')
        );
    }

    private function assertReadMode(KernelBrowser $client): void
    {
        $mainCrawler = $client->getCrawler();

        $this->assertCount(
            0,
            $mainCrawler
                ->filter('.album-case select')
        );

        $this->assertCount(
            1736,
            $mainCrawler
                ->filter('.album-case .album-case-catch-state')
        );
    }

    private function assertWriteMode(KernelBrowser $client): void
    {
        $mainCrawler = $client->getCrawler();

        $this->assertCount(
            1736,
            $mainCrawler
                ->filter('.album-case select')
        );

        $options = $mainCrawler->filter('#bulbasaur select option');
        $this->assertCount(5, $options);

        $this->assertCount(
            0,
            $mainCrawler
                ->filter('.album-case .album-case-catch-state')
        );
    }

    private function assertAlbumFrench(KernelBrowser $client): void
    {
        $mainCrawler = $client->getCrawler();

        $this->assertPageTitleSame('Pokénini Démo');

        $tooltip = $mainCrawler->filter('#bulbasaur .album-case-image');
        $this->assertEquals(
            'Bulbizarre',
            $tooltip->attr('title')
        );
        $imgAlt = $mainCrawler->filter('#bulbasaur .album-image');
        $this->assertEquals(
            'Icone de Bulbizarre',
            $imgAlt->attr('alt')
        );
        $titleBox1 = $mainCrawler->filter('h2#box-1');
        $this->assertEquals(
            'Boite 1',
            $titleBox1->text()
        );
        $titleBox58 = $mainCrawler->filter('h2#box-58');
        $this->assertEquals(
            'Boite 58',
            $titleBox58->text()
        );
    }

    private function assertAlbumEnglish(KernelBrowser $client): void
    {
        $mainCrawler = $client->getCrawler();

        $this->assertPageTitleSame('Pokénini Demo');

        $tooltip = $mainCrawler->filter('#bulbasaur .album-case-image');
        $this->assertEquals(
            'Bulbasaur',
            $tooltip->attr('title')
        );
        $imgAlt = $mainCrawler->filter('#bulbasaur .album-image');
        $this->assertEquals(
            'Icon of Bulbasaur',
            $imgAlt->attr('alt')
        );
        $titleBox1 = $mainCrawler->filter('h2#box-1');
        $this->assertEquals(
            'Box 1',
            $titleBox1->text()
        );
        $titleBox58 = $mainCrawler->filter('h2#box-58');
        $this->assertEquals(
            'Box 58',
            $titleBox58->text()
        );
    }

    private function assertAlbumFrenchWriteMode(KernelBrowser $client): void
    {
        $mainCrawler = $client->getCrawler();

        $selectedOption = $mainCrawler->filter('#bulbasaur select option:selected')->first();
        $this->assertEquals('Non', $selectedOption->text());

        $selectedOption = $mainCrawler->filter('#ivysaur select option:selected')->first();
        $this->assertEquals('Non', $selectedOption->text());

        $selectedOption = $mainCrawler->filter('#venusaur select option:selected')->first();
        $this->assertEquals('af. évoluer', $selectedOption->text());

        $selectedOption = $mainCrawler->filter('#venusaur-f select option:selected')->first();
        $this->assertEquals('af. reproduire', $selectedOption->text());

        $selectedOption = $mainCrawler->filter('#venusaur-mega select option:selected')->first();
        $this->assertEquals('à transférer', $selectedOption->text());

        $selectedOption = $mainCrawler->filter('#venusaur-gmax select option:selected')->first();
        $this->assertEquals('Oui', $selectedOption->text());
    }

    private function assertAlbumFrenchReadMode(KernelBrowser $client): void
    {
        $mainCrawler = $client->getCrawler();

        $this->assertEquals(
            'Non',
            $mainCrawler
                ->filter('#bulbasaur .album-case-catch-state')
                ->text()
        );
        $this->assertEquals(
            'Non',
            $mainCrawler
                ->filter('#ivysaur .album-case-catch-state')
                ->text()
        );
        $this->assertEquals(
            'af. évoluer',
            $mainCrawler
                ->filter('#venusaur .album-case-catch-state')
                ->text()
        );
        $this->assertEquals(
            'af. reproduire',
            $mainCrawler
                ->filter('#venusaur-f .album-case-catch-state')
                ->text()
        );
        $this->assertEquals(
            'à transférer',
            $mainCrawler
                ->filter('#venusaur-mega .album-case-catch-state')
                ->text()
        );
        $this->assertEquals(
            'Oui',
            $mainCrawler
                ->filter('#venusaur-gmax .album-case-catch-state')
                ->text()
        );
    }

    private function assertAlbumEnglishWriteMode(KernelBrowser $client): void
    {
        $mainCrawler = $client->getCrawler();

        $selectedOption = $mainCrawler->filter('#bulbasaur select option:selected')->first();
        $this->assertEquals('No', $selectedOption->text());

        $selectedOption = $mainCrawler->filter('#ivysaur select option:selected')->first();
        $this->assertEquals('No', $selectedOption->text());

        $selectedOption = $mainCrawler->filter('#venusaur select option:selected')->first();
        $this->assertEquals('To evolve', $selectedOption->text());

        $selectedOption = $mainCrawler->filter('#venusaur-f select option:selected')->first();
        $this->assertEquals('To breed', $selectedOption->text());

        $selectedOption = $mainCrawler->filter('#venusaur-mega select option:selected')->first();
        $this->assertEquals('To transfer', $selectedOption->text());

        $selectedOption = $mainCrawler->filter('#venusaur-gmax select option:selected')->first();
        $this->assertEquals('Yes', $selectedOption->text());
    }

    private function assertAlbumEnglishReadMode(KernelBrowser $client): void
    {
        $mainCrawler = $client->getCrawler();

        $this->assertEquals(
            'No',
            $mainCrawler
                ->filter('#bulbasaur .album-case-catch-state')
                ->text()
        );
        $this->assertEquals(
            'No',
            $mainCrawler
                ->filter('#ivysaur .album-case-catch-state')
                ->text()
        );
        $this->assertEquals(
            'To evolve',
            $mainCrawler
                ->filter('#venusaur .album-case-catch-state')
                ->text()
        );
        $this->assertEquals(
            'To breed',
            $mainCrawler
                ->filter('#venusaur-f .album-case-catch-state')
                ->text()
        );
        $this->assertEquals(
            'To transfer',
            $mainCrawler
                ->filter('#venusaur-mega .album-case-catch-state')
                ->text()
        );
        $this->assertEquals(
            'Yes',
            $mainCrawler
                ->filter('#venusaur-gmax .album-case-catch-state')
                ->text()
        );
    }
}
