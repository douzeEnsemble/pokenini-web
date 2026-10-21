<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Game;

use App\Controller\WhichShinyGameController;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @internal
 */
#[CoversClass(WhichShinyGameController::class)]
#[Group('api-mocked-testing')]
final class WhichShinyGameImageTest extends WebTestCase
{
    private const string IN_PROGRESS = '11111111-1111-4111-8111-111111111111';
    private const string COMPLETED = '22222222-2222-4222-8222-222222222222';
    private const string ABANDONED = '44444444-4444-4444-8444-444444444444';
    private const string LAST_ROUND = '66666666-6666-4666-8666-666666666666';
    private const string FORMS = '77777777-7777-4777-8777-777777777777';
    private const string UNKNOWN = '99999999-9999-4999-8999-999999999999';

    private const string IMAGES_PATH = __DIR__.'/../../../../resources/moco/Back/responses/';

    #[Test]
    public function imageShinyFirst(): void
    {
        $client = $this->createTrainerClient();

        $client->request('GET', '/fr/game/which-shiny/'.self::IN_PROGRESS.'/rounds/2/images/1');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'image/png');
        $this->assertStringContainsString('no-store', (string) $client->getResponse()->headers->get('Cache-Control'));
        $this->assertSame(
            new Filesystem()->readFile(self::IMAGES_PATH.'which_shiny_game/images/shiny-ivysaur.png'),
            $client->getResponse()->getContent(),
        );

        $client->request('GET', '/fr/game/which-shiny/'.self::IN_PROGRESS.'/rounds/2/images/2');

        $this->assertResponseIsSuccessful();
        $this->assertSame(
            new Filesystem()->readFile(self::IMAGES_PATH.'shiny_game/images/regular-ivysaur.png'),
            $client->getResponse()->getContent(),
        );
    }

    #[Test]
    public function imageShinySecond(): void
    {
        $client = $this->createTrainerClient();

        $client->request('GET', '/fr/game/which-shiny/'.self::FORMS.'/rounds/2/images/1');

        $this->assertResponseIsSuccessful();
        $this->assertSame(
            new Filesystem()->readFile(self::IMAGES_PATH.'which_shiny_game/images/regular-raichu-alola.png'),
            $client->getResponse()->getContent(),
        );

        $client->request('GET', '/fr/game/which-shiny/'.self::FORMS.'/rounds/2/images/2');

        $this->assertResponseIsSuccessful();
        $this->assertSame(
            new Filesystem()->readFile(self::IMAGES_PATH.'shiny_game/images/shiny-raichu-alola.png'),
            $client->getResponse()->getContent(),
        );
    }

    #[Test]
    public function imageOfAnUnknownSlot(): void
    {
        $client = $this->createTrainerClient();

        $client->request('GET', '/fr/game/which-shiny/'.self::IN_PROGRESS.'/rounds/2/images/3');

        $this->assertResponseStatusCodeSame(404);
    }

    #[Test]
    public function imageOfAnotherRound(): void
    {
        $client = $this->createTrainerClient();

        $client->request('GET', '/fr/game/which-shiny/'.self::IN_PROGRESS.'/rounds/1/images/1');

        $this->assertResponseStatusCodeSame(404);
    }

    #[Test]
    public function imageOfAFinishedGame(): void
    {
        $client = $this->createTrainerClient();

        $client->request('GET', '/fr/game/which-shiny/'.self::COMPLETED.'/rounds/1/images/1');

        $this->assertResponseStatusCodeSame(404);
    }

    #[Test]
    public function imageOfAnAbandonedGame(): void
    {
        $client = $this->createTrainerClient();

        $client->request('GET', '/fr/game/which-shiny/'.self::ABANDONED.'/rounds/1/images/1');

        $this->assertResponseStatusCodeSame(404);
    }

    #[Test]
    public function imageOfAnUnknownGame(): void
    {
        $client = $this->createTrainerClient();

        $client->request('GET', '/fr/game/which-shiny/'.self::UNKNOWN.'/rounds/1/images/1');

        $this->assertResponseStatusCodeSame(404);
    }

    #[Test]
    public function imageNotFound(): void
    {
        $client = $this->createTrainerClient();

        $client->request('GET', '/fr/game/which-shiny/'.self::LAST_ROUND.'/rounds/3/images/1');

        $this->assertResponseStatusCodeSame(404);
    }

    #[Test]
    public function imageNotTrainer(): void
    {
        $client = self::createClient();
        $client->loginUser(GetUserToken::getFakeUserToken(), 'web');

        $client->request('GET', '/fr/game/which-shiny/'.self::IN_PROGRESS.'/rounds/2/images/1');

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
