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
class TypesTest extends WebTestCase
{
    use TestNavTrait;

    public function testFilterPrimaryTypeFire(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr/album/demo?t1[]=fire&t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertCountFilter($crawler, 6, '.album-case');

        $this->assertCountFilter($crawler, 0, 'h2.box');
        $this->assertCountFilter($crawler, 0, '#bulbasaur');
        $this->assertCountFilter($crawler, 0, '#venusaur-f');
        $this->assertCountFilter($crawler, 0, '#venusaur-mega');
        $this->assertCountFilter($crawler, 0, '#venusaur-gmax');
        $this->assertCountFilter($crawler, 1, '#charmander');
        $this->assertCountFilter($crawler, 0, '#tauros');
        $this->assertCountFilter($crawler, 0, '#tauros-paldea');
        $this->assertCountFilter($crawler, 0, '#tauros-paldea-blaze');
        $this->assertCountFilter($crawler, 0, '#tauros-paldea-aqua');

        $this->assertCountFilter($crawler, 0, '.toast');

        $this->assertCountFilter($crawler, 13, 'table a');
        $this->assertEquals(
            '/fr/album/demo?cs=no&t=7b52009b64fd0a2a49e6d8a939753077792b0554&t1%5B0%5D=fire',
            $crawler->filter('table a')->first()->attr('href')
        );
        $this->assertEquals(
            '/fr/album/demo?t=7b52009b64fd0a2a49e6d8a939753077792b0554&t1%5B0%5D=fire',
            $crawler->filter('table a')->last()->attr('href')
        );

        $this->assertSelectedOptions($crawler, 'select#any_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#primary_type', ['fire']);
        $this->assertSelectedOptions($crawler, 'select#secondary_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#category_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#regional_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#special_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#variant_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#original_game_bundle', ['']);
        $this->assertSelectedOptions($crawler, 'select#game_bundle_availability', ['']);
        $this->assertCount(0, $crawler->filter('select#game_bundle_shiny_availability'));
        $this->assertSelectedOptions($crawler, 'select#collection_availability', ['']);
    }

    public function testFilterSecondaryTypePoisonOrFlying(): void
    {
        $client = static::createClient();

        $crawler = $client->request(
            'GET',
            '/fr/album/demo?t2[]=poison&t2[]=flying&t=7b52009b64fd0a2a49e6d8a939753077792b0554'
        );

        $this->assertCountFilter($crawler, 9, '.album-case');

        $this->assertCountFilter($crawler, 0, 'h2.box');
        $this->assertCountFilter($crawler, 1, '#bulbasaur');
        $this->assertCountFilter($crawler, 1, '#venusaur-f');
        $this->assertCountFilter($crawler, 1, '#venusaur-mega');
        $this->assertCountFilter($crawler, 1, '#venusaur-gmax');
        $this->assertCountFilter($crawler, 0, '#charmander');
        $this->assertCountFilter($crawler, 0, '#tauros');
        $this->assertCountFilter($crawler, 0, '#tauros-paldea');
        $this->assertCountFilter($crawler, 0, '#tauros-paldea-blaze');
        $this->assertCountFilter($crawler, 0, '#tauros-paldea-aqua');

        $this->assertCountFilter($crawler, 0, '.toast');

        $this->assertCountFilter($crawler, 13, 'table a');
        $this->assertEquals(
            '/fr/album/demo?cs=no&t=7b52009b64fd0a2a49e6d8a939753077792b0554&t2%5B0%5D=poison&t2%5B1%5D=flying',
            $crawler->filter('table a')->first()->attr('href')
        );
        $this->assertEquals(
            '/fr/album/demo?t=7b52009b64fd0a2a49e6d8a939753077792b0554&t2%5B0%5D=poison&t2%5B1%5D=flying',
            $crawler->filter('table a')->last()->attr('href')
        );

        $this->assertSelectedOptions($crawler, 'select#any_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#primary_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#secondary_type', ['poison', 'flying']);
        $this->assertSelectedOptions($crawler, 'select#category_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#regional_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#special_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#variant_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#original_game_bundle', ['']);
        $this->assertSelectedOptions($crawler, 'select#game_bundle_availability', ['']);
        $this->assertCount(0, $crawler->filter('select#game_bundle_shiny_availability'));
        $this->assertSelectedOptions($crawler, 'select#collection_availability', ['']);
    }

