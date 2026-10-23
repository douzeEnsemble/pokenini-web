<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Game;

use App\Controller\WhichShinyGameController;
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
#[CoversClass(WhichShinyGameController::class)]
#[Group('api-mocked-testing')]
final class WhichShinyGamePlayTest extends WebTestCase
{
    use TestNavTrait;

    private const string IN_PROGRESS = '11111111-1111-4111-8111-111111111111';
    private const string COMPLETED = '22222222-2222-4222-8222-222222222222';
    private const string ABANDONED = '44444444-4444-4444-8444-444444444444';
    private const string CONFLICT = '55555555-5555-4555-8555-555555555555';
    private const string LAST_ROUND = '66666666-6666-4666-8666-666666666666';
    private const string FORMS = '77777777-7777-4777-8777-777777777777';
    private const string UNKNOWN = '99999999-9999-4999-8999-999999999999';

    #[Test]
    public function play(): void
    {
        $client = $this->createTrainerClient();

        $crawler = $client->request('GET', '/fr/game/which-shiny/'.self::IN_PROGRESS);

        $this->assertResponseIsSuccessful();

        $this->assertSame('Pokénini Lequel est le shiny ?', $crawler->filter('title')->text());
        $this->assertSame('Démo', $crawler->filter('h2 small')->text());
        $this->assertCountFilter($crawler, 1, '.navbar-nav .game-link a.nav-link.active');
        $this->assertCountFilter($crawler, 1, '.navbar-nav .game-link .dropdown-item.active');
        $this->assertSame('/fr/game/which-shiny', $crawler->filter('.navbar-nav .game-link .dropdown-item.active')->attr('href'));

        $this->assertSame('1 manche jouée sur 3', $crawler->filter('.color-game-progress + p')->text());
        $this->assertSame('1 bonne réponse sur 1', $crawler->filter('.game-bar .color-game-score')->text());

        $this->assertSame('Manche 2 sur 3', $crawler->filter('.game-bar .round-position')->text());
        $this->assertSame('2/3', $crawler->filter('.game-bar .round-position-short')->text());
        $this->assertSame('1/1', $crawler->filter('.game-bar .game-score-short')->text());
        $this->assertSame('Lequel est le shiny ?', $crawler->filter('.game-bar h1')->text());
        $this->assertCountFilter($crawler, 1, '.game-bar .color-game-progress + p.visually-hidden');
        $this->assertCountFilter($crawler, 1, '.game-bar a.stop-game');
        $this->assertCountFilter($crawler, 0, '.game-is-shiny');
        $this->assertSame('Herbizarre', $crawler->filter('.pokemon-name')->text());
        $this->assertCountFilter($crawler, 0, '.pokemon-forms');
        $this->assertSame('Lequel est le shiny ?', $crawler->filter('.color-game-status h4')->text());

        // The images URL must not tell which one is the shiny
        $this->assertSame(
            [
                '/fr/game/which-shiny/'.self::IN_PROGRESS.'/rounds/2/images/1',
                '/fr/game/which-shiny/'.self::IN_PROGRESS.'/rounds/2/images/2',
            ],
            $crawler->filter('.round-image')->each(static fn (Crawler $node): ?string => $node->attr('src')),
        );
        $this->assertSame(['Image 1', 'Image 2'], $crawler->filter('.round-image')->each(static fn (Crawler $node): ?string => $node->attr('alt')));
        $this->assertStringNotContainsString('pokemon/big', (string) $client->getResponse()->getContent());

        $this->assertCountFilter($crawler, 2, '.which-shiny-choices button.which-shiny-choice');
        $this->assertSame(['1', '2'], $crawler->filter('.which-shiny-choice')->each(static fn (Crawler $node): ?string => $node->attr('value')));
        $this->assertSame(['1', '2'], $crawler->filter('.which-shiny-choice')->each(static fn (Crawler $node): ?string => $node->attr('data-key')));
        $this->assertSame(['1', '2'], $crawler->filter('.which-shiny-choice .choice-key')->each(static fn (Crawler $node): string => $node->text()));
        $this->assertSame(['Clique encore pour valider', 'Clique encore pour valider'], $crawler->filter('.which-shiny-choice .choice-confirm')->each(static fn (Crawler $node): string => $node->text()));
        $this->assertSame(['false', 'false'], $crawler->filter('.which-shiny-choice')->each(static fn (Crawler $node): ?string => $node->attr('aria-pressed')));
        $this->assertCountFilter($crawler, 2, '.which-shiny-choice.game-choice');
        $this->assertSame('2', $crawler->filter('.which-shiny-choices input[name="position"]')->attr('value'));
        $this->assertSame("Astuce : clique une fois sur une image pour la choisir, une seconde fois pour valider. Au clavier, la touche 1 choisit l'image de gauche, la touche 2 celle de droite et Entrée valide.", $crawler->filter('.which-shiny-choices + p')->text());

        $this->assertCountFilter($crawler, 0, '.which-shiny-answers');
        $this->assertSame('/fr/game/which-shiny', $crawler->filter('a.stop-game')->attr('href'));
    }

