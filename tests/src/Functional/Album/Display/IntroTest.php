<?php

declare(strict_types=1);

namespace App\Tests\Functional\Album\Display;

use App\Controller\AlbumIndexController;
use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(AlbumIndexController::class)]
class IntroTest extends WebTestCase
{
    use TestNavTrait;

    public function testIntroHome(): void
    {
        $client = static::createClient();

        $user = new User('12', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/home');

        $this->assertCountFilter($crawler, 1, 'h1#album-title');
        $this->assertEquals(
            'Home',
            $crawler->filter('h1#album-title')->text()
        );

        $this->assertCountFilter($crawler, 0, 'h2#album-subtitle');

        $this->assertCountFilter($crawler, 1, '#album-description');
        $this->assertStringContainsString(
            'Tous les pokémons pouvant être transférés sur Pokémon Home.',
            $crawler->filter('#album-description')->text()
        );
        $this->assertStringContainsString(
            'Incluant les mâles/femelles, les formes différentes et les transformations',
            $crawler->filter('#album-description')->text()
        );

        $this->assertCountFilter($crawler, 1, '#intro .album-all-catch-state-read-action');
        $this->assertCountFilter($crawler, 1, '#intro .album-all-catch-state-edit-action');

        $this->assertCountFilter($crawler, 2, '#intro .screenshot-mode');
        $this->assertCountFilter($crawler, 1, '#intro .screenshot-mode.screenshot-mode-on');
        $this->assertCountFilter($crawler, 1, '#intro .screenshot-mode.screenshot-mode-off');

        $this->assertCountFilter($crawler, 1, '#intro .goto');
        $this->assertCountFilter($crawler, 1, '#intro .goto.goto-box1');
        $this->assertCountFilter($crawler, 0, '#intro .goto.goto-topofthelist');

        $this->assertCountFilter($crawler, 0, '#intro .share');

        $this->assertCountFilter($crawler, 0, '#intro .album-private');
        $this->assertCountFilter($crawler, 0, '#intro .album-another-trainer');
        $this->assertCountFilter($crawler, 0, '#intro .dex-type');
        $this->assertCountFilter($crawler, 0, '#intro .dex-shiny-or-not');
        $this->assertCountFilter($crawler, 0, '#intro .dex-template');
        $this->assertCountFilter($crawler, 0, '#intro .dex-version');
    }

    public function testIntroDemoList3(): void
    {
        $client = static::createClient();

        $user = new User('12', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/demolist3');

        $this->assertCountFilter($crawler, 1, 'h1#album-title');
        $this->assertEquals(
            'Démo',
            $crawler->filter('h1#album-title')->text()
        );

        $this->assertCountFilter($crawler, 0, 'h2#album-subtitle');

        $this->assertCountFilter($crawler, 1, '#album-description');
        $this->assertEquals(
            'Tous les pokémons de la démo affiché en liste, 3 éléments par colonnes',
            $crawler->filter('#album-description')->text()
        );

        $this->assertCountFilter($crawler, 1, '#intro .album-all-catch-state-read-action');
        $this->assertCountFilter($crawler, 1, '#intro .album-all-catch-state-edit-action');

        $this->assertCountFilter($crawler, 2, '#intro .screenshot-mode');
        $this->assertCountFilter($crawler, 1, '#intro .screenshot-mode.screenshot-mode-on');
        $this->assertCountFilter($crawler, 1, '#intro .screenshot-mode.screenshot-mode-off');

        $this->assertCountFilter($crawler, 1, '#intro .goto');
        $this->assertCountFilter($crawler, 0, '#intro .goto.goto-box1');
        $this->assertCountFilter($crawler, 1, '#intro .goto.goto-topofthelist');

        $this->assertCountFilter($crawler, 0, '#intro .share');

        $this->assertCountFilter($crawler, 0, '#intro .album-private');
        $this->assertCountFilter($crawler, 0, '#intro .album-another-trainer');
        $this->assertCountFilter($crawler, 0, '#intro .dex-type');
        $this->assertCountFilter($crawler, 0, '#intro .dex-shiny-or-not');
        $this->assertCountFilter($crawler, 0, '#intro .dex-template');
        $this->assertCountFilter($crawler, 0, '#intro .dex-version');
    }

    public function testIntroDemoLiteShiny(): void
    {
        $client = static::createClient();

        $user = new User('12', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/demoliteshiny');

        $this->assertCountFilter($crawler, 1, 'h1#album-title');
        $this->assertEquals(
            'Démo, extrait',
            $crawler->filter('h1#album-title')->text()
        );

        $this->assertCountFilter($crawler, 1, 'h2#album-subtitle');
        $this->assertEquals(
            'chromatique',
            $crawler->filter('h2#album-subtitle')->text()
        );

        $this->assertCountFilter($crawler, 1, '#intro .album-all-catch-state-read-action');
        $this->assertCountFilter($crawler, 1, '#intro .album-all-catch-state-edit-action');

        $this->assertCountFilter($crawler, 2, '#intro .screenshot-mode');
        $this->assertCountFilter($crawler, 1, '#intro .screenshot-mode.screenshot-mode-on');
        $this->assertCountFilter($crawler, 1, '#intro .screenshot-mode.screenshot-mode-off');

        $this->assertCountFilter($crawler, 1, '#intro .goto');
        $this->assertCountFilter($crawler, 1, '#intro .goto.goto-box1');
        $this->assertCountFilter($crawler, 0, '#intro .goto.goto-topofthelist');

        $this->assertCountFilter($crawler, 1, '#intro .share');
        $this->assertEquals(
            '/fr/album/demoliteshiny?t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $crawler->filter('#intro .share')->attr('href')
        );

        $this->assertCountFilter($crawler, 0, '#intro .album-private');
        $this->assertCountFilter($crawler, 0, '#intro .album-another-trainer');
        $this->assertCountFilter($crawler, 0, '#intro .dex-type');
        $this->assertCountFilter($crawler, 0, '#intro .dex-shiny-or-not');
        $this->assertCountFilter($crawler, 0, '#intro .dex-template');
        $this->assertCountFilter($crawler, 0, '#intro .dex-version');
    }

    public function testIntroGoldSilverCrystal(): void
    {
        $client = static::createClient();

        $user = new User('12', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/goldsilvercrystal');

        $this->assertCountFilter($crawler, 1, 'h1#album-title');
        $this->assertEquals(
            'Or, Argent, Cristal',
            $crawler->filter('h1#album-title')->text()
        );

        $this->assertCountFilter($crawler, 0, 'h2#album-subtitle');

        $this->assertCountFilter($crawler, 1, '#album-description');
        $this->assertStringContainsString(
            'La liste des pokémons obtenable dans les jeux Or, Argent et Cristal.',
            $crawler->filter('#album-description')->text()
        );
        $this->assertStringContainsString(
            "Seul les Zarbi ont des formes différentes, seulement les 26 lettres de l'alphabet.",
            $crawler->filter('#album-description')->text()
        );

        $this->assertCountFilter($crawler, 1, '#intro .album-all-catch-state-read-action');
        $this->assertCountFilter($crawler, 1, '#intro .album-all-catch-state-edit-action');

        $this->assertCountFilter($crawler, 2, '#intro .screenshot-mode');
        $this->assertCountFilter($crawler, 1, '#intro .screenshot-mode.screenshot-mode-on');
        $this->assertCountFilter($crawler, 1, '#intro .screenshot-mode.screenshot-mode-off');

        $this->assertCountFilter($crawler, 1, '#intro .goto');
        $this->assertCountFilter($crawler, 1, '#intro .goto.goto-box1');
        $this->assertCountFilter($crawler, 0, '#intro .goto.goto-topofthelist');

        $this->assertCountFilter($crawler, 0, '#intro .share');

        $this->assertCountFilter($crawler, 0, '#intro .album-private');
        $this->assertCountFilter($crawler, 0, '#intro .album-another-trainer');
        $this->assertCountFilter($crawler, 0, '#intro .dex-type');
        $this->assertCountFilter($crawler, 0, '#intro .dex-shiny-or-not');
        $this->assertCountFilter($crawler, 0, '#intro .dex-template');
        $this->assertCountFilter($crawler, 0, '#intro .dex-version');
    }

    public function testIntroBlackWhiteFrench(): void
    {
        $client = static::createClient();

        $user = new User('12', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/blackwhite');

        $this->assertCountFilter($crawler, 1, 'h1#album-title');
        $this->assertEquals(
            'Noire, Blanche',
            $crawler->filter('h1#album-title')->text()
        );

        $this->assertCountFilter($crawler, 0, 'h2#album-subtitle');

        $this->assertCountFilter($crawler, 1, '#album-description');
        $this->assertStringContainsString(
            'La liste des pokémons obtenable dans les jeux Noire et Blanche.',
            $crawler->filter('#album-description')->text()
        );
        $this->assertStringContainsString(
            'Les pokémons ont des formes différentes en fonction du genre ou pas.',
            $crawler->filter('#album-description')->text()
        );

        $this->assertCountFilter($crawler, 1, '#intro .album-all-catch-state-read-action');
        $this->assertCountFilter($crawler, 1, '#intro .album-all-catch-state-edit-action');

        $this->assertCountFilter($crawler, 2, '#intro .screenshot-mode');
        $this->assertCountFilter($crawler, 1, '#intro .screenshot-mode.screenshot-mode-on');
        $this->assertCountFilter($crawler, 1, '#intro .screenshot-mode.screenshot-mode-off');

        $this->assertCountFilter($crawler, 1, '#intro .goto');
        $this->assertCountFilter($crawler, 1, '#intro .goto.goto-box1');
        $this->assertCountFilter($crawler, 0, '#intro .goto.goto-topofthelist');

        $this->assertCountFilter($crawler, 0, '#intro .share');

        $this->assertCountFilter($crawler, 0, '#intro .album-private');
        $this->assertCountFilter($crawler, 0, '#intro .album-another-trainer');
        $this->assertCountFilter($crawler, 0, '#intro .dex-type');
        $this->assertCountFilter($crawler, 0, '#intro .dex-shiny-or-not');
        $this->assertCountFilter($crawler, 0, '#intro .dex-template');
        $this->assertCountFilter($crawler, 0, '#intro .dex-version');
    }

    public function testIntroBlackWhiteEnglish(): void
    {
        $client = static::createClient();

        $user = new User('12', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/en/album/blackwhite');

        $this->assertCountFilter($crawler, 1, 'h1#album-title');
        $this->assertEquals(
            'Black, White',
            $crawler->filter('h1#album-title')->text()
        );

        $this->assertCountFilter($crawler, 0, 'h2#album-subtitle');

        $this->assertCountFilter($crawler, 1, '#album-description');
        $this->assertStringContainsString(
            'The list of obtainable Pokémons in Black and White games.',
            $crawler->filter('#album-description')->text()
        );
        $this->assertStringContainsString(
            'Pokémons have different shapes depending on the gender or not.',
            $crawler->filter('#album-description')->text()
        );

        $this->assertCountFilter($crawler, 1, '#intro .album-all-catch-state-read-action');
        $this->assertCountFilter($crawler, 1, '#intro .album-all-catch-state-edit-action');

        $this->assertCountFilter($crawler, 2, '#intro .screenshot-mode');
        $this->assertCountFilter($crawler, 1, '#intro .screenshot-mode.screenshot-mode-on');
        $this->assertCountFilter($crawler, 1, '#intro .screenshot-mode.screenshot-mode-off');

        $this->assertCountFilter($crawler, 1, '#intro .goto');
        $this->assertCountFilter($crawler, 1, '#intro .goto.goto-box1');
        $this->assertCountFilter($crawler, 0, '#intro .goto.goto-topofthelist');

        $this->assertCountFilter($crawler, 0, '#intro .share');

        $this->assertCountFilter($crawler, 0, '#intro .album-private');
        $this->assertCountFilter($crawler, 0, '#intro .album-another-trainer');
        $this->assertCountFilter($crawler, 0, '#intro .dex-type');
        $this->assertCountFilter($crawler, 0, '#intro .dex-shiny-or-not');
        $this->assertCountFilter($crawler, 0, '#intro .dex-template');
        $this->assertCountFilter($crawler, 0, '#intro .dex-version');
    }

    public function testIntroDemoAnotherTrainer(): void
    {
        $client = static::createClient();

        $user = new User('13', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/demo?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertCountFilter($crawler, 1, 'h1#album-title');
        $this->assertEquals(
            'Démo',
            $crawler->filter('h1#album-title')->text()
        );

        $this->assertCountFilter($crawler, 0, 'h2#album-subtitle');

        $this->assertCountFilter($crawler, 0, '#intro .album-all-catch-state-read-action');
        $this->assertCountFilter($crawler, 0, '#intro .album-all-catch-state-edit-action');

        $this->assertCountFilter($crawler, 2, '#intro .screenshot-mode');
        $this->assertCountFilter($crawler, 1, '#intro .screenshot-mode.screenshot-mode-on');
        $this->assertCountFilter($crawler, 1, '#intro .screenshot-mode.screenshot-mode-off');

        $this->assertCountFilter($crawler, 1, '#intro .goto');
        $this->assertCountFilter($crawler, 1, '#intro .goto.goto-box1');
        $this->assertCountFilter($crawler, 0, '#intro .goto.goto-topofthelist');

        $this->assertCountFilter($crawler, 1, '#intro .share');
        $this->assertEquals(
            '/fr/album/demo?t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $crawler->filter('#intro .share')->attr('href')
        );

        $this->assertCountFilter($crawler, 0, '#intro .album-private');
        $this->assertCountFilter($crawler, 0, '#intro .album-another-trainer');
        $this->assertCountFilter($crawler, 0, '#intro .dex-type');
        $this->assertCountFilter($crawler, 0, '#intro .dex-shiny-or-not');
        $this->assertCountFilter($crawler, 0, '#intro .dex-template');
        $this->assertCountFilter($crawler, 0, '#intro .dex-version');
    }
}
