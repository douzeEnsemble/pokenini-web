<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Game;

use App\Controller\ColorGameController;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @internal
 */
#[CoversClass(ColorGameController::class)]
#[Group('api-mocked-testing')]
final class ColorGameDexTest extends WebTestCase
{
    use TestNavTrait;

    #[Test]
    public function dex(): void
    {
        $client = $this->createTrainerClient();

        $crawler = $client->request('GET', '/fr/game/color');

        $this->assertResponseIsSuccessful();

        $this->assertSame('Pokénini Bonne Palette', $crawler->filter('title')->text());
        $this->assertSame('Bonne Palette', $crawler->filter('h1')->text());
        $this->assertCountFilter($crawler, 1, '.navbar-nav .game-link a.nav-link.active');
        $this->assertCountFilter($crawler, 1, '.navbar-nav .game-link .dropdown-item.active');
        $this->assertSame('/fr/game/color', $crawler->filter('.navbar-nav .game-link .dropdown-item.active')->attr('href'));

        $this->assertCountFilter($crawler, 4, '.dex-item');
        $this->assertSame(
            ['Démo', 'Rouge / Vert / Bleu / Jaune', 'Home Chromatique', 'Home Pokémon GO'],
            $crawler->filter('.dex-item .card-title')->each(static fn (Crawler $node): string => $node->text()),
        );
        $this->assertSame(
            ['Démo 1 dex', 'Jeux principaux 1 dex', 'Pokémon Home 2 dex'],
            $crawler->filter('.dex-category')->each(static fn (Crawler $node): string => $node->text()),
        );
        $this->assertCountFilter($crawler, 2, '#dex-category-home + .row .dex-item');
        $this->assertSame(
            ['Démo 1', 'Jeux principaux 1', 'Pokémon Home 2'],
            $crawler->filter('.dex-category-summary .dex-category-link')->each(static fn (Crawler $node): string => $node->text()),
        );
        $this->assertCountFilter($crawler, 2, '#dex-category-home-list.collapse.show .dex-item');
        $this->assertSame('Kanto', $crawler->filter('.dex-item')->eq(1)->filter('.card-subtitle')->text());
        $this->assertSame(
            ['4 Pokémons', '151 Pokémons', '2 Pokémons', '0 Pokémon'],
            $crawler->filter('.dex-item .playable-count')->each(static fn (Crawler $node): string => $node->text()),
        );
        $this->assertSame('Un petit dex pour essayer.', $crawler->filter('.dex-item')->eq(0)->filter('p.small.text-start')->text());
        $this->assertCountFilter($crawler, 1, '.dex-item p.small.text-start');

        $this->assertCountFilter($crawler, 1, '.dex-item .dex_is_shiny');
        $this->assertCountFilter($crawler, 1, '.dex-item .dex_is_premium');
        $this->assertCountFilter($crawler, 1, '.dex-item .dex_not_is_released');
        $this->assertCountFilter($crawler, 1, '.dex-item', 2, '.dex_is_shiny');

        $demo = $crawler->filter('.dex-item')->eq(0);
        $this->assertCount(1, $demo->filter('.current-game'));
        $this->assertSame('1 manche jouée sur 3', $demo->filter('.current-game p.small')->eq(0)->text());
        $this->assertSame('1 bonne réponse sur 1', $demo->filter('.current-game .color-game-score')->text());
        $this->assertSame('width: 33%', $demo->filter('.progress-bar')->attr('style'));
        $this->assertSame(
            '/fr/game/color/11111111-1111-4111-8111-111111111111',
            $demo->filter('a.resume-game')->attr('href'),
        );
        $this->assertSame('Continuer', trim($demo->filter('a.resume-game')->text()));
        $this->assertSame('Nouvelle partie', $demo->filter('.new-game')->text());
        $this->assertSame('demo', $demo->filter('input[name="dex_slug"]')->attr('value'));

        $kanto = $crawler->filter('.dex-item')->eq(1);
        $this->assertCount(0, $kanto->filter('.current-game'));
        $this->assertCount(0, $kanto->filter('a.resume-game'));
        $this->assertSame('Jouer', $kanto->filter('.new-game')->text());

        // The number of rounds is chosen before starting
        $this->assertSame('Combien de manches ?', $kanto->filter('.round-count-menu .dropdown-header')->text());
        $this->assertSame(
            ['10 manches', '20 manches', '50 manches'],
            $kanto->filter('button.round-count')->each(static fn (Crawler $node): string => $node->text()),
        );
        $this->assertSame(
            ['10', '20', '50'],
            $kanto->filter('button.round-count')->each(static fn (Crawler $node): string => (string) $node->attr('value')),
        );
        $this->assertSame('Toute la liste (151)', $kanto->filter('button.round-count-all')->text());
        $this->assertSame('', $kanto->filter('button.round-count-all')->attr('value'));
        $this->assertSame('151', $kanto->filter('.round-count-custom input[name="round_count"]')->attr('max'));

        // Too few Pokémon to choose: the whole dex is played
        $this->assertCount(0, $demo->filter('.round-count-menu'));
        $this->assertCount(1, $demo->filter('form'));

        $pogo = $crawler->filter('.dex-item')->eq(3);
        $this->assertCount(0, $pogo->filter('form'));
        $this->assertSame('Pas encore de couleurs pour ce dex.', $pogo->filter('.no-playable')->text());

        $this->assertSame('Tes parties', $crawler->filter('h2.mt-5')->text());
        $this->assertCountFilter($crawler, 3, '.color-game-history tbody tr');
        $firstRow = $crawler->filter('.color-game-history tbody tr')->eq(0);
        $this->assertSame(
            ['20/10/2026 10:00', 'Démo', 'En cours'],
            $firstRow->filter('td')->slice(0, 3)->each(static fn (Crawler $node): string => $node->text()),
        );
        $this->assertSame('1 bonne réponse sur 1 (1 manche jouée sur 3)', $firstRow->filter('td')->eq(3)->text());
        $this->assertSame(
            '/fr/game/color/11111111-1111-4111-8111-111111111111',
            $firstRow->filter('a')->attr('href'),
        );

        $secondRow = $crawler->filter('.color-game-history tbody tr')->eq(1);
        $this->assertSame('Terminée', $secondRow->filter('td')->eq(2)->text());
        $this->assertCount(0, $secondRow->filter('a'));

        $thirdRow = $crawler->filter('.color-game-history tbody tr')->eq(2);
        $this->assertSame('Rouge / Vert / Bleu / Jaune', $thirdRow->filter('td')->eq(1)->text());
        $this->assertSame('Abandonnée', $thirdRow->filter('td')->eq(2)->text());
        $this->assertSame('Aucune bonne réponse sur 0 (0 manche jouée sur 151)', $thirdRow->filter('td')->eq(3)->text());
    }