    #[Test]
    public function playWithForms(): void
    {
        $client = $this->createTrainerClient();

        $crawler = $client->request('GET', '/en/game/which-shiny/'.self::FORMS);

        $this->assertResponseIsSuccessful();

        $this->assertSame('Alolan Raichu', $crawler->filter('.pokemon-name')->text());
        $this->assertSame('Alolan form', $crawler->filter('.pokemon-forms')->text());
        $this->assertSame('Which one is shiny?', $crawler->filter('.color-game-status h4')->text());
        $this->assertSame(['Image 1', 'Image 2'], $crawler->filter('.round-image')->each(static fn (Crawler $node): ?string => $node->attr('alt')));
        $this->assertSame('Tip: click an image once to pick it, a second time to confirm. On the keyboard, key 1 picks the left image, key 2 the right one and Enter confirms.', $crawler->filter('.which-shiny-choices + p')->text());
    }

    #[Test]
    public function playCompleted(): void
    {
        $client = $this->createTrainerClient();

        $crawler = $client->request('GET', '/fr/game/which-shiny/'.self::COMPLETED);

        $this->assertResponseIsSuccessful();

        $this->assertCountFilter($crawler, 0, '.which-shiny-choices');
        $this->assertCountFilter($crawler, 0, '.stop-game');
        $this->assertSame('Partie terminée !', $crawler->filter('.game-over h3')->text());
        $this->assertSame('1 bonne réponse sur 2', $crawler->filter('.game-over .lead')->text());
        $this->assertSame('redgreenblueyellow', $crawler->filter('.game-over input[name="dex_slug"]')->attr('value'));
        $this->assertSame('/fr/game/which-shiny', $crawler->filter('.game-over form')->attr('action'));
        $this->assertSame('/fr/game/which-shiny', $crawler->filter('.game-over a')->attr('href'));
        $this->assertSame(
            "Pokénini · Lequel est le shiny ?\nRouge / Vert / Bleu / Jaune · 1/2 (50 %)\n🟥🟩\nhttp://localhost/fr/game/which-shiny",
            $crawler->filter('.game-over .game-share')->attr('data-share-text'),
        );
    }

    #[Test]
    public function playAbandoned(): void
    {
        $client = $this->createTrainerClient();

        $crawler = $client->request('GET', '/fr/game/which-shiny/'.self::ABANDONED);

        $this->assertResponseIsSuccessful();

        $this->assertCountFilter($crawler, 0, '.which-shiny-choices');
        $this->assertCountFilter($crawler, 0, '.pokemon-name');
        $this->assertSame('Partie abandonnée', $crawler->filter('.game-over h3')->text());
        // No answered round: nothing to share
        $this->assertCountFilter($crawler, 0, '.game-share');
    }

