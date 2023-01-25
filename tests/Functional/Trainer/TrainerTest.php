<?php

declare(strict_types=1);

namespace App\Tests\Functional\Trainer;

use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class TrainerTest extends WebTestCase
{
    use TestNavTrait;

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

        $this->assertEquals("Retour à l'accueil", $crawler->filter('.navbar-brand')->text());
    }

    public function assertCustomizeAlbumSection(Crawler $crawler): void
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
    }
}
