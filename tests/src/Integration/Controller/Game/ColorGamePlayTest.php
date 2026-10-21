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
final class ColorGamePlayTest extends WebTestCase
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

        $crawler = $client->request('GET', '/fr/game/color/'.self::IN_PROGRESS);

        $this->assertResponseIsSuccessful();

        $this->assertSame('Pokénini Jeu des couleurs', $crawler->filter('title')->text());
        $this->assertSame('Démo', $crawler->filter('h2 small')->text());
        $this->assertCountFilter($crawler, 0, '.game-is-shiny');
        $this->assertCountFilter($crawler, 1, '.navbar-nav .game-link a.active');

        $this->assertSame('1 manche jouée sur 3', $crawler->filter('.color-game-progress + p')->text());
        $this->assertSame('1 bonne réponse sur 1', $crawler->filter('.game-bar .color-game-score')->text());

        $this->assertSame('Manche 2 sur 3', $crawler->filter('.game-bar .round-position')->text());
        $this->assertSame('Jeu des couleurs', $crawler->filter('.game-bar h1')->text());
        $this->assertCountFilter($crawler, 1, '.game-bar .color-game-progress + p.visually-hidden');
        $this->assertCountFilter($crawler, 1, '.game-bar a.stop-game');
        $this->assertSame('Herbizarre', $crawler->filter('.pokemon-name')->text());
        $this->assertCountFilter($crawler, 0, '.pokemon-forms');

        $this->assertCountFilter($crawler, 3, '.color-choices button.color-choice');
        $this->assertSame(['0', '1', '2'], $crawler->filter('.color-choice')->each(static fn (Crawler $node): ?string => $node->attr('value')));
        $this->assertSame('Lot de couleurs 1', $crawler->filter('.color-choice')->eq(0)->attr('aria-label'));
        $this->assertCountFilter($crawler, 2, '.color-choice', 0, '.color-swatch');
        $this->assertCountFilter($crawler, 1, '.color-choice', 2, '.color-swatch');
        $this->assertSame('background-color: #B45AA0;', $crawler->filter('.color-swatch')->eq(0)->attr('style'));
        $this->assertSame('2', $crawler->filter('.color-choices input[name="position"]')->attr('value'));
        $this->assertSame(['violet', 'vert', 'bleu vert', 'rose', 'jaune'], $crawler->filter('.color-swatch')->each(static fn (Crawler $node): ?string => $node->attr('title')));
        $this->assertSame("C'est quoi ses couleurs ?", $crawler->filter('.color-game-status h4')->text());
        $this->assertCountFilter($crawler, 0, '.pokemon-image');
        $this->assertSame("L'image du Pokémon apparaît après ta réponse", $crawler->filter('.pokemon-image-pending')->text());
        $this->assertSame(['1', '2', '3'], $crawler->filter('.color-choice .choice-key')->each(static fn (Crawler $node): string => $node->text()));
        $this->assertSame(['1', '2', '3'], $crawler->filter('.color-choice')->each(static fn (Crawler $node): ?string => $node->attr('data-key')));
        $this->assertSame('Astuce : clique une fois pour choisir un lot, une seconde fois pour valider. Au clavier, les touches 1 à 3 choisissent et Entrée valide.', $crawler->filter('.color-choices + p')->text());
        $this->assertSame(['Clique encore pour valider', 'Clique encore pour valider', 'Clique encore pour valider'], $crawler->filter('.color-choice .choice-confirm')->each(static fn (Crawler $node): string => $node->text()));
        $this->assertSame(['false', 'false', 'false'], $crawler->filter('.color-choice')->each(static fn (Crawler $node): ?string => $node->attr('aria-pressed')));

        $this->assertSame('/fr/game/color', $crawler->filter('a.stop-game')->attr('href'));

        $this->assertCountFilter($crawler, 0, '.color-game-credit');
        $this->assertSame('Le visuel du Pokémon ne correspond pas forcément à ses couleurs.', $crawler->filter('.color-game-warning')->text());
        $this->assertSame('Source des couleurs : PokéAPI', $crawler->filter('.color-game-source')->text());
        $this->assertSame('https://pokeapi.co/', $crawler->filter('.color-game-source a')->attr('href'));
    }

    #[Test]
    public function playWithFormsAndShiny(): void
    {
        $client = $this->createTrainerClient();

        $crawler = $client->request('GET', '/en/game/color/'.self::FORMS);

        $this->assertResponseIsSuccessful();

        $this->assertSame('Alolan Raichu', $crawler->filter('.pokemon-name')->text());
        $this->assertSame('Alolan form', $crawler->filter('.pokemon-forms')->text());
        $this->assertSame('Shiny', $crawler->filter('.game-is-shiny')->text());
        $this->assertSame('Colour set 2', $crawler->filter('.color-choice')->eq(1)->attr('aria-label'));
    }

    #[Test]
    public function playCompleted(): void
    {
        $client = $this->createTrainerClient();

        $crawler = $client->request('GET', '/fr/game/color/'.self::COMPLETED);

        $this->assertResponseIsSuccessful();

        $this->assertCountFilter($crawler, 0, '.color-choices');
        $this->assertCountFilter($crawler, 0, '.stop-game');
        $this->assertSame('Partie terminée !', $crawler->filter('.game-over h3')->text());
        $this->assertSame('1 bonne réponse sur 2', $crawler->filter('.game-over .lead')->text());
        $this->assertSame('homeshiny', $crawler->filter('.game-over input[name="dex_slug"]')->attr('value'));
        $this->assertSame('Rejouer', $crawler->filter('.game-over .new-game')->text());
        $this->assertSame('/fr/game/color', $crawler->filter('.game-over a')->attr('href'));
    }

    #[Test]
    public function playAbandoned(): void
    {
        $client = $this->createTrainerClient();

        $crawler = $client->request('GET', '/fr/game/color/'.self::ABANDONED);

        $this->assertResponseIsSuccessful();

        $this->assertCountFilter($crawler, 0, '.color-choices');
        $this->assertCountFilter($crawler, 0, '.pokemon-name');
        $this->assertSame('Partie abandonnée', $crawler->filter('.game-over h3')->text());
    }

    #[Test]
    public function playUnknown(): void
    {
        $client = $this->createTrainerClient();

        $client->request('GET', '/fr/game/color/'.self::UNKNOWN);

        $this->assertResponseStatusCodeSame(404);
    }

    #[Test]
    public function playNotAnUuid(): void
    {
        $client = $this->createTrainerClient();

        $client->request('GET', '/fr/game/color/douze');

        $this->assertResponseStatusCodeSame(404);
    }

    #[Test]
    public function answerWrong(): void
    {
        $client = $this->createTrainerClient();

        $crawler = $client->request('GET', '/fr/game/color/'.self::IN_PROGRESS);
        $form = $crawler->filter('.color-choice')->eq(0)->form();
        $crawler = $client->submit($form);

        $this->assertResponseIsSuccessful();

        $this->assertSame('Raté !', $crawler->filter('.answer-result')->text());
        $this->assertStringContainsString('alert-danger', (string) $crawler->filter('.answer-box')->attr('class'));
        $this->assertSame('2 manches jouées sur 3', $crawler->filter('.color-game-progress + p')->text());
        $this->assertSame('Herbizarre', $crawler->filter('.pokemon-name')->text());
        $this->assertSame(
            'https://icon.pokenini.fr/pokemon/big/regular/ivysaur.png',
            $crawler->filter('.pokemon-image')->attr('src'),
        );
        $this->assertSame('Herbizarre', $crawler->filter('.pokemon-image')->attr('alt'));

        $this->assertCountFilter($crawler, 0, '.color-choices');
        $this->assertCountFilter($crawler, 0, '.stop-game');
        $this->assertStringContainsString('color-game-status', (string) $crawler->filter('.answer-box')->attr('class'));
        $this->assertCountFilter($crawler, 3, '.color-answers .color-answer');
        $this->assertCountFilter($crawler, 0, '.color-answer[data-key]');
        $this->assertSame(['violet', 'vert', 'bleu vert', 'rose', 'jaune'], $crawler->filter('.color-swatch')->each(static fn (Crawler $node): ?string => $node->attr('title')));
        $this->assertCountFilter($crawler, 2, '.color-answer', 0, '.color-swatch');
        $this->assertCountFilter($crawler, 1, '.color-answer', 2, '.color-swatch');
        $this->assertSame('Ton choix', $crawler->filter('.color-answer')->eq(0)->filter('.choice-key')->text());
        $this->assertStringContainsString('chosen-choice', (string) $crawler->filter('.color-answer')->eq(0)->attr('class'));
        $this->assertCountFilter($crawler, 1, '.color-answer', 0, '.bi-x-lg');
        $this->assertSame('Les bonnes couleurs', $crawler->filter('.color-answer')->eq(1)->filter('.choice-key')->text());
        $this->assertStringContainsString('correct-choice', (string) $crawler->filter('.color-answer')->eq(1)->attr('class'));
        $this->assertCountFilter($crawler, 1, '.color-answer', 1, '.bi-check-lg');
        $this->assertSame('3', $crawler->filter('.color-answer')->eq(2)->filter('.choice-key')->text());
        $this->assertCountFilter($crawler, 1, '.chosen-choice');
        $this->assertCountFilter($crawler, 1, '.correct-choice');

        $this->assertSame('Manche suivante', trim($crawler->filter('a.next-round')->text()));
        $this->assertSame('/fr/game/color/'.self::IN_PROGRESS, $crawler->filter('a.next-round')->attr('href'));
    }

    #[Test]
    public function answerCorrect(): void
    {
        $client = $this->createTrainerClient();

        $crawler = $client->request('GET', '/en/game/color/'.self::IN_PROGRESS);
        $form = $crawler->filter('.color-choice')->eq(1)->form();
        $crawler = $client->submit($form);

        $this->assertResponseIsSuccessful();

        $this->assertSame('Well done!', $crawler->filter('.answer-result')->text());
        $this->assertStringContainsString('alert-success', (string) $crawler->filter('.answer-box')->attr('class'));
        $this->assertSame('2 right answers out of 2', $crawler->filter('.game-bar .color-game-score')->text());
        $this->assertSame(['purple', 'green', 'teal', 'pink', 'yellow'], $crawler->filter('.color-swatch')->each(static fn (Crawler $node): ?string => $node->attr('title')));
        $this->assertCountFilter($crawler, 0, '.chosen-choice');
        $this->assertCountFilter($crawler, 1, '.correct-choice');
        $this->assertSame('The right colours', $crawler->filter('.correct-choice .choice-key')->text());
        $this->assertSame('The Pokémon picture may not exactly match its colours.', $crawler->filter('.color-game-warning')->text());
        $this->assertSame('Colours source: PokéAPI', $crawler->filter('.color-game-source')->text());
        $this->assertSame('https://pokeapi.co/', $crawler->filter('.color-game-source a')->attr('href'));
        $this->assertSame(['1', '3'], $crawler->filter('.color-answer:not(.correct-choice) .choice-key')->each(static fn (Crawler $node): string => $node->text()));
    }

    #[Test]
    public function answerLastRound(): void
    {
        $client = $this->createTrainerClient();

        $crawler = $client->request('GET', '/fr/game/color/'.self::LAST_ROUND);
        $form = $crawler->filter('.color-choice')->eq(1)->form();
        $crawler = $client->submit($form);

        $this->assertResponseIsSuccessful();

        $this->assertSame('Bien vu !', $crawler->filter('.answer-result')->text());
        $this->assertSame('3 manches jouées sur 3', $crawler->filter('.color-game-progress + p')->text());
        $this->assertSame('Voir le résultat', trim($crawler->filter('a.next-round')->text()));
    }

    #[Test]
    public function answerRefusedByTheBack(): void
    {
        $client = $this->createTrainerClient();

        $crawler = $client->request('GET', '/fr/game/color/'.self::CONFLICT);
        $form = $crawler->filter('.color-choice')->eq(0)->form();
        $client->submit($form);

        $this->assertResponseRedirects('/fr/game/color/'.self::CONFLICT);
    }

    #[Test]
    public function answerAnotherRound(): void
    {
        $client = $this->createTrainerClient();

        $crawler = $client->request('GET', '/fr/game/color/'.self::IN_PROGRESS);
        $form = $crawler->filter('.color-choice')->eq(0)->form();
        $form['position'] = '1';
        $client->submit($form);

        $this->assertResponseRedirects('/fr/game/color/'.self::IN_PROGRESS);
    }

    #[Test]
    public function answerFinishedGame(): void
    {
        $client = $this->createTrainerClient();

        $crawler = $client->request('GET', '/fr/game/color/'.self::IN_PROGRESS);
        $token = $crawler->filter('.color-choices input[name="_token"]')->attr('value');

        $client->request('POST', '/fr/game/color/'.self::COMPLETED, ['_token' => $token, 'position' => '1', 'choice' => '0']);

        $this->assertResponseRedirects('/fr/game/color/'.self::COMPLETED);
    }

    #[Test]
    public function answerUnknownGame(): void
    {
        $client = $this->createTrainerClient();

        $crawler = $client->request('GET', '/fr/game/color/'.self::IN_PROGRESS);
        $token = $crawler->filter('.color-choices input[name="_token"]')->attr('value');

        $client->request('POST', '/fr/game/color/'.self::UNKNOWN, ['_token' => $token, 'position' => '1', 'choice' => '0']);

        $this->assertResponseStatusCodeSame(404);
    }

    #[Test]
    public function answerInvalidToken(): void
    {
        $client = $this->createTrainerClient();

        $client->request('POST', '/fr/game/color/'.self::IN_PROGRESS, ['_token' => 'nope', 'position' => '2', 'choice' => '0']);

        $this->assertResponseStatusCodeSame(403);
    }

    #[Test]
    public function create(): void
    {
        $client = $this->createTrainerClient();

        $crawler = $client->request('GET', '/fr/game/color');
        $form = $crawler->filter('.dex-item')->eq(0)->filter('form')->form();
        $client->submit($form);

        $this->assertResponseRedirects('/fr/game/color/33333333-3333-4333-8333-333333333333');
    }

    #[Test]
    public function createFailed(): void
    {
        $client = $this->createTrainerClient();

        $crawler = $client->request('GET', '/fr/game/color');
        $form = $crawler->filter('.dex-item')->eq(0)->filter('form')->form();
        $form['dex_slug'] = 'homepogo';
        $client->submit($form);

        $this->assertResponseRedirects('/fr/game/color');

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

        $client->request('POST', '/fr/game/color', ['_token' => 'nope', 'dex_slug' => 'demo']);

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