    #[Test]
    public function playUnknown(): void
    {
        $client = $this->createTrainerClient();

        $client->request('GET', '/fr/game/which-shiny/'.self::UNKNOWN);

        $this->assertResponseStatusCodeSame(404);
    }

    #[Test]
    public function playNotAnUuid(): void
    {
        $client = $this->createTrainerClient();

        $client->request('GET', '/fr/game/which-shiny/douze');

        $this->assertResponseStatusCodeSame(404);
    }

    #[Test]
    public function answerWrong(): void
    {
        $client = $this->createTrainerClient();

        $crawler = $client->request('GET', '/fr/game/which-shiny/'.self::IN_PROGRESS);
        $form = $crawler->filter('.which-shiny-choice')->eq(1)->form();
        $client->submit($form);

        $this->assertResponseRedirects('/fr/game/which-shiny/'.self::IN_PROGRESS.'/rounds/2/answer');

        $crawler = $client->followRedirect();

        $this->assertResponseIsSuccessful();

        $this->assertSame('Raté !', $crawler->filter('.answer-result')->text());
        $this->assertStringContainsString('alert-danger', (string) $crawler->filter('.answer-box')->attr('class'));
        $this->assertSame('2 manches jouées sur 3', $crawler->filter('.color-game-progress + p')->text());
        $this->assertSame('Herbizarre', $crawler->filter('.pokemon-name')->text());

        $this->assertCountFilter($crawler, 0, '.which-shiny-choices');
        $this->assertCountFilter($crawler, 0, '.round-image');
        // The game can be paused after an answer too
        $this->assertSame('/fr/game/which-shiny', $crawler->filter('.game-bar a.stop-game')->attr('href'));
        $this->assertSame('Manche 2 sur 3', $crawler->filter('.game-bar .round-position')->text());
        $this->assertSame(['1', '2'], $crawler->filter('.which-shiny-version .choice-key')->each(static fn (Crawler $node): string => $node->text()));
        $this->assertCountFilter($crawler, 2, '.which-shiny-answers .which-shiny-version');

        // The shiny was the first image, the trainer chose the second one
        $this->assertSame(
            [
                'https://icon.pokenini.fr/pokemon/big/shiny/ivysaur.png',
                'https://icon.pokenini.fr/pokemon/big/regular/ivysaur.png',
            ],
            $crawler->filter('.which-shiny-version img')->each(static fn (Crawler $node): ?string => $node->attr('src')),
        );
        $this->assertSame(
            ['Herbizarre Chromatique', 'Herbizarre Normal'],
            $crawler->filter('.which-shiny-version img')->each(static fn (Crawler $node): ?string => $node->attr('alt')),
        );
        $this->assertSame(['Chromatique', 'Normal'], $crawler->filter('.version-name')->each(static fn (Crawler $node): string => $node->text()));

        $shiny = $crawler->filter('.which-shiny-version')->eq(0);
        $this->assertStringContainsString(' shiny-version', (string) $shiny->attr('class'));
        $this->assertStringNotContainsString('chosen-version', (string) $shiny->attr('class'));
        $this->assertCountFilter($shiny, 1, '.bi-stars');
        $this->assertCountFilter($shiny, 0, '.version-label');

        $regular = $crawler->filter('.which-shiny-version')->eq(1);
        $this->assertStringContainsString('chosen-version', (string) $regular->attr('class'));
        $this->assertCountFilter($regular, 0, '.bi-stars');
        $this->assertSame('Ton choix', $regular->filter('.version-label')->text());
        $this->assertStringContainsString('text-bg-danger', (string) $regular->filter('.badge')->attr('class'));

        $this->assertCountFilter($crawler, 1, '.shiny-version');
        $this->assertCountFilter($crawler, 1, '.chosen-version');

        $this->assertSame('Manche suivante', trim($crawler->filter('a.next-round')->text()));
        $this->assertSame('/fr/game/which-shiny/'.self::IN_PROGRESS, $crawler->filter('a.next-round')->attr('href'));
    }

