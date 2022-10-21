<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\AlbumController;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class EnglishAlbumControllerTest extends AbstractAlbumControllerTestCase
{
    public function testListLanguageEnglish(): void
    {
        $client = static::createClient();

        $client->request('GET', '/en/album/r/demo');

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
        $this->assertNoConnectedNavBar($client->getCrawler());
    }

    public function testListLanguageEnglishWriteMode(): void
    {
        $client = static::createClient();

        $client->request('GET', '/en/album/w/demo', [], [], [
            'PHP_AUTH_USER' => 'renaud',
            'PHP_AUTH_PW'   => 'douze',
        ]);

        $this->assertAlbum($client);
        $this->assertWriteMode($client);
        $this->assertRegular($client);
        $this->assertRegularEnglish($client);
        $this->assertAlbumEnglish($client);
        $this->assertAlbumEnglishWriteMode($client);
        $this->assertStatistics($client);
        $this->assertEnglishStatistics($client);
        $this->assertNavigationBarEnglish($client);
        $this->assertConnectedAlbumNavBar($client->getCrawler());
    }

    public function testListShinyEnglish(): void
    {
        $client = static::createClient();

        $client->request('GET', '/en/album/r/homeshiny');

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
        $this->assertEquals('To trade', $selectedOption->text());

        $selectedOption = $mainCrawler->filter('#charmander select option:selected')->first();
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
            'To trade',
            $mainCrawler
                ->filter('#venusaur-gmax .album-case-catch-state')
                ->text()
        );
        $this->assertEquals(
            'Yes',
            $mainCrawler
                ->filter('#charmander .album-case-catch-state')
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

        $navbarTitle = $mainCrawler->filter('.navbar-brand');
        $this->assertEquals('Demo', $navbarTitle->text());
        $this->assertEquals('/en/', $navbarTitle->attr('href'));

        $this->assertEnglishLangSwitch($mainCrawler);
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
