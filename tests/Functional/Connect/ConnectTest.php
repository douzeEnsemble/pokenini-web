<?php

declare(strict_types=1);

namespace App\Tests\Functional\Connect;

use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Common\Traits\TestSetUp;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ConnectTest extends WebTestCase
{
    use TestNavTrait;
    use TestSetUp;

    public function testConnectPage(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr/connect');

        $this->assertResponseStatusCodeSame(200);

        $this->assertCountFilter($crawler, 1, 'h1');
        $this->assertCountFilter($crawler, 1, '#main-container ul.nav');
        $this->assertCountFilter($crawler, 1, '#main-container ul.nav li');
        $this->assertEquals('Google', $crawler->filter('#main-container ul.nav li')->text());
        $this->assertCountFilter($crawler, 1, '#main-container ul.nav li a');
        $this->assertEquals('/fr/connect/g', $crawler->filter('#main-container ul.nav li a')->attr('href'));

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
}
