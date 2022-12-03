<?php

declare(strict_types=1);

namespace App\Tests\Common\Traits;

use Symfony\Component\DomCrawler\Crawler;

trait TestNavTrait
{
    public function assertEnglishLangSwitch(Crawler $crawler): void
    {
        $langItem = $crawler->filter('.lang-switch');
        $this->assertCount(1, $langItem);
        $this->assertStringContainsString(
            '/fr/',
            $langItem->filter('a')->attr('href') ?? ''
        );
        $this->assertEquals(
            'Français',
            $langItem->filter('a')->text()
        );
    }

    public function assertFrenchLangSwitch(Crawler $crawler): void
    {
        $langItem = $crawler->filter('.lang-switch');
        $this->assertCount(1, $langItem);
        $this->assertStringContainsString(
            '/en/',
            $langItem->filter('a')->attr('href') ?? ''
        );
        $this->assertEquals(
            'English',
            $langItem->filter('a')->text()
        );
    }

    public function assertNoConnectedNavBar(Crawler $crawler): void
    {
        $this->assertCount(1, $crawler->filter('.navbar-nav .lang-switch'));

        $this->assertCount(1, $crawler->filter('.navbar-nav .trainer-link'));
        $this->assertStringContainsString(
            '/connect/',
            $crawler->filter('.navbar-nav .trainer-link a')->attr('href') ?? ''
        );
    }

    public function assertConnectedNavBar(Crawler $crawler): void
    {
        $this->assertCount(1, $crawler->filter('.navbar-nav .lang-switch'));
        $this->assertCount(1, $crawler->filter('.navbar-nav .admin-link'));
    }

    public function assertTrainerAlbumNavBar(Crawler $crawler): void
    {
        $this->assertCount(1, $crawler->filter('.navbar-nav .lang-switch'));
        $this->assertCount(0, $crawler->filter('.navbar-nav .mode-switch'));
        $this->assertCount(0, $crawler->filter('.navbar-nav .admin-link'));
    }

    public function assertAdminAlbumNavBar(Crawler $crawler): void
    {
        $this->assertCount(1, $crawler->filter('.navbar-nav .lang-switch'));
        $this->assertCount(0, $crawler->filter('.navbar-nav .mode-switch'));
        $this->assertCount(1, $crawler->filter('.navbar-nav .admin-link'));
    }
}
