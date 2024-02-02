<?php

declare(strict_types=1);

namespace App\Tests\Functional\Album\Filter;

use App\Tests\Common\Traits\TestNavTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FormsTest extends WebTestCase
{
    use TestNavTrait;

    public function testFilterSpecialMega(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr/album/demo?fs=mega&t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertCountFilter($crawler, 4, '.album-case');

        $this->assertCountFilter($crawler, 0, 'h2.box');
        $this->assertCountFilter($crawler, 0, '#bulbasaur');
        $this->assertCountFilter($crawler, 0, '#venusaur-f');
        $this->assertCountFilter($crawler, 1, '#venusaur-mega');
        $this->assertCountFilter($crawler, 0, '#venusaur-gmax');
        $this->assertCountFilter($crawler, 0, '#charmander');
        $this->assertCountFilter($crawler, 0, '#tauros');
        $this->assertCountFilter($crawler, 0, '#tauros-paldea');
        $this->assertCountFilter($crawler, 0, '#tauros-paldea-blaze');
        $this->assertCountFilter($crawler, 0, '#tauros-paldea-aqua');

        $this->assertCountFilter($crawler, 0, '.toast');

        $this->assertCountFilter($crawler, 7, 'table a');
        $this->assertEquals(
            '/fr/album/demo?cs=no&fs=mega&t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $crawler->filter('table a')->first()->attr('href')
        );
        $this->assertEquals(
            '/fr/album/demo?fs=mega&t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $crawler->filter('table a')->last()->attr('href')
        );
    }

    public function testFilterSpecialMegaAndGigantamax(): void
    {
        $client = static::createClient();

        $crawler = $client->request(
            'GET',
            '/fr/album/demo?fs=mega,gigantamax&t=7b52009b64fd0a2a49e6d8a939753077792b0554',
        );

        $this->assertCountFilter($crawler, 7, '.album-case');

        $this->assertCountFilter($crawler, 0, 'h2.box');
        $this->assertCountFilter($crawler, 0, '#bulbasaur');
        $this->assertCountFilter($crawler, 0, '#venusaur-f');
        $this->assertCountFilter($crawler, 1, '#venusaur-mega');
        $this->assertCountFilter($crawler, 1, '#venusaur-gmax');
        $this->assertCountFilter($crawler, 0, '#charmander');
        $this->assertCountFilter($crawler, 0, '#tauros');
        $this->assertCountFilter($crawler, 0, '#tauros-paldea');
        $this->assertCountFilter($crawler, 0, '#tauros-paldea-blaze');
        $this->assertCountFilter($crawler, 0, '#tauros-paldea-aqua');

        $this->assertCountFilter($crawler, 0, '.toast');

        $this->assertCountFilter($crawler, 7, 'table a');
        $this->assertEquals(
            '/fr/album/demo?cs=no&fs=mega,gigantamax&t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $crawler->filter('table a')->first()->attr('href')
        );
        $this->assertEquals(
            '/fr/album/demo?fs=mega,gigantamax&t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $crawler->filter('table a')->last()->attr('href')
        );
    }

    public function testFilterRegionalPaldeanAndVariantAlternate(): void
    {
        $client = static::createClient();

        $crawler = $client->request(
            'GET',
            '/fr/album/demo?fr=paldean&fv=alternate&t=7b52009b64fd0a2a49e6d8a939753077792b0554',
        );

        $this->assertCountFilter($crawler, 2, '.album-case');

        $this->assertCountFilter($crawler, 0, 'h2.box');
        $this->assertCountFilter($crawler, 0, '#bulbasaur');
        $this->assertCountFilter($crawler, 0, '#venusaur-f');
        $this->assertCountFilter($crawler, 0, '#venusaur-mega');
        $this->assertCountFilter($crawler, 0, '#venusaur-gmax');
        $this->assertCountFilter($crawler, 0, '#charmander');
        $this->assertCountFilter($crawler, 0, '#tauros');
        $this->assertCountFilter($crawler, 0, '#tauros-paldea');
        $this->assertCountFilter($crawler, 1, '#tauros-paldea-blaze');
        $this->assertCountFilter($crawler, 1, '#tauros-paldea-aqua');

        $this->assertCountFilter($crawler, 0, '.toast');

        $this->assertCountFilter($crawler, 7, 'table a');
        $this->assertEquals(
            '/fr/album/demo?cs=no&fr=paldean&fv=alternate&t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $crawler->filter('table a')->first()->attr('href')
        );
        $this->assertEquals(
            '/fr/album/demo?fr=paldean&fv=alternate&t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $crawler->filter('table a')->last()->attr('href')
        );
    }

    public function testFilterSpecialNull(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr/album/demo?fs=null&t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertCountFilter($crawler, 18, '.album-case');

        $this->assertCountFilter($crawler, 0, 'h2.box');
        $this->assertCountFilter($crawler, 1, '#bulbasaur');
        $this->assertCountFilter($crawler, 1, '#venusaur-f');
        $this->assertCountFilter($crawler, 0, '#venusaur-mega');
        $this->assertCountFilter($crawler, 0, '#venusaur-gmax');
        $this->assertCountFilter($crawler, 1, '#charmander');
        $this->assertCountFilter($crawler, 1, '#tauros');
        $this->assertCountFilter($crawler, 1, '#tauros-paldea');
        $this->assertCountFilter($crawler, 1, '#tauros-paldea-blaze');
        $this->assertCountFilter($crawler, 1, '#tauros-paldea-aqua');

        $this->assertCountFilter($crawler, 0, '.toast');

        $this->assertCountFilter($crawler, 7, 'table a');
        $this->assertEquals(
            '/fr/album/demo?cs=no&fs=null&t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $crawler->filter('table a')->first()->attr('href')
        );
        $this->assertEquals(
            '/fr/album/demo?fs=null&t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $crawler->filter('table a')->last()->attr('href')
        );
    }

    public function testFilterSpecialNullAndMega(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr/album/demo?fs=null,mega&t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertCountFilter($crawler, 22, '.album-case');

        $this->assertCountFilter($crawler, 0, 'h2.box');
        $this->assertCountFilter($crawler, 1, '#bulbasaur');
        $this->assertCountFilter($crawler, 1, '#venusaur-f');
        $this->assertCountFilter($crawler, 1, '#venusaur-mega');
        $this->assertCountFilter($crawler, 0, '#venusaur-gmax');
        $this->assertCountFilter($crawler, 1, '#charmander');
        $this->assertCountFilter($crawler, 1, '#tauros');
        $this->assertCountFilter($crawler, 1, '#tauros-paldea');
        $this->assertCountFilter($crawler, 1, '#tauros-paldea-blaze');
        $this->assertCountFilter($crawler, 1, '#tauros-paldea-aqua');

        $this->assertCountFilter($crawler, 0, '.toast');

        $this->assertCountFilter($crawler, 7, 'table a');
        $this->assertEquals(
            '/fr/album/demo?cs=no&fs=null,mega&t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $crawler->filter('table a')->first()->attr('href')
        );
        $this->assertEquals(
            '/fr/album/demo?fs=null,mega&t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $crawler->filter('table a')->last()->attr('href')
        );
    }

    public function testFilterSpecialUnknown(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr/album/demo?fs=unknown&t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertCountFilter($crawler, 0, '.album-case');

        $this->assertCountFilter($crawler, 0, 'h2.box');

        $this->assertCountFilter($crawler, 7, 'table a');
        $this->assertEquals(
            '/fr/album/demo?cs=no&fs=unknown&t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $crawler->filter('table a')->first()->attr('href')
        );
        $this->assertEquals(
            '/fr/album/demo?fs=unknown&t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $crawler->filter('table a')->last()->attr('href')
        );
    }
}