    #[Test]
    public function answerCorrect(): void
    {
        $client = $this->createTrainerClient();

        $crawler = $client->request('GET', '/en/game/which-shiny/'.self::IN_PROGRESS);
        $form = $crawler->filter('.which-shiny-choice')->eq(0)->form();
        $client->submit($form);

        $this->assertResponseRedirects('/en/game/which-shiny/'.self::IN_PROGRESS.'/rounds/2/answer');

        $crawler = $client->followRedirect();

        $this->assertResponseIsSuccessful();

        $this->assertSame('Well done!', $crawler->filter('.answer-result')->text());
        $this->assertStringContainsString('alert-success', (string) $crawler->filter('.answer-box')->attr('class'));
        $this->assertSame('2 right answers out of 2', $crawler->filter('.game-bar .color-game-score')->text());
        $this->assertCountFilter($crawler, 0, '.chosen-version');
        $this->assertCountFilter($crawler, 1, '.version-label');
        $this->assertSame('Your choice', $crawler->filter('.shiny-version .version-label')->text());
        $this->assertStringContainsString('text-bg-success', (string) $crawler->filter('.shiny-version .badge')->attr('class'));
        $this->assertSame('Shiny', $crawler->filter('.shiny-version .version-name')->text());
        $this->assertSame(['Shiny', 'Regular'], $crawler->filter('.version-name')->each(static fn (Crawler $node): string => $node->text()));
    }

    #[Test]
    public function answerLastRound(): void
    {
        $client = $this->createTrainerClient();

        $crawler = $client->request('GET', '/fr/game/which-shiny/'.self::LAST_ROUND);
        $form = $crawler->filter('.which-shiny-choice')->eq(1)->form();
        $client->submit($form);

        $this->assertResponseRedirects('/fr/game/which-shiny/'.self::LAST_ROUND.'/rounds/3/answer');

        $crawler = $client->followRedirect();

        $this->assertResponseIsSuccessful();

        $this->assertSame('Bien vu !', $crawler->filter('.answer-result')->text());
        $this->assertSame('3 manches jouées sur 3', $crawler->filter('.color-game-progress + p')->text());
        $this->assertSame(['Normal', 'Chromatique'], $crawler->filter('.version-name')->each(static fn (Crawler $node): string => $node->text()));
        $this->assertSame('Chromatique', $crawler->filter('.shiny-version .version-name')->text());
        $this->assertSame('Voir le résultat', trim($crawler->filter('a.next-round')->text()));
    }

    #[Test]
    public function answerRefusedByTheBack(): void
    {
        $client = $this->createTrainerClient();

        $crawler = $client->request('GET', '/fr/game/which-shiny/'.self::CONFLICT);
        $form = $crawler->filter('.which-shiny-choice')->eq(0)->form();
        $client->submit($form);

        $this->assertResponseRedirects('/fr/game/which-shiny/'.self::CONFLICT);
    }

    #[Test]
    public function answerAnotherRound(): void
    {
        $client = $this->createTrainerClient();

        $crawler = $client->request('GET', '/fr/game/which-shiny/'.self::IN_PROGRESS);
        $form = $crawler->filter('.which-shiny-choice')->eq(0)->form();
        $form['position'] = '1';
        $client->submit($form);

        $this->assertResponseRedirects('/fr/game/which-shiny/'.self::IN_PROGRESS);
    }

    #[Test]
    public function answerFinishedGame(): void
    {
        $client = $this->createTrainerClient();

        $crawler = $client->request('GET', '/fr/game/which-shiny/'.self::IN_PROGRESS);
        $token = $crawler->filter('.which-shiny-choices input[name="_token"]')->attr('value');

        $client->request('POST', '/fr/game/which-shiny/'.self::COMPLETED, ['_token' => $token, 'position' => '1', 'slot' => '1']);

        $this->assertResponseRedirects('/fr/game/which-shiny/'.self::COMPLETED);
    }

