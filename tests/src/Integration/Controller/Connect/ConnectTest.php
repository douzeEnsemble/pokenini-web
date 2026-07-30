<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Connect;

use App\Controller\ConnectController;
use App\Tests\Common\Traits\TestNavTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @internal
 */
#[CoversClass(ConnectController::class)]
final class ConnectTest extends WebTestCase
{
    use TestNavTrait;

    public function testConnectPage(): void
    {
        $client = self::createClient();

        $crawler = $client->request('GET', '/fr/connect');

        $this->assertResponseStatusCodeSame(200);

        $this->assertSame(
            'Pokénini Connexion',
            $crawler->filter('title')->text()
        );

        $this->assertSame(
            'Connexion',
            $crawler->filter('h1')->text()
        );

        $this->assertCountFilter($crawler, 1, 'h1');
        $this->assertCountFilter($crawler, 1, '#main-container ul.nav');
        $this->assertCountFilter($crawler, 2, '#main-container ul.nav li');
        $this->assertCountFilter($crawler, 2, '#main-container ul.nav li a');

        $index = 0;
        $this->assertConnectLink($crawler, 'Discord', 'dd', $index);
        ++$index;
        $this->assertConnectLink($crawler, 'Google', 'g', $index);

        $this->assertCount(0, $crawler->filter('.navbar-link'));
    }

    public function testGoogleConnectPage(): void
    {
        $client = self::createClient();

        // A plain, user-initiated visit to /fr/connect/g has no '_security_target_path' in
        // session (that key is only set by AuthenticationEntryPoint during silent re-auth), so
        // Google must not be asked to force the heavy 'prompt=consent' re-consent screen.
        $client->request('GET', '/fr/connect/g');

        $this->assertResponseStatusCodeSame(302);
        $crawler = $client->followRedirect();

        $this->assertStringStartsWith(
            'https://accounts.google.com/o/oauth2/v2/auth?scope=openid%20email%20profile&access_type=offline&state=',
            (string) $crawler->getUri()
        );
    }

    public function testFakeConnectPage(): void
    {
        $client = self::createClient();

        $client->request('GET', '/fr/connect/f/c?t=test');

        $this->assertResponseStatusCodeSame(302);
    }

    private function assertConnectLink(Crawler $crawler, string $label, string $shortName, int $index): void
    {
        $this->assertEquals($label, $crawler->filter('#main-container ul.nav li')->eq($index)->text());
        $this->assertEquals('/fr/connect/'.$shortName, $crawler->filter('#main-container ul.nav li a')->eq($index)->attr('href'));
    }
}
