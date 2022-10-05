<?php

namespace App\Tests\Common\Traits;

use Symfony\Component\DomCrawler\Crawler;

trait TestNavTrait
{
    public function assertLangSwitch(Crawler $crawler): void
    {
        $langItems = $crawler->filter('.lang-switch .dropdown-item');
        $this->assertCount(2, $langItems);
        $this->assertStringContainsString(
            '/fr/',
            $langItems->eq(0)->attr('href') ?? ''
        );
        $this->assertEquals(
            'Français',
            $langItems->eq(0)->text()
        );

        $this->assertStringContainsString(
            '/en/',
            $langItems->eq(1)->attr('href') ?? ''
        );
        $this->assertEquals(
            'English',
            $langItems->eq(1)->text()
        );
    }

    public function assertNoConnectedNavBar(Crawler $crawler): void
    {
        $this->assertCount(2, $crawler->filter('.navbar-nav .nav-item'));
        $this->assertCount(4, $crawler->filter('.navbar-nav .nav-item a'));
    }

    public function assertConnectedNavBar(Crawler $crawler): void
    {
        $this->assertCount(4, $crawler->filter('.navbar-nav .nav-item'));
        $this->assertCount(8, $crawler->filter('.navbar-nav .nav-item a'));
    }
}
