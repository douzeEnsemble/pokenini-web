<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\Common\Traits\TestNavTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class AdminControllerTest extends WebTestCase
{
    use TestNavTrait;

    public function testAdminHomeNotConnected(): void
    {
        $client = static::createClient();

        $client->request('GET', '/fr/istrateur/');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testAdminHomeBadCredentials(): void
    {
        $client = static::createClient();

        $client->request('GET', '/fr/istrateur/', [], [], [
            'PHP_AUTH_USER' => 'renaud',
            'PHP_AUTH_PW'   => '12',
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testAdminHomeConnected(): void
    {
        $this->getAdminHomeConnected();
    }

    public function testAdminHome(): void
    {
        $crawler = $this->getAdminHomeConnected();

        $this->assertCount(1, $crawler->filter('h2'));
        $this->assertCount(5, $crawler->filter('.admin-item'));
        $this->assertCount(5, $crawler->filter('.admin-item a'));
        $this->assertCount(5, $crawler->filter('.admin-item i.bi'));
    }

    private function getAdminHomeConnected(): Crawler
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr/istrateur/', [], [], [
            'PHP_AUTH_USER' => 'renaud',
            'PHP_AUTH_PW'   => 'douze',
        ]);

        $this->assertResponseStatusCodeSame(200);

        $this->assertConnectedNavBar($crawler);
        $this->assertFrenchLangSwitch($crawler);

        return $crawler;
    }
}
