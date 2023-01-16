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

        $crawler = $client->getCrawler();

        $expectedPokemonCount = 1738;

        $this->assertCountFilter($crawler, $expectedPokemonCount, '.album-case');

        $icon = $crawler->filter('#bulbasaur .album-case-image img');
        $this->assertEquals(
            'https://raw.githubusercontent.com/msikma/pokesprite/master/pokemon-gen8/regular/bulbasaur.png',
            $icon->attr('src')
        );

        $this->assertEquals(
            html_entity_decode('&nbsp;'),
            $crawler->filter('#bulbasaur .album-case-forms')->text()
        );
        $this->assertEquals(
            '♀️',
            $crawler->filter('#venusaur-f .album-case-forms')->text()
        );

        $this->assertCountFilter($crawler, 1, '#bulbasaur.album-case.catch-state-no');
        $this->assertCountFilter($crawler, 1, '#ivysaur.album-case.catch-state-no');
        $this->assertCountFilter($crawler, 1, '#venusaur.album-case.catch-state-toevolve');
        $this->assertCountFilter($crawler, 1, '#venusaur-f.album-case.catch-state-tobreed');
        $this->assertCountFilter($crawler, 1, '#venusaur-mega.album-case.catch-state-totransfer');
        $this->assertCountFilter($crawler, 1, '#venusaur-gmax.album-case.catch-state-totrade');
        $this->assertCountFilter($crawler, 1, '#charmander.album-case.catch-state-yes');
    }

    protected function assertReadMode(KernelBrowser $client): void
    {
        $crawler = $client->getCrawler();

        $this->assertCountFilter($crawler, 0, '.album-case select');
        $this->assertCountFilter($crawler, 1738, '.album-case .album-case-catch-state');

        $this->assertCountFilter($crawler, 0, '.toast');

        $this->assertCountFilter($crawler, 0, 'script[src="/js/album_edit.js"]');

        $this->assertStringNotContainsString('const catchStates = JSON.parse', $crawler->outerHtml());
        $this->assertStringNotContainsString('watchCatchStates();', $crawler->outerHtml());
    }

    protected function assertWriteMode(KernelBrowser $client): void
    {
        $crawler = $client->getCrawler();

        $this->assertCountFilter($crawler, 1738, '.album-case select');

        $this->assertCountFilter($crawler, 6, '#bulbasaur select option');

        $this->assertCountFilter($crawler, 0, '.album-case .album-case-catch-state');

        $this->assertCountFilter($crawler, 3476, '.toast');
        $this->assertCountFilter($crawler, 1738, '.toast.text-bg-success');
        $this->assertCountFilter($crawler, 1738, '.toast.text-bg-danger');

        $this->assertCountFilter($crawler, 1, 'script[src="/js/album_edit.js"]');

        $this->assertStringContainsString('const catchStates = JSON.parse', $crawler->outerHtml());
        $this->assertStringContainsString('watchCatchStates();', $crawler->outerHtml());
    }

    protected function assertStatistics(KernelBrowser $client): void
    {
        $crawler = $client->getCrawler();

        $this->assertCountFilter($crawler, 1, 'h2#stats');

        $this->assertCountFilter($crawler, 1, '.progress');
        $this->assertCountFilter($crawler, 6, '.progress-bar');

        $this->assertEquals(
            '99.71%',
            $crawler->filter('.progress-bar.catch-state-no')->text()
        );
        $this->assertEmpty(
            $crawler->filter('.progress-bar.catch-state-toevolve')->text()
        );
        $this->assertEmpty(
            $crawler->filter('.progress-bar.catch-state-tobreed')->text()
        );
        $this->assertEmpty(
            $crawler->filter('.progress-bar.catch-state-totransfer')->text()
        );
        $this->assertEquals(
            '0.12%',
            $crawler->filter('.progress-bar.catch-state-yes')->text()
        );

        $this->assertCountFilter($crawler, 1, 'table#report');
        $this->assertCountFilter($crawler, 7, 'table#report tr');

        $this->assertCountFilter($crawler, 1, 'table#report tr.catch-state-no');
        $this->assertCountFilter($crawler, 1, 'table#report tr.catch-state-toevolve');
        $this->assertCountFilter($crawler, 1, 'table#report tr.catch-state-tobreed');
        $this->assertCountFilter($crawler, 1, 'table#report tr.catch-state-totransfer');
        $this->assertCountFilter($crawler, 1, 'table#report tr.catch-state-yes');
        $this->assertCountFilter($crawler, 1, 'table#report tr.catch-state-total');

        $this->assertEquals(
            1731,
            $crawler->filter('table#report tr.catch-state-no td')->text()
        );
        $this->assertEquals(
            1,
            $crawler->filter('table#report tr.catch-state-toevolve td')->text()
        );
        $this->assertEquals(
            1,
            $crawler->filter('table#report tr.catch-state-tobreed td')->text()
        );
        $this->assertEquals(
            1,
            $crawler->filter('table#report tr.catch-state-totransfer td')->text()
        );
        $this->assertEquals(
            2,
            $crawler->filter('table#report tr.catch-state-yes td')->text()
        );
        $this->assertEquals(
            1736,
            $crawler->filter('table#report tr.catch-state-total td')->text()
        );

        $this->assertStringContainsString(
            '/album/r/demo/no',
            (string) $crawler->filter('table#report tr.catch-state-no a')->attr('href')
        );
        $this->assertStringContainsString(
            '/album/r/demo/toevolve',
            (string) $crawler->filter('table#report tr.catch-state-toevolve a')->attr('href')
        );
        $this->assertStringContainsString(
            '/album/r/demo/tobreed',
            (string) $crawler->filter('table#report tr.catch-state-tobreed a')->attr('href')
        );
        $this->assertStringContainsString(
            '/album/r/demo/totransfer',
            (string) $crawler->filter('table#report tr.catch-state-totransfer a')->attr('href')
        );
        $this->assertStringContainsString(
            '/album/r/demo/yes',
            (string) $crawler->filter('table#report tr.catch-state-yes a')->attr('href')
        );
        $this->assertStringContainsString(
            '/album/r/demo',
            (string) $crawler->filter('table#report tr.catch-state-total a')->attr('href')
        );
    }

    protected function assertNavigationBar(KernelBrowser $client): void
    {
        $crawler = $client->getCrawler();

        $this->assertNoConnectedNavBar($crawler);
    }

    protected function assertRegular(KernelBrowser $client): void
    {
        $crawler = $client->getCrawler();

        $this->assertStringContainsString(
            '/regular/',
            $crawler->filter('.album-image')->first()->attr('src') ?? ''
        );
    }

    protected function assertShiny(KernelBrowser $client): void
    {
        $crawler = $client->getCrawler();

        $this->assertStringContainsString(
            '/shiny/',
            $crawler->filter('.album-image')->first()->attr('src') ?? ''
        );
    }
}
