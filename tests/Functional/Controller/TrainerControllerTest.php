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

        $this->assertStringContainsString(
            "/connect/logout",
            $crawler->filter('.accordion-item')->last()->filter('a')->attr('href') ?? ''
        );

        $this->assertEquals("Retour à l'accueil", $crawler->filter('.navbar-brand')->text());
    }
}
