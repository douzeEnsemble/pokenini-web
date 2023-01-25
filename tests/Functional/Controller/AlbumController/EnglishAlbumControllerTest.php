<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\AlbumController;

use App\Security\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class EnglishAlbumControllerTest extends AbstractAlbumControllerTestCase
{
    public function testListLanguageEnglish(): void
    {
        $client = static::createClient();

        $client->request('GET', '/en/album/demo?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

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

        $user = new User('12');
        $user->addTrainerRole();
        $client->loginUser($user);

        $client->request('GET', '/en/album/demo?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertAlbum($client);
        $this->assertWriteMode($client);
        $this->assertRegular($client);
        $this->assertRegularEnglish($client);
        $this->assertAlbumEnglish($client);
        $this->assertAlbumEnglishWriteMode($client);
        $this->assertStatistics($client);
        $this->assertEnglishStatistics($client);
        $this->assertNavigationBarEnglish($client);
        $this->assertTrainerAlbumNavBar($client->getCrawler());
    }

    public function testListShinyEnglish(): void
    {
        $client = static::createClient();

        $client->request('GET', '/en/album/homeshiny?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertShiny($client);
        $this->assertShinyEnglish($client);
    }

    private function assertAlbumEnglish(KernelBrowser $client): void
    {
        $crawler = $client->getCrawler();

        $this->assertPageTitleSame('Pokénini Demo');

        $this->assertEquals(
            'Bulbasaur',
            $crawler->filter('#bulbasaur .album-case-name')->text()
        );

        $this->assertEquals(
            'Alpha ♀️',
            $crawler->filter('#pikachu-alpha-f .album-case-forms')->text()
        );

        $tooltip = $crawler->filter('#bulbasaur .album-case-image');
        $this->assertEquals(
            '#1 Bulbasaur',
            $tooltip->attr('title')
        );
        $imgAlt = $crawler->filter('#bulbasaur .album-image');
        $this->assertEquals(
            'Icon of Bulbasaur',
            $imgAlt->attr('alt')
        );
    }

    private function assertAlbumEnglishWriteMode(KernelBrowser $client): void
    {
        $crawler = $client->getCrawler();

        $selectedOption = $crawler->filter('#bulbasaur select option:selected')->first();
        $this->assertEquals('No', $selectedOption->text());

        $selectedOption = $crawler->filter('#ivysaur select option:selected')->first();
        $this->assertEquals('No', $selectedOption->text());

        $selectedOption = $crawler->filter('#venusaur select option:selected')->first();
        $this->assertEquals('To evolve', $selectedOption->text());

        $selectedOption = $crawler->filter('#venusaur-f select option:selected')->first();
        $this->assertEquals('To breed', $selectedOption->text());

        $selectedOption = $crawler->filter('#venusaur-mega select option:selected')->first();
        $this->assertEquals('To transfer', $selectedOption->text());

        $selectedOption = $crawler->filter('#venusaur-gmax select option:selected')->first();
        $this->assertEquals('To trade', $selectedOption->text());

        $selectedOption = $crawler->filter('#charmander select option:selected')->first();
        $this->assertEquals('Yes', $selectedOption->text());
    }

    private function assertAlbumEnglishReadMode(KernelBrowser $client): void
    {
        $crawler = $client->getCrawler();

        $this->assertEquals(
            'No',
            $crawler
                ->filter('#bulbasaur .album-case-catch-state')
                ->text()
        );
        $this->assertEquals(
            'No',
            $crawler
                ->filter('#ivysaur .album-case-catch-state')
                ->text()
        );
        $this->assertEquals(
            'To evolve',
            $crawler
                ->filter('#venusaur .album-case-catch-state')
                ->text()
        );
        $this->assertEquals(
            'To breed',
            $crawler
                ->filter('#venusaur-f .album-case-catch-state')
                ->text()
        );
        $this->assertEquals(
            'To transfer',
            $crawler
                ->filter('#venusaur-mega .album-case-catch-state')
                ->text()
        );
        $this->assertEquals(
            'To trade',
            $crawler
                ->filter('#venusaur-gmax .album-case-catch-state')
                ->text()
        );
        $this->assertEquals(
            'Yes',
            $crawler
                ->filter('#charmander .album-case-catch-state')
                ->text()
        );
    }

    private function assertEnglishStatistics(KernelBrowser $client): void
    {
        $crawler = $client->getCrawler();

        $this->assertEquals(
            'No',
            $crawler->filter('table#report tr.catch-state-no th')->text()
        );
        $this->assertEquals(
            'To evolve',
            $crawler->filter('table#report tr.catch-state-toevolve th')->text()
        );
        $this->assertEquals(
            'To breed',
            $crawler->filter('table#report tr.catch-state-tobreed th')->text()
        );
        $this->assertEquals(
            'To transfer',
            $crawler->filter('table#report tr.catch-state-totransfer th')->text()
        );
        $this->assertEquals(
            'Yes',
            $crawler->filter('table#report tr.catch-state-yes th')->text()
        );
    }

    private function assertNavigationBarEnglish(KernelBrowser $client): void
    {
        $crawler = $client->getCrawler();

        $navbarTitle = $crawler->filter('.navbar-brand');
        $this->assertEquals('Demo', $navbarTitle->text());
        $this->assertEquals('/en', $navbarTitle->attr('href'));

        $this->assertEnglishLangSwitch($crawler);
    }

    private function assertRegularEnglish(KernelBrowser $client): void
    {
        $crawler = $client->getCrawler();

        $this->assertStringContainsString(
            'Icon of ',
            $crawler->filter('.album-image')->first()->attr('alt') ?? ''
        );
    }

    private function assertShinyEnglish(KernelBrowser $client): void
    {
        $crawler = $client->getCrawler();

        $this->assertStringContainsString(
            'Shiny icon of ',
            $crawler->filter('.album-image')->first()->attr('alt') ?? ''
        );
    }
}
