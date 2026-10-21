<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Game;

use App\Controller\ShinyGameController;
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
#[CoversClass(ShinyGameController::class)]
#[Group('api-mocked-testing')]
final class ShinyGamePlayTest extends WebTestCase
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

        $crawler = $client->request('GET', '/fr/game/shiny/'.self::IN_PROGRESS);

        $this->assertResponseIsSuccessful();

        $this->assertSame('Pokénini Shiny or not', $crawler->filter('title')->text());
        $this->assertSame('Démo', $crawler->filter('h2 small')->text());
        $this->assertCountFilter($crawler, 1, '.navbar-nav .game-link a.active');

        $this->assertSame('1 manche jouée sur 3', $crawler->filter('.color-game-progress + p')->text());
        $this->assertSame('1 bonne réponse sur 1', $crawler->filter('.game-bar .color-game-score')->text());

        $this->assertSame('Manche 2 sur 3', $crawler->filter('.game-bar .round-position')->text());
        $this->assertSame('Shiny or not', $crawler->filter('.game-bar h1')->text());
        $this->assertCountFilter($crawler, 1, '.game-bar .color-game-progress + p.visually-hidden');
        $this->assertCountFilter($crawler, 1, '.game-bar a.stop-game');
        $this->assertCountFilter($crawler, 0, '.game-is-shiny');
        $this->assertSame('Herbizarre', $crawler->filter('.pokemon-name')->text());
        $this->assertCountFilter($crawler, 0, '.pokemon-forms');
        $this->assertSame('Normal ou chromatique ?', $crawler->filter('.color-game-status h4')->text());

        // The image URL must not tell which version is shown
        $this->assertSame(
            '/fr/game/shiny/'.self::IN_PROGRESS.'/rounds/2/image',
            $crawler->filter('.round-image')->attr('src'),
        );
        $this->assertSame('Le Pokémon à deviner', $crawler->filter('.round-image')->attr('alt'));
        $this->assertStringNotContainsString('pokemon/big', (string) $client->getResponse()->getContent());

        $this->assertCountFilter($crawler, 2, '.shiny-choices button.shiny-choice');
        $this->assertSame(['0', '1'], $crawler->filter('.shiny-choice')->each(static fn (Crawler $node): ?string => $node->attr('value')));
        $this->assertSame(['1', '2'], $crawler->filter('.shiny-choice')->each(static fn (Crawler $node): ?string => $node->attr('data-key')));
        $this->assertSame(['1', '2'], $crawler->filter('.shiny-choice .choice-key')->each(static fn (Crawler $node): string => $node->text()));
        $this->assertSame(['Normal', 'Chromatique'], $crawler->filter('.shiny-choice .version-name')->each(static fn (Crawler $node): string => $node->text()));
        $this->assertSame(['Clique encore pour valider', 'Clique encore pour valider'], $crawler->filter('.shiny-choice .choice-confirm')->each(static fn (Crawler $node): string => $node->text()));
        $this->assertSame(['false', 'false'], $crawler->filter('.shiny-choice')->each(static fn (Crawler $node): ?string => $node->attr('aria-pressed')));
        $this->assertCountFilter($crawler, 2, '.shiny-choice.game-choice');
        $this->assertSame('2', $crawler->filter('.shiny-choices input[name="position"]')->attr('value'));
        $this->assertSame('Astuce : clique une fois pour choisir, une seconde fois pour valider. Au clavier, la touche 1 choisit normal, la touche 2 chromatique et Entrée valide.', $crawler->filter('.shiny-choices + p')->text());

        $this->assertCountFilter($crawler, 0, '.shiny-answers');
        $this->assertSame('/fr/game/shiny', $crawler->filter('a.stop-game')->attr('href'));
    }

    #[Test]
    public function playWithForms(): void
    {
        $client = $this->createTrainerClient();

        $crawler = $client->request('GET', '/en/game/shiny/'.self::FORMS);

        $this->assertResponseIsSuccessful();

        $this->assertSame('Alolan Raichu', $crawler->filter('.pokemon-name')->text());
        $this->assertSame('Alolan form', $crawler->filter('.pokemon-forms')->text());
        $this->assertSame('Regular or shiny?', $crawler->filter('.color-game-status h4')->text());
        $this->assertSame(['Regular', 'Shiny'], $crawler->filter('.shiny-choice .version-name')->each(static fn (Crawler $node): string => $node->text()));
        $this->assertSame(['Click again to confirm', 'Click again to confirm'], $crawler->filter('.shiny-choice .choice-confirm')->each(static fn (Crawler $node): string => $node->text()));
        $this->assertSame('Tip: click once to pick, a second time to confirm. On the keyboard, key 1 picks regular, key 2 picks shiny and Enter confirms.', $crawler->filter('.shiny-choices + p')->text());
    }

    #[Test]
    public function playCompleted(): void
    {
        $client = $this->createTrainerClient();

        $crawler = $client->request('GET', '/fr/game/shiny/'.self::COMPLETED);

        $this->assertResponseIsSuccessful();

        $this->assertCountFilter($crawler, 0, '.shiny-choices');
        $this->assertCountFilter($crawler, 0, '.stop-game');
        $this->assertSame('Partie terminée !', $crawler->filter('.game-over h3')->text());
        $this->assertSame('1 bonne réponse sur 2', $crawler->filter('.game-over .lead')->text());
        $this->assertSame('redgreenblueyellow', $crawler->filter('.game-over input[name="dex_slug"]')->attr('value'));
        $this->assertSame('/fr/game/shiny', $crawler->filter('.game-over form')->attr('action'));
        $this->assertSame('/fr/game/shiny', $crawler->filter('.game-over a')->attr('href'));
    }

    #[Test]
    public function playAbandoned(): void
    {
        $client = $this->createTrainerClient();

        $crawler = $client->request('GET', '/fr/game/shiny/'.self::ABANDONED);

        $this->assertResponseIsSuccessful();

        $this->assertCountFilter($crawler, 0, '.shiny-choices');
        $this->assertCountFilter($crawler, 0, '.pokemon-name');
        $this->assertSame('Partie abandonnée', $crawler->filter('.game-over h3')->text());
    }

    #[Test]
    public function playUnknown(): void
    {
        $client = $this->createTrainerClient();

        $client->request('GET', '/fr/game/shiny/'.self::UNKNOWN);

        $this->assertResponseStatusCodeSame(404);
    }

    #[Test]
    public function playNotAnUuid(): void
    {
        $client = $this->createTrainerClient();

        $client->request('GET', '/fr/game/shiny/douze');

        $this->assertResponseStatusCodeSame(404);
    }

    #[Test]
    public function answerWrong(): void
    {
        $client = $this->createTrainerClient();

        $crawler = $client->request('GET', '/fr/game/shiny/'.self::IN_PROGRESS);
        $form = $crawler->filter('.shiny-choice')->eq(1)->form();
        $crawler = $client->submit($form);

        $this->assertResponseIsSuccessful();

        $this->assertSame('Raté !', $crawler->filter('.answer-result')->text());
        $this->assertStringContainsString('alert-danger', (string) $crawler->filter('.answer-box')->attr('class'));
        $this->assertSame('2 manches jouées sur 3', $crawler->filter('.color-game-progress + p')->text());
        $this->assertSame('Herbizarre', $crawler->filter('.pokemon-name')->text());

        $this->assertCountFilter($crawler, 0, '.shiny-choices');
        $this->assertCountFilter($crawler, 0, '.round-image');
        $this->assertCountFilter($crawler, 0, '.stop-game');
        $this->assertSame('Manche 2 sur 3', $crawler->filter('.game-bar .round-position')->text());
        $this->assertSame('https://icon.pokenini.fr/pokemon/big/regular/ivysaur.png', $crawler->filter('.shiny-game-image-box .shown-image')->attr('src'));
        $this->assertSame('Herbizarre', $crawler->filter('.shiny-game-image-box .shown-image')->attr('alt'));
        $this->assertCountFilter($crawler, 2, '.shiny-answers .shiny-version');
        $this->assertSame(
            [
                'https://icon.pokenini.fr/pokemon/big/regular/ivysaur.png',
                'https://icon.pokenini.fr/pokemon/big/shiny/ivysaur.png',
            ],
            $crawler->filter('.shiny-version img')->each(static fn (Crawler $node): ?string => $node->attr('src')),
        );
        $this->assertSame(
            ['Herbizarre Normal', 'Herbizarre Chromatique'],
            $crawler->filter('.shiny-version img')->each(static fn (Crawler $node): ?string => $node->attr('alt')),
        );
        $this->assertSame(['Normal', 'Chromatique'], $crawler->filter('.version-name')->each(static fn (Crawler $node): string => $node->text()));

        $regular = $crawler->filter('.shiny-version')->eq(0);
        $this->assertStringContainsString('shown-version', (string) $regular->attr('class'));
        $this->assertSame("L'image montrée", $regular->filter('.version-label')->text());

        $shiny = $crawler->filter('.shiny-version')->eq(1);
        $this->assertStringContainsString('chosen-version', (string) $shiny->attr('class'));
        $this->assertSame('Ton choix', $shiny->filter('.version-label')->text());

        $this->assertCountFilter($crawler, 1, '.shown-version');
        $this->assertCountFilter($crawler, 1, '.chosen-version');

        $this->assertSame('Manche suivante', trim($crawler->filter('a.next-round')->text()));
        $this->assertSame('/fr/game/shiny/'.self::IN_PROGRESS, $crawler->filter('a.next-round')->attr('href'));
    }

    #[Test]
    public function answerCorrect(): void
    {
        $client = $this->createTrainerClient();

        $crawler = $client->request('GET', '/en/game/shiny/'.self::IN_PROGRESS);
        $form = $crawler->filter('.shiny-choice')->eq(0)->form();
        $crawler = $client->submit($form);

        $this->assertResponseIsSuccessful();

        $this->assertSame('Well done!', $crawler->filter('.answer-result')->text());
        $this->assertStringContainsString('alert-success', (string) $crawler->filter('.answer-box')->attr('class'));
        $this->assertSame('2 right answers out of 2', $crawler->filter('.game-bar .color-game-score')->text());
        $this->assertCountFilter($crawler, 0, '.chosen-version');
        $this->assertCountFilter($crawler, 1, '.version-label');
        $this->assertSame('The image shown', $crawler->filter('.shown-version .version-label')->text());
        $this->assertSame('Regular', $crawler->filter('.shown-version .version-name')->text());
    }

    #[Test]
    public function answerLastRound(): void
    {
        $client = $this->createTrainerClient();

        $crawler = $client->request('GET', '/fr/game/shiny/'.self::LAST_ROUND);
        $form = $crawler->filter('.shiny-choice')->eq(1)->form();
        $crawler = $client->submit($form);

        $this->assertResponseIsSuccessful();

        $this->assertSame('Bien vu !', $crawler->filter('.answer-result')->text());
        $this->assertSame('3 manches jouées sur 3', $crawler->filter('.color-game-progress + p')->text());
        $this->assertSame('Chromatique', $crawler->filter('.shown-version .version-name')->text());
        $this->assertSame('Voir le résultat', trim($crawler->filter('a.next-round')->text()));
    }

    #[Test]
    public function answerRefusedByTheBack(): void
    {
        $client = $this->createTrainerClient();

        $crawler = $client->request('GET', '/fr/game/shiny/'.self::CONFLICT);
        $form = $crawler->filter('.shiny-choice')->eq(0)->form();
        $client->submit($form);

        $this->assertResponseRedirects('/fr/game/shiny/'.self::CONFLICT);
    }

    #[Test]
    public function answerAnotherRound(): void
    {
        $client = $this->createTrainerClient();

        $crawler = $client->request('GET', '/fr/game/shiny/'.self::IN_PROGRESS);
        $form = $crawler->filter('.shiny-choice')->eq(0)->form();
        $form['position'] = '1';
        $client->submit($form);

        $this->assertResponseRedirects('/fr/game/shiny/'.self::IN_PROGRESS);
    }

    #[Test]
    public function answerFinishedGame(): void
    {
        $client = $this->createTrainerClient();

        $crawler = $client->request('GET', '/fr/game/shiny/'.self::IN_PROGRESS);
        $token = $crawler->filter('.shiny-choices input[name="_token"]')->attr('value');

        $client->request('POST', '/fr/game/shiny/'.self::COMPLETED, ['_token' => $token, 'position' => '1', 'is_shiny' => '0']);

        $this->assertResponseRedirects('/fr/game/shiny/'.self::COMPLETED);
    }

    #[Test]
    public function answerUnknownGame(): void
    {
        $client = $this->createTrainerClient();

        $crawler = $client->request('GET', '/fr/game/shiny/'.self::IN_PROGRESS);
        $token = $crawler->filter('.shiny-choices input[name="_token"]')->attr('value');

        $client->request('POST', '/fr/game/shiny/'.self::UNKNOWN, ['_token' => $token, 'position' => '1', 'is_shiny' => '0']);

        $this->assertResponseStatusCodeSame(404);
    }

    #[Test]
    public function answerInvalidToken(): void
    {
        $client = $this->createTrainerClient();

        $client->request('POST', '/fr/game/shiny/'.self::IN_PROGRESS, ['_token' => 'nope', 'position' => '2', 'is_shiny' => '0']);

        $this->assertResponseStatusCodeSame(403);
    }

    #[Test]
    public function create(): void
    {
        $client = $this->createTrainerClient();

        $crawler = $client->request('GET', '/fr/game/shiny');
        $form = $crawler->filter('.dex-item')->eq(0)->filter('form')->form();
        $client->submit($form);

        $this->assertResponseRedirects('/fr/game/shiny/33333333-3333-4333-8333-333333333333');
    }

    #[Test]
    public function createFailed(): void
    {
        $client = $this->createTrainerClient();

        $crawler = $client->request('GET', '/fr/game/shiny');
        $form = $crawler->filter('.dex-item')->eq(0)->filter('form')->form();
        $form['dex_slug'] = 'homepogo';
        $client->submit($form);

        $this->assertResponseRedirects('/fr/game/shiny');

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

        $client->request('POST', '/fr/game/shiny', ['_token' => 'nope', 'dex_slug' => 'demo']);

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
