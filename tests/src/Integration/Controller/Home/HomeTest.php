<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Home;

use App\Controller\HomeController;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @internal
 */
#[CoversClass(HomeController::class)]
final class HomeTest extends WebTestCase
{
    use TestNavTrait;

    #[Test]
    public function home(): void
    {
        $client = self::createClient();

        $crawler = $client->request('GET', '/fr');

        $this->assertResponseIsSuccessful();
        $this->assertVisitorHub($crawler);
    }

    #[Test]
    public function hubAsVisitor(): void
    {
        $client = self::createClient();

        $crawler = $client->request('GET', '/fr/hub');

        $this->assertResponseIsSuccessful();
        $this->assertVisitorHub($crawler);
        $this->assertCountFilter($crawler, 1, '.navbar-nav .hub-link .nav-link.active[aria-current="page"]');
    }

    #[Test]
    public function homeAsTrainerOnTheHub(): void
    {
        $client = $this->trainerClient('789465465489');

        $crawler = $client->request('GET', '/fr');

        $this->assertResponseIsSuccessful();
        $this->assertTrainerHub($crawler);
        $this->assertCountFilter($crawler, 0, '.navbar-nav .hub-link .nav-link.active');
    }

    #[Test]
    #[DataProvider('providerChosenHomePages')]
    public function homeAsTrainerRedirectsToTheChosenPage(string $identifier, string $url): void
    {
        $client = $this->trainerClient($identifier);

        $client->request('GET', '/fr');

        $this->assertResponseRedirects($url);
    }

    /**
     * @return string[][]
     */
    public static function providerChosenHomePages(): array
    {
        return [
            'album' => ['home-album', '/fr/album/dex'],
            'election' => ['home-election', '/fr/election/dex'],
            'game' => ['home-game', '/fr/game'],
        ];
    }

    #[Test]
    public function homeAsTrainerInEnglishRedirectsToTheChosenPage(): void
    {
        $client = $this->trainerClient('home-game');

        $client->request('GET', '/en');

        $this->assertResponseRedirects('/en/game');
    }

    #[Test]
    public function homeAsTrainerFallsBackOnTheHubWhenTheChoiceCantBeRead(): void
    {
        $client = $this->trainerClient('home-broken');

        $crawler = $client->request('GET', '/fr');

        $this->assertResponseIsSuccessful();
        $this->assertTrainerHub($crawler);
    }

    #[Test]
    public function hubAsTrainerWhoChoseAnotherPage(): void
    {
        $client = $this->trainerClient('home-album');

        $crawler = $client->request('GET', '/fr/hub');

        $this->assertResponseIsSuccessful();
        $this->assertTrainerHub($crawler);
        $this->assertCountFilter($crawler, 1, '.navbar-nav .hub-link .nav-link.active[aria-current="page"]');
    }

    #[Test]
    public function hubAsTrainerInEnglish(): void
    {
        $client = $this->trainerClient('789465465489');

        $crawler = $client->request('GET', '/en/hub');

        $this->assertResponseIsSuccessful();
        $this->assertSame('Hi young trainer, what do you want to do today?', $crawler->filter('#home > div > p')->text());
        $this->assertSame('Your albums', $crawler->filter('.hub-album h2')->text());
        $this->assertSame('/en/album/dex', $crawler->filter('.hub-album a.hub-action')->attr('href'));
        $this->assertSame('/en/trainer/home_page', $crawler->filter('.hub-choose-home a')->attr('href'));
        $this->assertEnglishLangSwitch($crawler);
    }

    #[Test]
    public function hubAsLoggedUserWithoutTrainerRole(): void
    {
        $client = self::createClient();
        $client->loginUser(GetUserToken::getFakeUserToken(), 'web');

        $crawler = $client->request('GET', '/fr');

        $this->assertResponseIsSuccessful();
        $this->assertCountFilter($crawler, 3, '.hub-item');
        $this->assertCountFilter($crawler, 0, '.alert-warning');
        $this->assertCountFilter($crawler, 6, '.dex-item');
    }

