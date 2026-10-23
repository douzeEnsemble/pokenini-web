<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Trainer;

use App\Controller\TrainerHomePageController;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(TrainerHomePageController::class)]
final class TrainerHomePageTest extends WebTestCase
{
    use TestNavTrait;

    #[Test]
    public function homePagePage(): void
    {
        $client = $this->trainerClient('789465465489');

        $crawler = $client->request('GET', '/fr/trainer/home_page');

        $this->assertResponseIsSuccessful();

        $this->assertSame('Pokénini Ton espace de dresseur', $crawler->filter('title')->text());
        $this->assertSame('Ton espace de dresseur', $crawler->filter('h1')->text());

        $this->assertCount(4, $crawler->filter('#trainer-section-tab .nav-link'));
        $this->assertSame('/fr/trainer/home_page', $crawler->filter('#trainer-section-tab .nav-link.active')->attr('href'));
        $this->assertSame("Page d'accueil", $crawler->filter('#trainer-section-tab .nav-link.active')->text());

        $this->assertSame('/fr/trainer/home_page', $crawler->filter('#home-page-form')->attr('action'));
        $this->assertSame('Quand tu arrives sur Pokénini, tu veux voir :', $crawler->filter('#home-page-form legend')->text());
        $this->assertCountFilter($crawler, 1, '#home-page-form input[name="_token"]');
        $this->assertCountFilter($crawler, 4, '#home-page-form input[type="radio"][name="home_page"]');
        $this->assertCountFilter($crawler, 1, '#home-page-form input[type="radio"]:checked');
        $this->assertCountFilter($crawler, 1, '#home-page-hub:checked');
        $this->assertSame(
            "L'accueil Pokénini par défaut Le hub avec tes albums, les élections et les jeux.",
            $crawler->filter('label[for="home-page-hub"]')->text()
        );
        $this->assertSame('Tes albums La liste de tes dex.', $crawler->filter('label[for="home-page-album"]')->text());
        $this->assertSame("C'est quoi ton préféré ? La liste des élections.", $crawler->filter('label[for="home-page-election"]')->text());
        $this->assertSame('Jeux La page des jeux.', $crawler->filter('label[for="home-page-game"]')->text());
        $this->assertCountFilter($crawler, 0, '.home-page-saved');
        $this->assertCountFilter($crawler, 0, '.home-page-error');

        $this->assertCountFilter($crawler, 4, '.navbar-nav .trainer-link .dropdown-menu .dropdown-item');
        $this->assertSame(
            '/fr/trainer/home_page',
            $crawler->filter('.navbar-nav .trainer-link .dropdown-item.active')->attr('href')
        );
    }

    #[Test]
    public function homePagePageShowsTheChosenPage(): void
    {
        $client = $this->trainerClient('home-game');

        $crawler = $client->request('GET', '/en/trainer/home_page');

        $this->assertResponseIsSuccessful();
        $this->assertCountFilter($crawler, 1, '#home-page-form input[type="radio"]:checked');
        $this->assertCountFilter($crawler, 1, '#home-page-game:checked');
        $this->assertSame('When you open Pokénini, you want to see:', $crawler->filter('#home-page-form legend')->text());
        $this->assertSame('Home page', $crawler->filter('#trainer-section-tab .nav-link.active')->text());
    }

    #[Test]
    public function save(): void
    {
        $client = $this->trainerClient('789465465489');

        $crawler = $client->request('GET', '/fr/trainer/home_page');

        $form = $crawler->filter('#home-page-form')->form();
        $client->submit($form, ['home_page' => 'album']);

        $this->assertResponseRedirects('/fr/trainer/home_page');
        $crawler = $client->followRedirect();

        // Moco always answers "hub" for this trainer: only the confirmation is checked here
        $this->assertCountFilter($crawler, 1, '.home-page-saved');
        $this->assertSame("C'est noté, tu arriveras sur « L'accueil Pokénini ».", $crawler->filter('.home-page-saved')->text());
        $this->assertCountFilter($crawler, 0, '.home-page-error');

        $crawler = $client->request('GET', '/fr/trainer/home_page');
        $this->assertCountFilter($crawler, 0, '.home-page-saved');
    }

    #[Test]
    public function saveUnknownPage(): void
    {
        $client = $this->trainerClient('789465465489');

        $crawler = $client->request('GET', '/fr/trainer/home_page');
        $token = $crawler->filter('#home-page-form input[name="_token"]')->attr('value');

        $client->request('POST', '/fr/trainer/home_page', ['_token' => $token, 'home_page' => 'admin']);

        $this->assertResponseRedirects('/fr/trainer/home_page');
        $crawler = $client->followRedirect();

        $this->assertCountFilter($crawler, 0, '.home-page-saved');
        $this->assertCountFilter($crawler, 1, '.home-page-error');
        $this->assertSame(
            "Ton choix n'a pas pu être enregistré, réessaie un peu plus tard.",
            $crawler->filter('.home-page-error')->text()
        );
    }

    #[Test]
    public function saveWhenTheBackFails(): void
    {
        $client = $this->trainerClient('home-unsaved');

        $crawler = $client->request('GET', '/fr/trainer/home_page');

        $form = $crawler->filter('#home-page-form')->form();
        $client->submit($form, ['home_page' => 'game']);

        $this->assertResponseRedirects('/fr/trainer/home_page');
        $crawler = $client->followRedirect();

        $this->assertCountFilter($crawler, 0, '.home-page-saved');
        $this->assertCountFilter($crawler, 1, '.home-page-error');
    }

    #[Test]
    public function saveWithAnInvalidToken(): void
    {
        $client = $this->trainerClient('789465465489');

        $client->request('POST', '/fr/trainer/home_page', ['_token' => 'nope', 'home_page' => 'album']);

        $this->assertResponseStatusCodeSame(403);
    }

    #[Test]
    public function homePagePageNotAllowed(): void
    {
        $client = self::createClient();
        $client->loginUser(GetUserToken::getFakeUserToken(), 'web');

        $client->request('GET', '/fr/trainer/home_page');

        $this->assertResponseStatusCodeSame(403);
    }

    #[Test]
    public function homePagePageNotConnected(): void
    {
        $client = self::createClient();

        $client->request('GET', '/fr/trainer/home_page');

        $this->assertResponseRedirects();
    }

    private function trainerClient(string $identifier): KernelBrowser
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken($identifier);
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        return $client;
    }
}
