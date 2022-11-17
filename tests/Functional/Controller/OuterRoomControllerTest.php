<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Security\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class OuterRoomControllerTest extends WebTestCase
{
    public function testOuterRoomPageNonConnected(): void
    {
        $client = static::createClient();

        $client->request('GET', '/fr/outerroom/');

        $this->assertResponseStatusCodeSame(307);
    }

    public function testOuterRoomPageConnectedAsTrainer(): void
    {
        $client = static::createClient();

        $user = new User('789465465489');
        $user->addTrainerRole();
        $client->loginUser($user);

        $client->request('GET', '/fr/outerroom/');

        $this->assertResponseStatusCodeSame(302);
    }

    public function testOuterRoomPageConnectedAsAdmin(): void
    {
        $client = static::createClient();

        $user = new User('8764532');
        $user->addAdminRole();
        $client->loginUser($user);

        $client->request('GET', '/fr/outerroom/');

        $this->assertResponseStatusCodeSame(302);
    }

    public function testOuterRoomPage(): void
    {
        $client = static::createClient();

        $client->loginUser(new User('121212'));

        $crawler = $client->request('GET', '/fr/outerroom/');

        $this->assertResponseStatusCodeSame(200);

        $this->assertOuterRoom($crawler);
    }

    private function assertOuterRoom(Crawler $crawler): void
    {
        $this->assertCount(1, $crawler->filter('h1'));
        $this->assertCount(2, $crawler->filter('#main-container p'));
        $this->assertStringContainsString('121212', $crawler->filter('#main-container  p')->first()->text());
        $this->assertCount(1, $crawler->filter('a.btn'));
        $this->assertStringContainsString('mailto:', $crawler->filter('a.btn')->attr('href') ?? '');
        $this->assertStringContainsString('121212', $crawler->filter('a.btn')->attr('href') ?? '');

        $this->assertStringContainsString(
            '/connect/logout',
            $crawler->filter('#main-container a')->last()->attr('href') ?? ''
        );
    }
}