    private function trainerClient(string $identifier): KernelBrowser
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken($identifier);
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        return $client;
    }

    private function assertVisitorHub(Crawler $crawler): void
    {
        $this->assertSame('Pokénini', $crawler->filter('title')->text());
        $this->assertSame('Pokénini', $crawler->filter('h1')->text());
        $this->assertSame('Suis ta collection, élis ton Pokémon préféré et joue.', $crawler->filter('#home > div > p')->text());

        $this->assertFrenchLangSwitch($crawler);

        $this->assertCountFilter($crawler, 1, '.alert-warning');
        $this->assertSame('/fr/connect', $crawler->filter('.alert-warning a')->attr('href'));

        $this->assertCountFilter($crawler, 3, '.hub-item');
        $this->assertSame('Les albums', $crawler->filter('.hub-album h2')->text());
        $this->assertSame('#home-demo', $crawler->filter('.hub-album a.hub-action')->attr('href'));
        $this->assertSame('Voir un exemple', $crawler->filter('.hub-album a.hub-action')->text());
        $this->assertSame('/fr/connect', $crawler->filter('.hub-election a.hub-action-outline')->attr('href'));
        $this->assertSame('Connecte-toi pour voter', $crawler->filter('.hub-election a.hub-action-outline')->text());
        $this->assertSame('/fr/connect', $crawler->filter('.hub-game a.hub-action-outline')->attr('href'));
        $this->assertSame('Connecte-toi pour jouer', $crawler->filter('.hub-game a.hub-action-outline')->text());
        $this->assertCountFilter($crawler, 3, '.hub-games li');
        $this->assertCountFilter($crawler, 0, '.hub-games a');
        $this->assertCountFilter($crawler, 0, '.hub-choose-home');

        $this->assertSame("Exemple d'albums", $crawler->filter('#home-demo h2')->text());
        $this->assertCountFilter($crawler, 6, '#home-demo .dex-item');

        $this->assertCountFilter($crawler, 0, 'script[src^="/js/album.js?v="]');
        $this->assertStringNotContainsString('watchCatchStates();', $crawler->outerHtml());

        $this->assertCountFilter($crawler, 1, '.navbar-nav .hub-link a[href="/fr/hub"]');
        $this->assertCountFilter($crawler, 0, '.navbar-nav .album-link');
        $this->assertSame('/fr', $crawler->filter('.navbar-logo')->attr('href'));
    }

    private function assertTrainerHub(Crawler $crawler): void
    {
        $this->assertSame('Pokénini', $crawler->filter('title')->text());
        $this->assertSame('Pokénini', $crawler->filter('h1')->text());
        $this->assertSame("Salut jeune dresseur, tu veux faire quoi aujourd'hui ?", $crawler->filter('#home > div > p')->text());

        $this->assertCountFilter($crawler, 0, '.alert-warning');
        $this->assertCountFilter($crawler, 0, '.dex-item');
        $this->assertCountFilter($crawler, 0, '#home-demo');

        $this->assertCountFilter($crawler, 3, '.hub-item');

        $this->assertSame('Tes albums', $crawler->filter('.hub-album h2')->text());
        $this->assertSame('/fr/album/dex', $crawler->filter('.hub-album a.hub-action')->attr('href'));
        $this->assertSame('Voir mes albums', $crawler->filter('.hub-album a.hub-action')->text());

        $this->assertSame("C'est quoi ton préféré ?", $crawler->filter('.hub-election h2')->text());
        $this->assertSame('/fr/election/dex', $crawler->filter('.hub-election a.hub-action')->attr('href'));
        $this->assertSame('Choisir mon préféré', $crawler->filter('.hub-election a.hub-action')->text());

        $this->assertSame('Jeux', $crawler->filter('.hub-game h2')->text());
        $this->assertSame('/fr/game', $crawler->filter('.hub-game a.hub-action')->attr('href'));
        $this->assertCountFilter($crawler, 3, '.hub-games a');
        $this->assertSame('/fr/game/color', $crawler->filter('.hub-games a')->eq(0)->attr('href'));
        $this->assertSame('/fr/game/shiny', $crawler->filter('.hub-games a')->eq(1)->attr('href'));
        $this->assertSame('/fr/game/which-shiny', $crawler->filter('.hub-games a')->eq(2)->attr('href'));
        $this->assertSame(
            ['/img/game/color.svg', '/img/game/shiny.svg', '/img/game/which-shiny.svg'],
            $crawler->filter('.hub-games img.game-icon')->each(static fn (Crawler $node): string => explode('?', (string) $node->attr('src'))[0]),
        );

        $this->assertSame('/fr/trainer/home_page', $crawler->filter('.hub-choose-home a')->attr('href'));
        $this->assertSame(
            "Tu préfères arriver directement sur tes albums, les élections ou les jeux ? Choisis ta page d'accueil",
            $crawler->filter('.hub-choose-home')->text()
        );

        $this->assertCountFilter($crawler, 1, '.navbar-nav .hub-link a[href="/fr/hub"]');
        $this->assertCountFilter($crawler, 1, '.navbar-nav .album-link a[href="/fr/album/dex"]');
        $this->assertSame('/fr', $crawler->filter('.navbar-logo')->attr('href'));
    }
}
