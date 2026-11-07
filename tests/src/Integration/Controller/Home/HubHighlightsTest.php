<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Home;

use App\Controller\HubHighlightsController;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(HubHighlightsController::class)]
final class HubHighlightsTest extends WebTestCase
{
    use TestNavTrait;

    #[Test]
    public function albums(): void
    {
        $client = $this->trainerClient('hub-highlights');

        $crawler = $client->request('GET', '/fr/hub/highlights/album');

        $this->assertResponseIsSuccessful();

        // Four albums are on the hub, three are shown, in the order of the list
        $this->assertCountFilter($crawler, 3, '.hub-highlight-album');
        $this->assertSame('/fr/album/goldsilvercrystal', $crawler->filter('.hub-highlight-album')->eq(0)->attr('href'));
        $this->assertSame('/fr/album/rubysapphireemerald', $crawler->filter('.hub-highlight-album')->eq(1)->attr('href'));
        $this->assertSame('/fr/album/fireredleafgreen', $crawler->filter('.hub-highlight-album')->eq(2)->attr('href'));
        $this->assertSame('Or, Argent, Cristal', $crawler->filter('.hub-highlight-album .fw-semibold')->eq(0)->text());

        // The progress is shown when the album has a report
        $this->assertCountFilter($crawler, 1, '.hub-highlight-album .hub-highlight-progress');
        $this->assertSame('75', $crawler->filter('.hub-highlight-progress')->attr('aria-valuenow'));
        $this->assertSame('75 % attrapés', $crawler->filter('.hub-highlight-album .small')->text());
        $this->assertCountFilter($crawler, 0, '.hub-highlights-empty');
    }

    #[Test]
    public function noAlbumOnTheHub(): void
    {
        $client = $this->trainerClient('789465465489');

        $crawler = $client->request('GET', '/fr/hub/highlights/album');

        $this->assertResponseIsSuccessful();
        $this->assertCountFilter($crawler, 0, '.hub-highlight-album');
        $this->assertCountFilter($crawler, 1, '.hub-highlights-empty');
        $this->assertSame('/fr/trainer', $crawler->filter('.hub-highlights-empty a')->attr('href'));
    }

    #[Test]
    public function elections(): void
    {
        $client = $this->trainerClient('hub-highlights');

        $crawler = $client->request('GET', '/fr/hub/highlights/election');

        $this->assertResponseIsSuccessful();

        // The elections in progress first, then the ones not started, then the finished ones; three at most
        $elections = $crawler->filter('.hub-highlight-election');
        $this->assertCount(3, $elections);
        $this->assertSame('in_progress', $elections->eq(0)->attr('data-state'));
        $this->assertSame('in_progress', $elections->eq(1)->attr('data-state'));
        $this->assertSame('not_started', $elections->eq(2)->attr('data-state'));

        $this->assertSame('/fr/election/homepogo', $elections->eq(0)->attr('href'));
        $this->assertSame('Ton favori : Bulbizarre', $elections->eq(0)->filter('.text-body-secondary')->text());
        $this->assertSame('https://icon.pokenini.fr/pokemon/small/regular/bulbasaur.png', $elections->eq(0)->filter('img')->attr('src'));
        $this->assertSame('Continuer · manche 4 sur 12', $elections->eq(0)->filter('.hub-highlight-status')->text());

        $this->assertSame('Pas encore de favori', $elections->eq(1)->filter('.text-body-secondary')->text());
        $this->assertCountFilter($elections->eq(1), 0, 'img');
        $this->assertSame('Commencer', $elections->eq(2)->filter('.hub-highlight-status')->text());
    }

    #[Test]
    public function electionsInEnglish(): void
    {
        $client = $this->trainerClient('hub-highlights');

        $crawler = $client->request('GET', '/en/hub/highlights/election');

        $this->assertResponseIsSuccessful();
        $this->assertSame('Your favorite: Bulbasaur', $crawler->filter('.hub-highlight-election .text-body-secondary')->eq(0)->text());
        $this->assertSame('Continue · round 4 of 12', $crawler->filter('.hub-highlight-status')->eq(0)->text());
    }

    #[Test]
    public function games(): void
    {
        $client = $this->trainerClient('hub-highlights');

        $crawler = $client->request('GET', '/fr/hub/highlights/game');

        $this->assertResponseIsSuccessful();

        $games = $crawler->filter('.hub-highlight-game');
        $this->assertCount(3, $games);

        // The game in progress played last, even when a finished game was played after it
        $color = $games->eq(0);
        $this->assertSame('color_game', $color->attr('data-game'));
        $this->assertSame('Partie en cours · Rouge / Vert / Bleu / Jaune · 5 sur 20', $color->filter('.text-body-secondary')->text());
        $this->assertSame('/fr/game/color/cccccccc-1111-4111-8111-111111111111', $color->filter('a.hub-game-resume')->attr('href'));

        // No game in progress: the last game, to play again on the same dex with as many rounds
        $shiny = $games->eq(1);
        $this->assertSame('shiny_game', $shiny->attr('data-game'));
        $this->assertSame('Dernière partie · Rouge / Vert / Bleu / Jaune · 7/10', $shiny->filter('.text-body-secondary')->text());
        $this->assertSame('/fr/game/shiny', $shiny->filter('form')->attr('action'));
        $this->assertSame('redgreenblueyellow', $shiny->filter('input[name="dex_slug"]')->attr('value'));
        $this->assertSame('10', $shiny->filter('input[name="round_count"]')->attr('value'));
        $this->assertNotEmpty($shiny->filter('input[name="_token"]')->attr('value'));
        $this->assertSame('Rejouer', $shiny->filter('button.hub-game-replay')->text());

        // Never played
        $whichShiny = $games->eq(2);
        $this->assertSame('which_shiny_game', $whichShiny->attr('data-game'));
        $this->assertSame('Pas encore joué', $whichShiny->filter('.text-body-secondary')->text());
        $this->assertSame('/fr/game/which-shiny', $whichShiny->filter('a.hub-game-play')->attr('href'));
    }

    #[Test]
    public function nothingWhenTheBackFails(): void
    {
        $client = $this->trainerClient('hub-broken');

        foreach (['album', 'election', 'game'] as $part) {
            $client->request('GET', '/fr/hub/highlights/'.$part);

            $this->assertResponseStatusCodeSame(204);
        }
    }

    #[Test]
    public function onlyForTrainers(): void
    {
        $client = self::createClient();

        $client->request('GET', '/fr/hub/highlights/album');

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
