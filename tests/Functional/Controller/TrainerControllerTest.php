<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class TrainerControllerTest extends WebTestCase
{
    public function testTrainerPage(): void
    {
        $client = static::createClient();

        $user = new User('789465465489');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/trainer/');

        $this->assertResponseStatusCodeSame(200);

        $this->assertCount(1, $crawler->filter('h1'));
        $this->assertCount(2, $crawler->filter('table thead th'));
        $this->assertCount(1, $crawler->filter('table tbody tr'));
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
        $this->assertCount(21, $crawler->filter('.trainer-dex-item'));
        $this->assertCount(21, $crawler->filter('.trainer-dex-item img'));
        $this->assertCount(21, $crawler->filter('.trainer-dex-item a'));
        $this->assertCount(21, $crawler->filter('.trainer-dex-item h5'));
        $this->assertCount(21, $crawler->filter('.trainer-dex-item h6'));
        $this->assertCount(42, $crawler->filter('.trainer-dex-item input[type="checkbox"]'));

        $this->assertEmpty($crawler->filter('#redgreenblueyellow-is_private')->attr('checked'));
        $this->assertNull($crawler->filter('#redgreenblueyellow-is_on_home')->attr('checked'));

        $this->assertNull($crawler->filter('#home-is_private')->attr('checked'));
        $this->assertEmpty($crawler->filter('#home-is_on_home')->attr('checked'));
    }
}
