<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Game;

use App\Controller\ShinyGameController;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * The answer page shown after answering a round, which can be reloaded.
 *
 * @internal
 */
#[CoversClass(ShinyGameController::class)]
#[Group('api-mocked-testing')]
final class ShinyGameAnsweredTest extends WebTestCase
{
    private const string IN_PROGRESS = '11111111-1111-4111-8111-111111111111';
    private const string LAST_ROUND = '66666666-6666-4666-8666-666666666666';

    #[Test]
    public function answerReloaded(): void
    {
        $client = $this->createTrainerClient();

        $crawler = $client->request('GET', '/fr/game/shiny/'.self::IN_PROGRESS);
        $form = $crawler->filter('.shiny-choice')->eq(1)->form();
        $client->submit($form);
        $client->followRedirect();

        // Reloading the answer page shows the answer again without sending it again
        $crawler = $client->request('GET', '/fr/game/shiny/'.self::IN_PROGRESS.'/rounds/2/answer');

        $this->assertResponseIsSuccessful();

        $this->assertSame('Raté !', $crawler->filter('.answer-result')->text());
        $this->assertSame('2 manches jouées sur 3', $crawler->filter('.color-game-progress + p')->text());
        $this->assertSame('/fr/game/shiny/'.self::IN_PROGRESS, $crawler->filter('a.next-round')->attr('href'));
    }

    #[Test]
    public function answeredNotAnswered(): void
    {
        $client = $this->createTrainerClient();

        $client->request('GET', '/fr/game/shiny/'.self::IN_PROGRESS.'/rounds/2/answer');

        $this->assertResponseRedirects('/fr/game/shiny/'.self::IN_PROGRESS);
    }

    #[Test]
    public function answeredAnotherRound(): void
    {
        $client = $this->createTrainerClient();

        $crawler = $client->request('GET', '/fr/game/shiny/'.self::IN_PROGRESS);
        $form = $crawler->filter('.shiny-choice')->eq(1)->form();
        $client->submit($form);

        $client->request('GET', '/fr/game/shiny/'.self::IN_PROGRESS.'/rounds/1/answer');

        $this->assertResponseRedirects('/fr/game/shiny/'.self::IN_PROGRESS);
    }

    #[Test]
    public function answeredAnotherGame(): void
    {
        $client = $this->createTrainerClient();

        $crawler = $client->request('GET', '/fr/game/shiny/'.self::IN_PROGRESS);
        $form = $crawler->filter('.shiny-choice')->eq(1)->form();
        $client->submit($form);

        $client->request('GET', '/fr/game/shiny/'.self::LAST_ROUND.'/rounds/2/answer');

        $this->assertResponseRedirects('/fr/game/shiny/'.self::LAST_ROUND);
    }

    #[Test]
    public function answeredNotAPosition(): void
    {
        $client = $this->createTrainerClient();

        $client->request('GET', '/fr/game/shiny/'.self::IN_PROGRESS.'/rounds/douze/answer');

        $this->assertResponseStatusCodeSame(404);
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
