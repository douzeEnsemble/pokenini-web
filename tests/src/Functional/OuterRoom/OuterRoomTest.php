<?php

declare(strict_types=1);

namespace App\Tests\Functional\OuterRoom;

use App\Controller\OuterRoomController;
use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @internal
 */
#[CoversClass(OuterRoomController::class)]
class OuterRoomTest extends WebTestCase
{
    use TestNavTrait;

    public function testOuterRoomPageNonConnected(): void
    {
        $client = static::createClient();

        $client->request('GET', '/fr/outerroom');

        $this->assertResponseStatusCodeSame(307);
    }

    public function testOuterRoomPageConnectedAsTrainer(): void
    {
        $client = static::createClient();

        $user = new User('789465465489', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $client->request('GET', '/fr/outerroom');

        $this->assertResponseStatusCodeSame(302);
    }

    public function testOuterRoomPageConnectedAsAdmin(): void
    {
        $client = static::createClient();

        $user = new User('8764532', 'TestProvider');
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $client->request('GET', '/fr/outerroom');

        $this->assertResponseStatusCodeSame(302);
    }

    public function testOuterRoomPage(): void
    {
        $client = static::createClient();

        $client->loginUser(new User('121212', 'TestProvider'), 'web');

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
