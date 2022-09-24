<?php

namespace App\Tests\Functional\Controller\AlbumController;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class EnglishAlbumControllerTest extends AbstractAlbumControllerTestCase
{
    public function testListLanguageEnglish(): void
    {
        $client = static::createClient();

        $client->request('GET', '/album/demo?lang=en');

        $this->assertAlbum($client);
        $this->assertReadMode($client);
        $this->assertRegular($client);
        $this->assertRegularEnglish($client);
        $this->assertAlbumEnglish($client);
        $this->assertAlbumEnglishReadMode($client);
        $this->assertStatistics($client);
        $this->assertEnglishStatistics($client);
        $this->assertNavigationBar($client);
        $this->assertNavigationBarEnglish($client);
    }

    public function testListLanguageEnglishWriteMode(): void
    {
        $client = static::createClient();

        $client->request('GET', '/album/demo?lang=en&token=cb19dc668f0c426c8f3e319f9ea36ecc');

        $this->assertAlbum($client);
        $this->assertWriteMode($client);
        $this->assertRegular($client);
        $this->assertRegularEnglish($client);
        $this->assertAlbumEnglish($client);
        $this->assertAlbumEnglishWriteMode($client);
        $this->assertStatistics($client);
        $this->assertEnglishStatistics($client);
        $this->assertNavigationBar($client);
        $this->assertNavigationBarEnglish($client);
    }

    public function testListShinyEnglish(): void
    {
        $client = static::createClient();

        $client->request('GET', '/album/homeshiny?lang=en');

        $this->assertShiny($client);
        $this->assertShinyEnglish($client);
    }

    private function assertAlbumEnglish(KernelBrowser $client): void
    {
        $mainCrawler = $client->getCrawler();

        $this->assertPageTitleSame('Pokénini Demo');

        $this->assertEquals(
            'Bulbasaur',
            $mainCrawler->filter('#bulbasaur .album-case-name')->text()
        );
        $this->assertEquals(
            'Bulbizarre',
            $mainCrawler->filter('#bulbasaur .album-case-name-hidden')->text()
        );

        $this->assertEquals(
            'Alpha ♀️',
            $mainCrawler->filter('#pikachu-alpha-f .album-case-forms')->text()
        );

        $tooltip = $mainCrawler->filter('#bulbasaur .album-case-image');
        $this->assertEquals(
            '#1 Bulbasaur',
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

    private function assertEnglishStatistics(KernelBrowser $client): void
    {
        $mainCrawler = $client->getCrawler();

        $this->assertEquals(
            'No',
            $mainCrawler->filter('table#report tr.catch-state-no th')->text()
        );
        $this->assertEquals(
            'To evolve',
            $mainCrawler->filter('table#report tr.catch-state-toevolve th')->text()
        );
        $this->assertEquals(
            'To breed',
            $mainCrawler->filter('table#report tr.catch-state-tobreed th')->text()
        );
        $this->assertEquals(
            'To transfer',
            $mainCrawler->filter('table#report tr.catch-state-totransfer th')->text()
        );
        $this->assertEquals(
            'Yes',
            $mainCrawler->filter('table#report tr.catch-state-yes th')->text()
        );
    }

    private function assertNavigationBarEnglish(KernelBrowser $client): void
    {
        $mainCrawler = $client->getCrawler();

        $firstAlbum = $mainCrawler->filter('.navbar .nav-item')->first();
        $this->assertEquals('Home', $firstAlbum->text());
        $this->assertEquals('/album/home?lang=en', $firstAlbum->filter('.nav-link')->attr('href'));

        $secondAlbum = $mainCrawler->filter('.navbar .nav-item')->eq(1);
        $this->assertEquals('Home Shiny', $secondAlbum->text());
        $this->assertEquals('/album/homeshiny?lang=en', $secondAlbum->filter('.nav-link')->attr('href'));
    }

    private function assertRegularEnglish(KernelBrowser $client): void
    {
        $mainCrawler = $client->getCrawler();

        $this->assertStringContainsString(
            'Icon of ',
            $mainCrawler->filter('.album-image')->first()->attr('alt') ?? ''
        );
    }

    private function assertShinyEnglish(KernelBrowser $client): void
    {
        $mainCrawler = $client->getCrawler();

        $this->assertStringContainsString(
            'Shiny icon of ',
            $mainCrawler->filter('.album-image')->first()->attr('alt') ?? ''
        );
    }
}
