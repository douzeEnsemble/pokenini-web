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
use Symfony\Component\Filesystem\Filesystem;

/**
 * @internal
 */
#[CoversClass(ShinyGameController::class)]
#[Group('api-mocked-testing')]
final class ShinyGameImageTest extends WebTestCase
{
    private const string IN_PROGRESS = '11111111-1111-4111-8111-111111111111';
    private const string COMPLETED = '22222222-2222-4222-8222-222222222222';
    private const string ABANDONED = '44444444-4444-4444-8444-444444444444';
    private const string LAST_ROUND = '66666666-6666-4666-8666-666666666666';
    private const string FORMS = '77777777-7777-4777-8777-777777777777';
    private const string UNKNOWN = '99999999-9999-4999-8999-999999999999';

    private const string IMAGES_PATH = __DIR__.'/../../../../resources/moco/Back/responses/shiny_game/images/';

    #[Test]
    public function image(): void
    {
        $client = $this->createTrainerClient();

        $client->request('GET', '/fr/game/shiny/'.self::IN_PROGRESS.'/rounds/2/image');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'image/png');
        $this->assertStringContainsString('no-store', (string) $client->getResponse()->headers->get('Cache-Control'));
        $this->assertSame(
            new Filesystem()->readFile(self::IMAGES_PATH.'regular-ivysaur.png'),
            $client->getResponse()->getContent(),
        );
    }

    #[Test]
    public function imageShiny(): void
    {
        $client = $this->createTrainerClient();

        $client->request('GET', '/fr/game/shiny/'.self::FORMS.'/rounds/2/image');

        $this->assertResponseIsSuccessful();
        $this->assertSame(
            new Filesystem()->readFile(self::IMAGES_PATH.'shiny-raichu-alola.png'),
            $client->getResponse()->getContent(),
        );
    }

    #[Test]
    public function imageOfAnotherRound(): void
    {
        $client = $this->createTrainerClient();

        $client->request('GET', '/fr/game/shiny/'.self::IN_PROGRESS.'/rounds/1/image');

        $this->assertResponseStatusCodeSame(404);
    }

    #[Test]
    public function imageOfAFinishedGame(): void
    {
        $client = $this->createTrainerClient();

        $client->request('GET', '/fr/game/shiny/'.self::COMPLETED.'/rounds/1/image');

        $this->assertResponseStatusCodeSame(404);
    }

    #[Test]
    public function imageOfAnAbandonedGame(): void
    {
        $client = $this->createTrainerClient();

        $client->request('GET', '/fr/game/shiny/'.self::ABANDONED.'/rounds/1/image');

        $this->assertResponseStatusCodeSame(404);
    }

    #[Test]
    public function imageOfAnUnknownGame(): void
    {
        $client = $this->createTrainerClient();

        $client->request('GET', '/fr/game/shiny/'.self::UNKNOWN.'/rounds/1/image');

        $this->assertResponseStatusCodeSame(404);
    }

    #[Test]
    public function imageNotFound(): void
    {
        $client = $this->createTrainerClient();

        $client->request('GET', '/fr/game/shiny/'.self::LAST_ROUND.'/rounds/3/image');

        $this->assertResponseStatusCodeSame(404);
    }

    #[Test]
    public function imageNotTrainer(): void
    {
        $client = self::createClient();
        $client->loginUser(GetUserToken::getFakeUserToken(), 'web');

        $client->request('GET', '/fr/game/shiny/'.self::IN_PROGRESS.'/rounds/2/image');

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
