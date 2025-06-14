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
            '/fr',
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
            '/en',
            $langItem->filter('a')->attr('href') ?? ''
        );
        $this->assertEquals(
            'English',
            $langItem->filter('a')->text()
        );
    }

    public function assertNoConnectedNavBar(Crawler $crawler): void
    {
        $this->assertCountFilter($crawler, 1, '.navbar-nav .lang-switch');

        $this->assertCountFilter($crawler, 1, '.navbar-nav .trainer-link');
        $this->assertStringContainsString(
            '/connect',
            $crawler->filter('.navbar-nav .trainer-link a')->attr('href') ?? ''
        );
    }

    public function assertConnectedNavBar(Crawler $crawler): void
    {
        $this->assertCountFilter($crawler, 1, '.navbar-nav .lang-switch');
        $this->assertCountFilter($crawler, 1, '.navbar-nav .admin-link');
    }

    public function assertTrainerAlbumNavBar(Crawler $crawler): void
    {
        $this->assertCountFilter($crawler, 1, '.navbar-nav .lang-switch');
        $this->assertCountFilter($crawler, 0, '.navbar-nav .admin-link');
    }

    public function assertAdminAlbumNavBar(Crawler $crawler): void
    {
        $this->assertCountFilter($crawler, 1, '.navbar-nav .lang-switch');
        $this->assertCountFilter($crawler, 1, '.navbar-nav .admin-link');
    }

    public function assertCountFilter(
        Crawler $crawler,
        int $expectedValue,
        string $selector,
        ?int $index = null,
        string $innerSelector = ''
    ): void {
        if (null === $index) {
            $this->assertCount($expectedValue, $crawler->filter($selector));

            return;
        }

        $this->assertCount(
            $expectedValue,
            $crawler
                ->filter($selector)
                ->eq($index)
                ->filter($innerSelector)
        );
    }

    /**
     * @param string[] $selectedValues
     */
    private function assertSelectedOptions(
        Crawler $crawler,
        string $selectSelector,
        array $selectedValues,
    ): void {
        $this->assertCountFilter($crawler, count($selectedValues), "{$selectSelector} option[selected]");

        foreach ($selectedValues as $selectedValue) {
            $this->assertCountFilter($crawler, 1, "{$selectSelector} option[value='{$selectedValue}'][selected]");
        }
    }
}
