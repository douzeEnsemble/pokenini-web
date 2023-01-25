<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\Common\Traits\TestNavTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ConnectControllerTest extends WebTestCase
{
    use TestNavTrait;

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

        $this->assertEquals("Retour à l'accueil", $crawler->filter('.navbar-brand')->text());
    }
}
