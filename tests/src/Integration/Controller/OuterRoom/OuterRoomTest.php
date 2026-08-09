<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\OuterRoom;

use App\Controller\OuterRoomController;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @internal
 */
#[CoversClass(OuterRoomController::class)]
final class OuterRoomTest extends WebTestCase
{
    use TestNavTrait;

    #[Test]
    public function outerRoomPageNonConnected(): void
    {
        $client = self::createClient();

        $client->request('GET', '/fr/outerroom');

        $this->assertResponseStatusCodeSame(307);
    }

    #[Test]
    public function outerRoomPageConnectedAsTrainer(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $client->request('GET', '/fr/outerroom');

        $this->assertResponseStatusCodeSame(302);
    }

    #[Test]
    public function outerRoomPageConnectedAsAdminOnly(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $client->request('GET', '/fr/outerroom');

        $this->assertResponseStatusCodeSame(200);
    }

    #[Test]
    public function outerRoomPageConnectedAsAdmin(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $user->addTrainerRole();
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $client->request('GET', '/fr/outerroom');

        $this->assertResponseStatusCodeSame(302);
    }

    #[Test]
    public function outerRoomPage(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('121212', 'TestProvider');

        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/outerroom');

        $this->assertResponseStatusCodeSame(200);

        $this->assertOuterRoom($crawler);
    }

    private function assertOuterRoom(Crawler $crawler): void
    {
        $this->assertCountFilter($crawler, 1, 'h1');
        $this->assertCountFilter($crawler, 2, '#main-container p');
        $this->assertStringContainsString('121212', $crawler->filter('#main-container  p')->first()->text());
        $this->assertCountFilter($crawler, 1, 'a.btn');
        $this->assertStringContainsString('mailto:', $crawler->filter('a.btn')->attr('href') ?? '');
        $this->assertStringContainsString('121212', $crawler->filter('a.btn')->attr('href') ?? '');

        $this->assertStringContainsString(
            '/connect/logout',
            $crawler->filter('#main-container a')->last()->attr('href') ?? ''
        );
    }
}
