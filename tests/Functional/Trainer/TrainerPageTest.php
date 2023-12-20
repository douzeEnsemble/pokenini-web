<?php

declare(strict_types=1);

namespace App\Tests\Functional\Trainer;

use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Common\Traits\TestSetUp;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class TrainerPageTest extends WebTestCase
{
    use TestNavTrait;
    use TestSetUp;

    public function testTrainerPage(): void
    {
        $client = static::createClient();

        $user = new User('789465465489');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/trainer');

        $this->assertResponseStatusCodeSame(200);

        $this->assertCountFilter($crawler, 1, 'h1');
        $this->assertCountFilter($crawler, 2, 'table thead th');
        $this->assertCountFilter($crawler, 1, 'table tbody tr');
        $this->assertEquals('789465465489', $crawler->filter('table tbody tr td')->last()->text());

        $this->assertCustomizeAlbumSection($crawler);

        $this->assertStringContainsString(
            "/connect/logout",
            $crawler->filter('.accordion-item')->last()->filter('a')->attr('href') ?? ''
        );

        $this->assertEquals("Retour à l'accueil", $crawler->filter('.navbar-link')->text());

        $this->assertCountFilter($crawler, 0, '.dex_not_released');
    }

    public function testAdminTrainerPage(): void
    {
        $client = static::createClient();

        $user = new User('8764532');
        $user->addTrainerRole();
        $user->addAdminRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/trainer');

        $this->assertResponseStatusCodeSame(200);

        $this->assertCountFilter($crawler, 1, 'h1');
        $this->assertCountFilter($crawler, 2, 'table thead th');
        $this->assertCountFilter($crawler, 1, 'table tbody tr');
        $this->assertEquals('8764532', $crawler->filter('table tbody tr td')->last()->text());

        $this->assertCustomizeAlbumSection($crawler);

        $this->assertStringContainsString(
            "/connect/logout",
            $crawler->filter('.accordion-item')->last()->filter('a')->attr('href') ?? ''
        );

        $this->assertEquals("Retour à l'accueil", $crawler->filter('.navbar-link')->text());

        $this->assertCountFilter($crawler, 1, '.dex_not_released');
    }

    public function testTrainerPageNotAllowed(): void
    {
        $client = static::createClient();

        $user = new User('789465465489');
        $client->loginUser($user);

        $client->request('GET', '/fr/trainer');

        $this->assertResponseStatusCodeSame(403);
    }

    private function assertCustomizeAlbumSection(Crawler $crawler): void
    {
        $this->assertCountFilter($crawler, 21, '.trainer-dex-item');
        $this->assertCountFilter($crawler, 21, '.trainer-dex-item img');
        $this->assertCountFilter($crawler, 21, '.trainer-dex-item a');
        $this->assertCountFilter($crawler, 21, '.trainer-dex-item h5');
        $this->assertCountFilter($crawler, 21, '.trainer-dex-item h6');
        $this->assertCountFilter($crawler, 42, '.trainer-dex-item input[type="checkbox"]');

        $this->assertEmpty($crawler->filter('#redgreenblueyellow-is_private')->attr('checked'));
        $this->assertNull($crawler->filter('#redgreenblueyellow-is_on_home')->attr('checked'));

        $this->assertNull($crawler->filter('#home-is_private')->attr('checked'));
        $this->assertEmpty($crawler->filter('#home-is_on_home')->attr('checked'));

        $this->assertStringContainsString(
            'https://icon.pokenini.fr/banner/',
            (string) $crawler->filter('.trainer-dex-item img')->eq(0)->attr('src')
        );
    }
}
