<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class AdminControllerTest extends WebTestCase
{
    use TestNavTrait;

    public function testAdminHomeNotConnected(): void
    {
        $client = static::createClient();

        $client->request('GET', '/fr/istration');

        $this->assertResponseStatusCodeSame(307);
    }

    public function testAdminHomeBadCredentials(): void
    {
        $client = static::createClient();

        $client->loginUser(new User('34654656489621361987'));

        $client->request('GET', '/fr/istration');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testAdminHomeConnected(): void
    {
        $this->getAdminHomeConnected();
    }

    public function testAdminHome(): void
    {
        $crawler = $this->getAdminHomeConnected();

        $this->assertCountFilter($crawler, 2, 'h2');
        $this->assertCountFilter($crawler, 7, 'h3');
        $this->assertCountFilter($crawler, 11, 'a.admin-item');
        $this->assertCountFilter($crawler, 11, 'a.admin-item i.bi');

        $this->assertCountFilter($crawler, 5, '.list-group-update a.admin-item');
        $this->assertCountFilter($crawler, 2, '.list-group-calculate a.admin-item');
        $this->assertCountFilter($crawler, 3, '.list-group-invalidate a.admin-item');

        $this->assertCountFilter($crawler, 0, 'script[src="/js/album.js"]');

        $this->assertStringNotContainsString('const catchStates = JSON.parse', $crawler->outerHtml());
        $this->assertStringNotContainsString('watchCatchStates();', $crawler->outerHtml());
    }

    private function getAdminHomeConnected(): Crawler
    {
        $client = static::createClient();

        $user = new User('8764532');
        $user->addAdminRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/istration');

        $this->assertResponseStatusCodeSame(200);

        $this->assertConnectedNavBar($crawler);
        $this->assertFrenchLangSwitch($crawler);

        return $crawler;
    }
}