    public function testFilterPrimaryTypeFightingAndSecondaryTypeFireOrWater(): void
    {
        $client = static::createClient();

        $crawler = $client->request(
            'GET',
            '/fr/album/demo?t=7b52009b64fd0a2a49e6d8a939753077792b0554&t1[]=fighting&t2[]=fire&t2[]=water',
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

        $this->assertCountFilter($crawler, 13, 'table a');
        $this->assertEquals(
            '/fr/album/demo?cs=no&t=7b52009b64fd0a2a49e6d8a939753077792b0554'
            .'&t1%5B0%5D=fighting&t2%5B0%5D=fire&t2%5B1%5D=water',
            $crawler->filter('table a')->first()->attr('href')
        );
        $this->assertEquals(
            '/fr/album/demo?t=7b52009b64fd0a2a49e6d8a939753077792b0554'
            .'&t1%5B0%5D=fighting&t2%5B0%5D=fire&t2%5B1%5D=water',
            $crawler->filter('table a')->last()->attr('href')
        );

        $this->assertSelectedOptions($crawler, 'select#any_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#primary_type', ['fighting']);
        $this->assertSelectedOptions($crawler, 'select#secondary_type', ['fire', 'water']);
        $this->assertSelectedOptions($crawler, 'select#category_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#regional_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#special_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#variant_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#original_game_bundle', ['']);
        $this->assertSelectedOptions($crawler, 'select#game_bundle_availability', ['']);
        $this->assertCount(0, $crawler->filter('select#game_bundle_shiny_availability'));
        $this->assertSelectedOptions($crawler, 'select#collection_availability', ['']);
    }

    public function testFilterPrimaryTypeFightingAndSecondaryTypeNullFireOrWater(): void
    {
        $client = static::createClient();

        $crawler = $client->request(
            'GET',
            '/fr/album/demo?t=7b52009b64fd0a2a49e6d8a939753077792b0554&t1[]=fighting&t2[]=null&t2[]=fire&t2[]=water',
        );

        $this->assertCountFilter($crawler, 3, '.album-case');

        $this->assertCountFilter($crawler, 0, 'h2.box');
        $this->assertCountFilter($crawler, 0, '#bulbasaur');
        $this->assertCountFilter($crawler, 0, '#venusaur-f');
        $this->assertCountFilter($crawler, 0, '#venusaur-mega');
        $this->assertCountFilter($crawler, 0, '#venusaur-gmax');
        $this->assertCountFilter($crawler, 0, '#charmander');
        $this->assertCountFilter($crawler, 0, '#tauros');
        $this->assertCountFilter($crawler, 1, '#tauros-paldea');
        $this->assertCountFilter($crawler, 1, '#tauros-paldea-blaze');
        $this->assertCountFilter($crawler, 1, '#tauros-paldea-aqua');

        $this->assertCountFilter($crawler, 0, '.toast');

        $this->assertCountFilter($crawler, 13, 'table a');
        $this->assertEquals(
            '/fr/album/demo?cs=no&t=7b52009b64fd0a2a49e6d8a939753077792b0554'
             .'&t1%5B0%5D=fighting&t2%5B0%5D=null&t2%5B1%5D=fire&t2%5B2%5D=water',
            $crawler->filter('table a')->first()->attr('href')
        );
        $this->assertEquals(
            '/fr/album/demo?t=7b52009b64fd0a2a49e6d8a939753077792b0554'
             .'&t1%5B0%5D=fighting&t2%5B0%5D=null&t2%5B1%5D=fire&t2%5B2%5D=water',
            $crawler->filter('table a')->last()->attr('href')
        );

        $this->assertSelectedOptions($crawler, 'select#any_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#primary_type', ['fighting']);
        $this->assertSelectedOptions($crawler, 'select#secondary_type', ['null', 'fire', 'water']);
        $this->assertSelectedOptions($crawler, 'select#category_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#regional_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#special_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#variant_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#original_game_bundle', ['']);
        $this->assertSelectedOptions($crawler, 'select#game_bundle_availability', ['']);
        $this->assertCount(0, $crawler->filter('select#game_bundle_shiny_availability'));
        $this->assertSelectedOptions($crawler, 'select#collection_availability', ['']);
    }

    public function testFilterAnyTypeFire(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr/album/demo?at[]=fire&t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertCountFilter($crawler, 7, '.album-case');

        $this->assertCountFilter($crawler, 0, 'h2.box');
        $this->assertCountFilter($crawler, 0, '#bulbasaur');
        $this->assertCountFilter($crawler, 0, '#venusaur-f');
        $this->assertCountFilter($crawler, 0, '#venusaur-mega');
        $this->assertCountFilter($crawler, 0, '#venusaur-gmax');
        $this->assertCountFilter($crawler, 1, '#charmander');
        $this->assertCountFilter($crawler, 0, '#tauros');
        $this->assertCountFilter($crawler, 0, '#tauros-paldea');
        $this->assertCountFilter($crawler, 1, '#tauros-paldea-blaze');
        $this->assertCountFilter($crawler, 0, '#tauros-paldea-aqua');

        $this->assertCountFilter($crawler, 0, '.toast');

        $this->assertCountFilter($crawler, 13, 'table a');
        $this->assertEquals(
            '/fr/album/demo?at%5B0%5D=fire&cs=no&t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $crawler->filter('table a')->first()->attr('href')
        );
        $this->assertEquals(
            '/fr/album/demo?at%5B0%5D=fire&t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $crawler->filter('table a')->last()->attr('href')
        );

        $this->assertSelectedOptions($crawler, 'select#any_type', ['fire']);
        $this->assertSelectedOptions($crawler, 'select#primary_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#secondary_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#category_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#regional_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#special_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#variant_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#original_game_bundle', ['']);
        $this->assertSelectedOptions($crawler, 'select#game_bundle_availability', ['']);
        $this->assertCount(0, $crawler->filter('select#game_bundle_shiny_availability'));
        $this->assertSelectedOptions($crawler, 'select#collection_availability', ['']);
    }

    public function testFilterTypeUnknown(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr/album/demo?t1[]=unknown&t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertCountFilter($crawler, 0, '.album-case');

        $this->assertCountFilter($crawler, 0, 'h2.box');

        $this->assertCountFilter($crawler, 13, 'table a');
        $this->assertEquals(
            '/fr/album/demo?cs=no&t=7b52009b64fd0a2a49e6d8a939753077792b0554&t1%5B0%5D=unknown',
            $crawler->filter('table a')->first()->attr('href')
        );
        $this->assertEquals(
            '/fr/album/demo?t=7b52009b64fd0a2a49e6d8a939753077792b0554&t1%5B0%5D=unknown',
            $crawler->filter('table a')->last()->attr('href')
        );

        $this->assertSelectedOptions($crawler, 'select#any_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#primary_type', []);
        $this->assertSelectedOptions($crawler, 'select#secondary_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#category_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#regional_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#special_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#variant_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#original_game_bundle', ['']);
        $this->assertSelectedOptions($crawler, 'select#game_bundle_availability', ['']);
        $this->assertCount(0, $crawler->filter('select#game_bundle_shiny_availability'));
        $this->assertSelectedOptions($crawler, 'select#collection_availability', ['']);
    }
}
