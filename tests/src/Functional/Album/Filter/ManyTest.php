<?php

declare(strict_types=1);

namespace App\Tests\Functional\Album\Filter;

use App\Controller\AlbumIndexController;
use App\Tests\Common\Traits\TestNavTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(AlbumIndexController::class)]
class ManyTest extends WebTestCase
{
    use TestNavTrait;

    public function testFilterCatchStateToBreedAndFamilyBulbasaur(): void
    {
        $client = static::createClient();

        $crawler = $client->request(
            'GET',
            '/fr/album/demo?cs=tobreed&f=bulbasaur&t=7b52009b64fd0a2a49e6d8a939753077792b0554'
        );

        $this->assertCountFilter($crawler, 1, '.album-case');

        $this->assertCountFilter($crawler, 0, 'h2.box');
        $this->assertCountFilter($crawler, 0, '#bulbasaur');
        $this->assertCountFilter($crawler, 1, '#venusaur-f');
        $this->assertCountFilter($crawler, 0, '#charmander');
        $this->assertCountFilter($crawler, 0, '#wartortle');

        $this->assertCountFilter($crawler, 0, '.toast');

        $this->assertCountFilter($crawler, 13, 'table a');
        $this->assertEquals(
            '/fr/album/demo?cs=no&f=bulbasaur&t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $crawler->filter('table a')->first()->attr('href')
        );
        $this->assertEquals(
            '/fr/album/demo?f=bulbasaur&t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $crawler->filter('table a')->last()->attr('href')
        );
    }

    public function testFilterCatchStateYesAndFamilyCharmander(): void
    {
        $client = static::createClient();

        $crawler = $client->request(
            'GET',
            '/fr/album/demo?cs=yes&f=charmander&t=7b52009b64fd0a2a49e6d8a939753077792b0554'
        );

        $this->assertCountFilter($crawler, 2, '.album-case');

        $this->assertCountFilter($crawler, 0, 'h2.box');
        $this->assertCountFilter($crawler, 0, '#bulbasaur');
        $this->assertCountFilter($crawler, 0, '#venusaur-f');
        $this->assertCountFilter($crawler, 1, '#charmander');
        $this->assertCountFilter($crawler, 0, '#wartortle');

        $this->assertCountFilter($crawler, 0, '.toast');

        $this->assertCountFilter($crawler, 13, 'table a');
        $this->assertEquals(
            '/fr/album/demo?cs=no&f=charmander&t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $crawler->filter('table a')->first()->attr('href')
        );
        $this->assertEquals(
            '/fr/album/demo?f=charmander&t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $crawler->filter('table a')->last()->attr('href')
        );
    }
}
