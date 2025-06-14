<?php

declare(strict_types=1);

namespace App\Tests\Functional\Connect;

use App\Controller\ConnectController;
use App\Tests\Common\Traits\TestNavTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @internal
 */
#[CoversClass(ConnectController::class)]
class ConnectTest extends WebTestCase
{
    use TestNavTrait;

    public function testConnectPage(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr/connect');

        $this->assertResponseStatusCodeSame(200);

        $this->assertCountFilter($crawler, 1, 'h1');
        $this->assertCountFilter($crawler, 1, '#main-container ul.nav');
        $this->assertCountFilter($crawler, 4, '#main-container ul.nav li');
        $this->assertCountFilter($crawler, 4, '#main-container ul.nav li a');

        $index = 0;
        $this->assertConnectLink($crawler, 'Amazon', 'az', $index++);
        $this->assertConnectLink($crawler, 'Discord', 'dd', $index++);
        $this->assertConnectLink($crawler, 'Google', 'g', $index++);
        $this->assertConnectLink($crawler, 'Passage', 'p', $index++);

        $this->assertEquals("Retour à l'accueil", $crawler->filter('.navbar-link')->text());
    }

    public function testGoogleConnectPage(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr/connect/g');

        $this->assertResponseStatusCodeSame(302);
        $crawler = $client->followRedirect();

        $this->assertStringStartsWith(
            'https://accounts.google.com/o/oauth2/v2/auth?scope=openid%20email%20profile&state=',
            (string) $crawler->getUri()
        );
    }

    public function testFakeConnectPage(): void
    {
        $client = static::createClient();

        $client->request('GET', '/fr/connect/f/c');

        $this->assertResponseStatusCodeSame(404);
    }

    private function assertConnectLink(Crawler $crawler, string $label, string $shortName, int $index): void
    {
        $this->assertEquals($label, $crawler->filter('#main-container ul.nav li')->eq($index)->text());
        $this->assertEquals('/fr/connect/'.$shortName, $crawler->filter('#main-container ul.nav li a')->eq($index)->attr('href'));
    }
}
