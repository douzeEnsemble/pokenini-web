<?php

namespace App\Tests\Common\Traits;

use Symfony\Component\DomCrawler\Crawler;

trait TestNavTrait
{
    public function assertLangSwitch(Crawler $crawler): void
    {
        $langItems = $crawler->filter('.lang-switch .dropdown-item');
        $this->assertCount(2, $langItems);
        $this->assertEquals(
            '?lang=fr',
            $langItems->eq(0)->attr('href')
        );
        $this->assertEquals(
            'Français',
            $langItems->eq(0)->text()
        );

        $this->assertEquals(
            '?lang=en',
            $langItems->eq(1)->attr('href')
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
        $this->assertCount(3, $crawler->filter('.navbar-nav .nav-item'));
        $this->assertCount(5, $crawler->filter('.navbar-nav .nav-item a'));
    }
}
