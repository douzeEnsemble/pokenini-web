<?php

declare(strict_types=1);

namespace App\Tests\Functional\Album\Localization;

use App\Controller\AlbumIndexController;
use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(AlbumIndexController::class)]
class FrenchAlbumLocalizationTest extends WebTestCase
{
    use TestNavTrait;

    public function testListEdit(): void
    {
        $client = static::createClient();

        $user = new User('12', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $client->request('GET', '/fr/album/demolite?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertAlbumFrench($client);
        $this->assertRegularFrench($client);
        $this->assertAlbumFrenchWriteMode($client);
        $this->assertFrenchStatistics($client);
        $this->assertNavigationBarFrench($client);
    }

    public function testListRead(): void
    {
        $client = static::createClient();

        $client->request('GET', '/fr/album/demolite?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertAlbumFrench($client);
        $this->assertRegularFrench($client);
        $this->assertAlbumFrenchReadMode($client);
        $this->assertFrenchStatistics($client);
        $this->assertNavigationBarFrench($client);
    }

    public function testListLanguage(): void
    {
        $client = static::createClient();

        $client->request('GET', '/fr/album/demolite?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertAlbumFrench($client);
        $this->assertRegularFrench($client);
        $this->assertAlbumFrenchReadMode($client);
        $this->assertFrenchStatistics($client);
        $this->assertNavigationBarFrench($client);
    }

    public function testListShiny(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr/album/demoliteshiny?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertShinyFrench($client);

        $navbarTitle = $crawler->filter('.navbar-link');
        $this->assertEquals('Démo, extrait chromatique', $navbarTitle->text());

        $this->assertCount(1, $crawler->filter('.navbar-link .navbar-title'));
        $this->assertCount(1, $crawler->filter('.navbar-link .navbar-subtitle'));

        $this->assertEquals(
            '/fr/album/demoliteshiny?t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $navbarTitle->attr('href')
        );

        $this->assertFrenchLangSwitch($crawler);
    }

    private function assertAlbumFrench(KernelBrowser $client): void
    {
        $crawler = $client->getCrawler();

        $this->assertPageTitleSame('Pokénini Démo, extrait');

        $this->assertEquals(
            'Bulbizarre',
            $crawler->filter('#bulbasaur .album-case-name')->text()
        );

        $this->assertEquals(
            'Gigamax',
            $crawler->filter('#butterfree-gmax .album-case-forms')->text()
        );

        $tooltip = $crawler->filter('#bulbasaur .album-case-image');
        $this->assertEquals(
            '#1 Bulbizarre',
            $tooltip->attr('title')
        );
        $imgAlt = $crawler->filter('#bulbasaur .pokemon-icon');
        $this->assertEquals(
            'Icone de Bulbizarre',
            $imgAlt->attr('alt')
        );
    }

    private function assertAlbumFrenchWriteMode(KernelBrowser $client): void
    {
        $crawler = $client->getCrawler();

        $selectedOption = $crawler->filter('#bulbasaur select option:selected')->first();
        $this->assertEquals('Non', $selectedOption->text());

        $selectedOption = $crawler->filter('#ivysaur select option:selected')->first();
        $this->assertEquals('Non', $selectedOption->text());

        $selectedOption = $crawler->filter('#venusaur select option:selected')->first();
        $this->assertEquals('af. évoluer', $selectedOption->text());

        $selectedOption = $crawler->filter('#venusaur-f select option:selected')->first();
        $this->assertEquals('af. reproduire', $selectedOption->text());

        $selectedOption = $crawler->filter('#venusaur-mega select option:selected')->first();
        $this->assertEquals('à transférer', $selectedOption->text());

        $selectedOption = $crawler->filter('#venusaur-gmax select option:selected')->first();
        $this->assertEquals('À échanger', $selectedOption->text());

        $selectedOption = $crawler->filter('#charmander select option:selected')->first();
        $this->assertEquals('Oui', $selectedOption->text());
    }

    private function assertAlbumFrenchReadMode(KernelBrowser $client): void
    {
        $crawler = $client->getCrawler();

        $this->assertEquals(
            'Non',
            $crawler
                ->filter('#bulbasaur .album-case-catch-state')
                ->text()
        );
        $this->assertEquals(
            'Non',
            $crawler
                ->filter('#ivysaur .album-case-catch-state')
                ->text()
        );
        $this->assertEquals(
            'af. évoluer',
            $crawler
                ->filter('#venusaur .album-case-catch-state')
                ->text()
        );
        $this->assertEquals(
            'Af. reproduire',
            $crawler
                ->filter('#venusaur-f .album-case-catch-state')
                ->text()
        );
        $this->assertEquals(
            'à transférer',
            $crawler
                ->filter('#venusaur-mega .album-case-catch-state')
                ->text()
        );
        $this->assertEquals(
            'À échanger',
            $crawler
                ->filter('#venusaur-gmax .album-case-catch-state')
                ->text()
        );
        $this->assertEquals(
            'Oui',
            $crawler
                ->filter('#charmander .album-case-catch-state')
                ->text()
        );
    }

    private function assertFrenchStatistics(KernelBrowser $client): void
    {
        $crawler = $client->getCrawler();

        $this->assertEquals(
            'Non',
            $crawler->filter('table#report tr.catch-state-no th')->text()
        );
        $this->assertEquals(
            'Af. évoluer',
            $crawler->filter('table#report tr.catch-state-toevolve th')->text()
        );
        $this->assertEquals(
            'Af. reproduire',
            $crawler->filter('table#report tr.catch-state-tobreed th')->text()
        );
        $this->assertEquals(
            'À transférer',
            $crawler->filter('table#report tr.catch-state-totransfer th')->text()
        );
        $this->assertEquals(
            'Oui',
            $crawler->filter('table#report tr.catch-state-yes th')->text()
        );
    }

    private function assertNavigationBarFrench(KernelBrowser $client): void
    {
        $crawler = $client->getCrawler();

        $navbarTitle = $crawler->filter('.navbar-link');
        $this->assertEquals('Démo, extrait', $navbarTitle->text());

        $this->assertCount(1, $crawler->filter('.navbar-link .navbar-title'));
        $this->assertCount(0, $crawler->filter('.navbar-link .navbar-subtitle'));

        $this->assertEquals(
            str_replace('http://localhost', '', (string) $crawler->getUri()),
            $navbarTitle->attr('href')
        );

        $this->assertFrenchLangSwitch($crawler);
    }

    private function assertRegularFrench(KernelBrowser $client): void
    {
        $crawler = $client->getCrawler();

        $this->assertStringContainsString(
            'Icone de ',
            $crawler->filter('.pokemon-icon')->first()->attr('alt') ?? ''
        );
    }

    private function assertShinyFrench(KernelBrowser $client): void
    {
        $crawler = $client->getCrawler();

        $this->assertStringContainsString(
            'Icone chromatique de ',
            $crawler->filter('.pokemon-icon')->first()->attr('alt') ?? ''
        );
    }
}
