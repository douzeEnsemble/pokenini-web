<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\AlbumController;

use App\Tests\Common\Traits\TestNavTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AbstractAlbumControllerTestCase extends WebTestCase
{
    use TestNavTrait;

    protected function assertAlbum(KernelBrowser $client): void
    {
        $this->assertResponseIsSuccessful();

        $mainCrawler = $client->getCrawler();

        $expectedPokemonCount = 1738;

        $this->assertCount(
            $expectedPokemonCount,
            $mainCrawler->filter('.album-case')
        );

        $icon = $mainCrawler->filter('#bulbasaur .album-case-image img');
        $this->assertEquals(
            'https://raw.githubusercontent.com/msikma/pokesprite/master/pokemon-gen8/regular/bulbasaur.png',
            $icon->attr('src')
        );

        $this->assertEquals(
            html_entity_decode('&nbsp;'),
            $mainCrawler->filter('#bulbasaur .album-case-forms')->text()
        );
        $this->assertEquals(
            '♀️',
            $mainCrawler->filter('#venusaur-f .album-case-forms')->text()
        );

        $this->assertCount(
            1,
            $mainCrawler
                ->filter('#bulbasaur.album-case.catch-state-no')
        );
        $this->assertCount(
            1,
            $mainCrawler
                ->filter('#ivysaur.album-case.catch-state-no')
        );
        $this->assertCount(
            1,
            $mainCrawler
                ->filter('#venusaur.album-case.catch-state-toevolve')
        );
        $this->assertCount(
            1,
            $mainCrawler
                ->filter('#venusaur-f.album-case.catch-state-tobreed')
        );
        $this->assertCount(
            1,
            $mainCrawler
                ->filter('#venusaur-mega.album-case.catch-state-totransfer')
        );
        $this->assertCount(
            1,
            $mainCrawler
                ->filter('#venusaur-gmax.album-case.catch-state-totrade')
        );
        $this->assertCount(
            1,
            $mainCrawler
                ->filter('#charmander.album-case.catch-state-yes')
        );
    }

    protected function assertReadMode(KernelBrowser $client): void
    {
        $mainCrawler = $client->getCrawler();

        $this->assertCount(
            0,
            $mainCrawler
                ->filter('.album-case select')
        );

        $this->assertCount(
            1738,
            $mainCrawler
                ->filter('.album-case .album-case-catch-state')
        );

        $this->assertCount(
            0,
            $mainCrawler
                ->filter('.toast')
        );

        $this->assertCount(0, $mainCrawler->filter('script[src="/js/album_edit.js"]'));

        $this->assertStringNotContainsString('const catchStates = JSON.parse', $mainCrawler->outerHtml());
        $this->assertStringNotContainsString('watchCatchStates();', $mainCrawler->outerHtml());
    }

    protected function assertWriteMode(KernelBrowser $client): void
    {
        $mainCrawler = $client->getCrawler();

        $this->assertCount(
            1738,
            $mainCrawler
                ->filter('.album-case select')
        );

        $options = $mainCrawler->filter('#bulbasaur select option');
        $this->assertCount(6, $options);

        $this->assertCount(
            0,
            $mainCrawler
                ->filter('.album-case .album-case-catch-state')
        );

        $this->assertCount(
            3476,
            $mainCrawler
                ->filter('.toast')
        );
        $this->assertCount(
            1738,
            $mainCrawler
                ->filter('.toast.text-bg-success')
        );
        $this->assertCount(
            1738,
            $mainCrawler
                ->filter('.toast.text-bg-danger')
        );

        $this->assertCount(1, $mainCrawler->filter('script[src="/js/album_edit.js"]'));

        $this->assertStringContainsString('const catchStates = JSON.parse', $mainCrawler->outerHtml());
        $this->assertStringContainsString('watchCatchStates();', $mainCrawler->outerHtml());
    }

    protected function assertStatistics(KernelBrowser $client): void
    {
        $mainCrawler = $client->getCrawler();

        $statsTitle = $mainCrawler->filter('h2#stats');
        $this->assertCount(1, $statsTitle);

        $this->assertCount(1, $mainCrawler->filter('.progress'));
        $this->assertCount(6, $mainCrawler->filter('.progress-bar'));

        $this->assertEquals(
            '99.71%',
            $mainCrawler->filter('.progress-bar.catch-state-no')->text()
        );
        $this->assertEmpty(
            $mainCrawler->filter('.progress-bar.catch-state-toevolve')->text()
        );
        $this->assertEmpty(
            $mainCrawler->filter('.progress-bar.catch-state-tobreed')->text()
        );
        $this->assertEmpty(
            $mainCrawler->filter('.progress-bar.catch-state-totransfer')->text()
        );
        $this->assertEquals(
            '0.12%',
            $mainCrawler->filter('.progress-bar.catch-state-yes')->text()
        );

        $this->assertCount(1, $mainCrawler->filter('table#report'));
        $this->assertCount(7, $mainCrawler->filter('table#report tr'));

        $this->assertCount(1, $mainCrawler->filter('table#report tr.catch-state-no'));
        $this->assertCount(1, $mainCrawler->filter('table#report tr.catch-state-toevolve'));
        $this->assertCount(1, $mainCrawler->filter('table#report tr.catch-state-tobreed'));
        $this->assertCount(1, $mainCrawler->filter('table#report tr.catch-state-totransfer'));
        $this->assertCount(1, $mainCrawler->filter('table#report tr.catch-state-yes'));
        $this->assertCount(1, $mainCrawler->filter('table#report tr.catch-state-total'));

        $this->assertEquals(
            1731,
            $mainCrawler->filter('table#report tr.catch-state-no td')->text()
        );
        $this->assertEquals(
            1,
            $mainCrawler->filter('table#report tr.catch-state-toevolve td')->text()
        );
        $this->assertEquals(
            1,
            $mainCrawler->filter('table#report tr.catch-state-tobreed td')->text()
        );
        $this->assertEquals(
            1,
            $mainCrawler->filter('table#report tr.catch-state-totransfer td')->text()
        );
        $this->assertEquals(
            2,
            $mainCrawler->filter('table#report tr.catch-state-yes td')->text()
        );
        $this->assertEquals(
            1736,
            $mainCrawler->filter('table#report tr.catch-state-total td')->text()
        );

        $this->assertStringContainsString(
            '/album/r/demo/no',
            (string) $mainCrawler->filter('table#report tr.catch-state-no a')->attr('href')
        );
        $this->assertStringContainsString(
            '/album/r/demo/toevolve',
            (string) $mainCrawler->filter('table#report tr.catch-state-toevolve a')->attr('href')
        );
        $this->assertStringContainsString(
            '/album/r/demo/tobreed',
            (string) $mainCrawler->filter('table#report tr.catch-state-tobreed a')->attr('href')
        );
        $this->assertStringContainsString(
            '/album/r/demo/totransfer',
            (string) $mainCrawler->filter('table#report tr.catch-state-totransfer a')->attr('href')
        );
        $this->assertStringContainsString(
            '/album/r/demo/yes',
            (string) $mainCrawler->filter('table#report tr.catch-state-yes a')->attr('href')
        );
        $this->assertStringContainsString(
            '/album/r/demo',
            (string) $mainCrawler->filter('table#report tr.catch-state-total a')->attr('href')
        );
    }

    protected function assertNavigationBar(KernelBrowser $client): void
    {
        $mainCrawler = $client->getCrawler();

        $this->assertNoConnectedNavBar($mainCrawler);
    }

    protected function assertRegular(KernelBrowser $client): void
    {
        $mainCrawler = $client->getCrawler();

        $this->assertStringContainsString(
            '/regular/',
            $mainCrawler->filter('.album-image')->first()->attr('src') ?? ''
        );
    }

    protected function assertShiny(KernelBrowser $client): void
    {
        $mainCrawler = $client->getCrawler();

        $this->assertStringContainsString(
            '/shiny/',
            $mainCrawler->filter('.album-image')->first()->attr('src') ?? ''
        );
    }
}
