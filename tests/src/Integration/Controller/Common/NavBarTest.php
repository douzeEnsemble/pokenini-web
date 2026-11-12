<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Common;

use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * The navigation is one markup with two layouts (a tab bar below xl, one row from xl):
 * the places visited most are in .nav-main, the rest in the panel #navbarNav, and each
 * item exists once.
 *
 * @internal
 */
#[CoversNothing]
final class NavBarTest extends WebTestCase
{
    use TestNavTrait;

    #[Test]
    public function trainerHasFourPlacesAndTheMoreTab(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/dex');

        $this->assertSame(
            ['hub-link', 'album-link', 'election-link', 'game-link', 'nav-more'],
            $crawler->filter('.nav-main > .nav-item')->each(
                static fn (Crawler $item): string => explode(' ', (string) $item->attr('class'))[1]
            ),
        );
        $this->assertSame(
            ['Accueil', 'Albums', 'Élections', 'Jeux', 'Plus'],
            $crawler->filter('.nav-main > .nav-item')->each(
                static fn (Crawler $item): string => $item->filter('.nav-link > span:not(.visually-hidden)')->first()->text()
            ),
        );

        // The rest is in the panel the "More" tab opens
        $this->assertSame(
            ['trainer-link', 'cookie-manager', 'lang-switch', 'logout-link'],
            $crawler->filter('#navbarNav .nav-item')->each(
                static fn (Crawler $item): string => explode(' ', (string) $item->attr('class'))[1]
            ),
        );
        $this->assertSame('#navbarNav', $crawler->filter('.nav-more button')->attr('data-bs-target'));
        $this->assertSame('false', $crawler->filter('.nav-more button')->attr('aria-expanded'));
        $this->assertSame('navbarNav', $crawler->filter('.nav-more button')->attr('aria-controls'));

        // Each item exists once, whatever the layout
        $this->assertCountFilter($crawler, 1, '.navbar-nav .hub-link');
        $this->assertCountFilter($crawler, 1, '.navbar-nav .album-link');
        $this->assertCountFilter($crawler, 1, '.navbar-nav .trainer-link');
        $this->assertCountFilter($crawler, 1, '.navbar-nav .lang-switch');
    }

    #[Test]
    public function trainerSpaceHasItsOwnLabelInThePanel(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/dex');

        $this->assertSame('Mon espace', $crawler->filter('#navbarNav .trainer-link a.nav-link')->text());
        // The sub-menu of the trainer pages only shows from xl
        $this->assertStringContainsString('d-none d-xl-block', (string) $crawler->filter('#navbarNav .trainer-link .dropdown-toggle')->attr('class'));
        $this->assertCountFilter($crawler, 4, '#navbarNav .trainer-link .dropdown-item');
    }

    #[Test]
    public function visitorKeepsLoggingInAsATab(): void
    {
        $client = self::createClient();

        $crawler = $client->request('GET', '/fr/connect');

        $this->assertSame(
            ['hub-link', 'trainer-link', 'nav-more'],
            $crawler->filter('.nav-main > .nav-item')->each(
                static fn (Crawler $item): string => explode(' ', (string) $item->attr('class'))[1]
            ),
        );
        $this->assertStringContainsString('/connect', (string) $crawler->filter('.nav-main .trainer-link a')->attr('href'));
        $this->assertSame('Se connecter', $crawler->filter('.nav-main .trainer-link a')->text());

        $this->assertSame(
            ['cookie-manager', 'lang-switch'],
            $crawler->filter('#navbarNav .nav-item')->each(
                static fn (Crawler $item): string => explode(' ', (string) $item->attr('class'))[1]
            ),
        );
        $this->assertCountFilter($crawler, 0, '.navbar-nav .album-link');
        $this->assertCountFilter($crawler, 0, '.navbar-nav .election-link');
        $this->assertCountFilter($crawler, 0, '.navbar-nav .game-link');
        $this->assertCountFilter($crawler, 0, '.navbar-nav .logout-link');
    }

    #[Test]
    public function albumsStayTheCurrentPlaceOnTheirAlbum(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/dex');
        $this->assertCountFilter($crawler, 1, '.navbar-nav .album-link .nav-link.active[aria-current="page"]');

        $crawler = $client->request('GET', '/fr/album/demolite?t=7b52009b64fd0a2a49e6d8a939753077792b0554');
        $this->assertCountFilter($crawler, 1, '.navbar-nav .album-link .nav-link.active[aria-current="page"]');
        $this->assertCountFilter($crawler, 0, '.navbar-nav .hub-link .nav-link.active');
    }

    #[Test]
    public function englishLabels(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/en/album/dex');

        $this->assertSame(
            ['Home', 'Albums', 'Elections', 'Games', 'More'],
            $crawler->filter('.nav-main > .nav-item')->each(
                static fn (Crawler $item): string => $item->filter('.nav-link > span:not(.visually-hidden)')->first()->text()
            ),
        );
        $this->assertSame('My space', $crawler->filter('#navbarNav .trainer-link a.nav-link')->text());
    }
}
