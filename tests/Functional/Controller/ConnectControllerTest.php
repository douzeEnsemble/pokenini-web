<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class ConnectControllerTest extends WebTestCase
{
    public function testConnectPage(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr/connect/');

        $this->assertResponseStatusCodeSame(200);

        $this->assertCount(1, $crawler->filter('h1'));
        $this->assertCount(1, $crawler->filter('ul.nav'));
        $this->assertCount(1, $crawler->filter('ul.nav li'));
        $this->assertEquals('Google', $crawler->filter('ul.nav li')->text());
        $this->assertCount(1, $crawler->filter('ul.nav li a'));
        $this->assertEquals('/fr/connect/g', $crawler->filter('ul.nav li a')->attr('href'));

        $this->assertEquals("Retour à l'accueil", $crawler->filter('.navbar-brand')->text());
    }
}
