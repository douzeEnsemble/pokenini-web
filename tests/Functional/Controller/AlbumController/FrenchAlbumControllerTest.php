<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\AlbumController;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class FrenchAlbumControllerTest extends AbstractAlbumControllerTestCase
{
    public function testListPrivate(): void
    {
        $client = static::createClient();

        $client->request('GET', '/fr/album/demo?token=cb19dc668f0c426c8f3e319f9ea36ecc');

        $this->assertAlbum($client);
        $this->assertAlbumFrench($client);
        $this->assertRegular($client);
        $this->assertRegularFrench($client);
        $this->assertWriteMode($client);
        $this->assertAlbumFrenchWriteMode($client);
        $this->assertStatistics($client);
        $this->assertFrenchStatistics($client);
        $this->assertNavigationBar($client);
        $this->assertNavigationBarFrench($client);
    }

    public function testListPublic(): void
    {
        $client = static::createClient();

        $client->request('GET', '/fr/album/demo');

        $this->assertAlbum($client);
        $this->assertAlbumFrench($client);
        $this->assertRegular($client);
        $this->assertRegularFrench($client);
        $this->assertReadMode($client);
        $this->assertAlbumFrenchReadMode($client);
        $this->assertStatistics($client);
        $this->assertFrenchStatistics($client);
        $this->assertNavigationBar($client);
        $this->assertNavigationBarFrench($client);
    }

    public function testListWrongToken(): void
    {
        $client = static::createClient();

        $client->request('GET', '/fr/album/demo?token=kadkjazpdazpdi');

        $this->assertAlbum($client);
        $this->assertAlbumFrench($client);
        $this->assertRegular($client);
        $this->assertRegularFrench($client);
        $this->assertReadMode($client);
        $this->assertAlbumFrenchReadMode($client);
        $this->assertStatistics($client);
        $this->assertFrenchStatistics($client);
        $this->assertNavigationBar($client);
        $this->assertNavigationBarFrench($client);
    }

    public function testListLanguageFrench(): void
    {
        $client = static::createClient();

        $client->request('GET', '/fr/album/demo');

        $this->assertAlbum($client);
        $this->assertAlbumFrench($client);
        $this->assertRegular($client);
        $this->assertRegularFrench($client);
        $this->assertReadMode($client);
        $this->assertAlbumFrenchReadMode($client);
        $this->assertStatistics($client);
        $this->assertFrenchStatistics($client);
        $this->assertNavigationBar($client);
        $this->assertNavigationBarFrench($client);
    }

    public function testListShiny(): void
    {
        $client = static::createClient();

        $client->request('GET', '/fr/album/homeshiny');

        $this->assertShiny($client);
        $this->assertShinyFrench($client);
    }

    public function testListShinyFrench(): void
    {
        $client = static::createClient();

        $client->request('GET', '/fr/album/homeshiny');

        $this->assertShiny($client);
        $this->assertShinyFrench($client);
    }

    private function assertAlbumFrench(KernelBrowser $client): void
    {
        $mainCrawler = $client->getCrawler();

        $this->assertPageTitleSame('Pokénini Démo');

        $this->assertEquals(
            'Bulbizarre',
            $mainCrawler->filter('#bulbasaur .album-case-name')->text()
        );

        $this->assertEquals(
            'Baron ♀️',
            $mainCrawler->filter('#pikachu-alpha-f .album-case-forms')->text()
        );

        $tooltip = $mainCrawler->filter('#bulbasaur .album-case-image');
        $this->assertEquals(
            '#1 Bulbizarre',
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
            'Af. reproduire',
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

    private function assertFrenchStatistics(KernelBrowser $client): void
    {
        $mainCrawler = $client->getCrawler();

        $this->assertEquals(
            'Non',
            $mainCrawler->filter('table#report tr.catch-state-no th')->text()
        );
        $this->assertEquals(
            'Af. évoluer',
            $mainCrawler->filter('table#report tr.catch-state-toevolve th')->text()
        );
        $this->assertEquals(
            'Af. reproduire',
            $mainCrawler->filter('table#report tr.catch-state-tobreed th')->text()
        );
        $this->assertEquals(
            'À transférer',
            $mainCrawler->filter('table#report tr.catch-state-totransfer th')->text()
        );
        $this->assertEquals(
            'Oui',
            $mainCrawler->filter('table#report tr.catch-state-yes th')->text()
        );
    }

    private function assertNavigationBarFrench(KernelBrowser $client): void
    {
        $mainCrawler = $client->getCrawler();

        $firstAlbum = $mainCrawler->filter('.navbar .nav-item')->first();
        $this->assertEquals('Accueil', $firstAlbum->text());
        $this->assertEquals('/fr/', $firstAlbum->filter('.nav-link')->attr('href'));
    }

    private function assertRegularFrench(KernelBrowser $client): void
    {
        $mainCrawler = $client->getCrawler();

        $this->assertStringContainsString(
            'Icone de ',
            $mainCrawler->filter('.album-image')->first()->attr('alt') ?? ''
        );
    }

    private function assertShinyFrench(KernelBrowser $client): void
    {
        $mainCrawler = $client->getCrawler();

        $this->assertStringContainsString(
            'Icone chromatique de ',
            $mainCrawler->filter('.album-image')->first()->attr('alt') ?? ''
        );
    }
}