    #[Test]
    public function dexShare(): void
    {
        $client = $this->createTrainerClient();

        $crawler = $client->request('GET', '/fr/game/color');

        $this->assertResponseIsSuccessful();

        $rows = $crawler->filter('.color-game-history tbody tr');
        $this->assertSame(
            "Pokénini · Bonne Palette\nDémo · 1/1 (100 %)\n🟩\nhttp://localhost/fr/game/color",
            $rows->eq(0)->filter('.game-share-panel')->attr('data-share-grid'),
        );
        $this->assertSame(
            "Pokénini · Bonne Palette ✨\nHome Chromatique · 1/2 (50 %)\n🟥🟩\nhttp://localhost/fr/game/color",
            $rows->eq(1)->filter('.game-share-panel')->attr('data-share-grid'),
        );
        $this->assertSame(
            "Pokénini · Bonne Palette\nDémo · 1/1 (100 %)\nhttp://localhost/fr/game/color",
            $rows->eq(0)->filter('.game-share-panel')->attr('data-share-score'),
        );
        $this->assertSame(
            '#game-share-11111111-1111-4111-8111-111111111111',
            $rows->eq(0)->filter('.game-share-open')->attr('data-bs-target'),
        );
        $this->assertSame('Partager ton résultat', $rows->eq(0)->filter('.game-share-modal .modal-title')->text());
        $this->assertCount(0, $rows->eq(0)->filter('.game-share-panel h4'));
        $this->assertCount(0, $rows->eq(2)->filter('.game-share'));
        $this->assertCount(0, $rows->eq(2)->filter('.game-share-open'));
        $this->assertCountFilter($crawler, 1, '#gameShareToastSuccess');
        $this->assertCountFilter($crawler, 1, 'script[src^="/js/game-share.js"]');
    }

    #[Test]
    public function dexEnglish(): void
    {
        $client = $this->createTrainerClient();

        $crawler = $client->request('GET', '/en/game/color');

        $this->assertResponseIsSuccessful();

        $this->assertSame('Palet Found', $crawler->filter('h1')->text());
        $this->assertSame('Demo', $crawler->filter('.dex-item .card-title')->eq(0)->text());
        $this->assertSame('A small dex to try.', $crawler->filter('.dex-item')->eq(0)->filter('p.small.text-start')->text());
        $this->assertSame('Red / Green / Blue / Yellow', $crawler->filter('.color-game-history tbody tr')->eq(2)->filter('td')->eq(1)->text());
        $this->assertSame(
            "Pokénini · Palet Found\nDemo · 1/1 (100%)\n🟩\nhttp://localhost/en/game/color",
            $crawler->filter('.color-game-history .game-share-panel')->attr('data-share-grid'),
        );
        $this->assertSame('1 right answer out of 1', $crawler->filter('.dex-item .current-game .color-game-score')->text());
    }

    #[Test]
    public function dexEmpty(): void
    {
        $client = $this->createTrainerClient('colorgamenone');

        $crawler = $client->request('GET', '/fr/game/color');

        $this->assertResponseIsSuccessful();

        $this->assertCountFilter($crawler, 0, '.dex-item');
        $this->assertCountFilter($crawler, 0, '.dex-category');
        $this->assertCountFilter($crawler, 0, '.dex-category-summary');
        $this->assertCountFilter($crawler, 0, '.dex-category-toggle');
        $this->assertSame("Y'a pas encore de dex pour jouer à Bonne Palette.", $crawler->filter('.no-dex')->text());
        $this->assertCountFilter($crawler, 0, '.color-game-history');
        $this->assertSame("Tu n'as pas encore joué.", $crawler->filter('.no-history')->text());
    }

    #[Test]
    public function dexNotTrainer(): void
    {
        $client = self::createClient();
        $client->loginUser(GetUserToken::getFakeUserToken(), 'web');

        $client->request('GET', '/fr/game/color');

        $this->assertResponseStatusCodeSame(403);
    }

    private function createTrainerClient(string $identifier = '789465465489'): KernelBrowser
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken($identifier);
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        return $client;
    }
}