    #[Test]
    public function answerUnknownGame(): void
    {
        $client = $this->createTrainerClient();

        $crawler = $client->request('GET', '/fr/game/which-shiny/'.self::IN_PROGRESS);
        $token = $crawler->filter('.which-shiny-choices input[name="_token"]')->attr('value');

        $client->request('POST', '/fr/game/which-shiny/'.self::UNKNOWN, ['_token' => $token, 'position' => '1', 'slot' => '1']);

        $this->assertResponseStatusCodeSame(404);
    }

    #[Test]
    public function answerInvalidToken(): void
    {
        $client = $this->createTrainerClient();

        $client->request('POST', '/fr/game/which-shiny/'.self::IN_PROGRESS, ['_token' => 'nope', 'position' => '2', 'slot' => '1']);

        $this->assertResponseStatusCodeSame(403);
    }

    #[Test]
    public function create(): void
    {
        $client = $this->createTrainerClient();

        $crawler = $client->request('GET', '/fr/game/which-shiny');
        $form = $crawler->filter('.dex-item')->eq(0)->filter('form')->form();
        $client->submit($form);

        $this->assertResponseRedirects('/fr/game/which-shiny/33333333-3333-4333-8333-333333333333');
    }

    #[Test]
    public function createWithPresetRoundCount(): void
    {
        $client = $this->createTrainerClient();

        $crawler = $client->request('GET', '/fr/game/which-shiny');
        $form = $crawler->filter('.dex-item')->eq(1)->filter('button.round-count')->eq(0)->form();
        $form['dex_slug'] = 'demo';
        $client->submit($form);

        $this->assertResponseRedirects('/fr/game/which-shiny/44444444-4444-4444-8444-444444444444');
    }

    #[Test]
    public function createWithCustomRoundCount(): void
    {
        $client = $this->createTrainerClient();

        $crawler = $client->request('GET', '/fr/game/which-shiny');
        $form = $crawler->filter('.dex-item')->eq(1)->filter('.round-count-custom')->form();
        $form['dex_slug'] = 'demo';
        $form['round_count'] = '10';
        $client->submit($form);

        $this->assertResponseRedirects('/fr/game/which-shiny/44444444-4444-4444-8444-444444444444');
    }

    #[Test]
    public function createWithTheWholeDex(): void
    {
        $client = $this->createTrainerClient();

        $crawler = $client->request('GET', '/fr/game/which-shiny');
        $form = $crawler->filter('.dex-item')->eq(1)->filter('button.round-count-all')->form();
        $form['dex_slug'] = 'demo';
        $client->submit($form);

        $this->assertResponseRedirects('/fr/game/which-shiny/33333333-3333-4333-8333-333333333333');
    }

    #[Test]
    public function createFailed(): void
    {
        $client = $this->createTrainerClient();

        $crawler = $client->request('GET', '/fr/game/which-shiny');
        $form = $crawler->filter('.dex-item')->eq(0)->filter('form')->form();
        $form['dex_slug'] = 'homepogo';
        $client->submit($form);

        $this->assertResponseRedirects('/fr/game/which-shiny');

        $crawler = $client->followRedirect();
        $this->assertSame(
            "La partie n'a pas pu être créée. Réessaie un peu plus tard.",
            $crawler->filter('.alert-warning')->text(),
        );
    }

    #[Test]
    public function createInvalidToken(): void
    {
        $client = $this->createTrainerClient();

        $client->request('POST', '/fr/game/which-shiny', ['_token' => 'nope', 'dex_slug' => 'demo']);

        $this->assertResponseStatusCodeSame(403);
    }

    private function createTrainerClient(): KernelBrowser
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        return $client;
    }
}
