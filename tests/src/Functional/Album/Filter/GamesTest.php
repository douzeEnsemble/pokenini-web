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
class GamesTest extends WebTestCase
{
    use TestNavTrait;

    public function testFilterSwordShieldOriginalGame(): void
    {
        $client = static::createClient();

        $crawler = $client->request(
            'GET',
            '/fr/album/demo?ogb[]=swordshield&t=7b52009b64fd0a2a49e6d8a939753077792b0554'
        );

        $this->assertCountFilter($crawler, 5, '.album-case');

        $this->assertCountFilter($crawler, 0, 'h2.box');
        $this->assertCountFilter($crawler, 0, '#bulbasaur');
        $this->assertCountFilter($crawler, 0, '#venusaur-f');
        $this->assertCountFilter($crawler, 0, '#venusaur-mega');
        $this->assertCountFilter($crawler, 1, '#venusaur-gmax');
        $this->assertCountFilter($crawler, 0, '#charmander');
        $this->assertCountFilter($crawler, 0, '#tauros');
        $this->assertCountFilter($crawler, 0, '#tauros-paldea');
        $this->assertCountFilter($crawler, 0, '#tauros-paldea-blaze');
        $this->assertCountFilter($crawler, 0, '#tauros-paldea-aqua');

        $this->assertCountFilter($crawler, 0, '.toast');

        $this->assertCountFilter($crawler, 13, 'table a');
        $this->assertEquals(
            '/fr/album/demo?cs=no&ogb%5B0%5D=swordshield&t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $crawler->filter('table a')->first()->attr('href')
        );
        $this->assertEquals(
            '/fr/album/demo?ogb%5B0%5D=swordshield&t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $crawler->filter('table a')->last()->attr('href')
        );

        $this->assertSelectedOptions($crawler, 'select#any_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#primary_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#secondary_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#category_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#regional_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#special_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#variant_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#original_game_bundle', ['swordshield']);
        $this->assertSelectedOptions($crawler, 'select#game_bundle_availability', ['']);
        $this->assertCount(0, $crawler->filter('select#game_bundle_shiny_availability'));
        $this->assertSelectedOptions($crawler, 'select#collection_availability', ['']);
    }

    public function testFilterSwordShieldAndXYOriginalGame(): void
    {
        $client = static::createClient();

        $crawler = $client->request(
            'GET',
            '/fr/album/demo?ogb[]=swordshield&ogb[]=xy&t=7b52009b64fd0a2a49e6d8a939753077792b0554'
        );

        $this->assertCountFilter($crawler, 9, '.album-case');

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

        $this->assertCountFilter($crawler, 13, 'table a');
        $this->assertEquals(
            '/fr/album/demo?cs=no&ogb%5B0%5D=swordshield&ogb%5B1%5D=xy&t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $crawler->filter('table a')->first()->attr('href')
        );
        $this->assertEquals(
            '/fr/album/demo?ogb%5B0%5D=swordshield&ogb%5B1%5D=xy&t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $crawler->filter('table a')->last()->attr('href')
        );

        $this->assertSelectedOptions($crawler, 'select#any_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#primary_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#secondary_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#category_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#regional_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#special_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#variant_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#original_game_bundle', ['swordshield', 'xy']);
        $this->assertSelectedOptions($crawler, 'select#game_bundle_availability', ['']);
        $this->assertCount(0, $crawler->filter('select#game_bundle_shiny_availability'));
        $this->assertSelectedOptions($crawler, 'select#collection_availability', ['']);
    }

    public function testFilterOriginalGameUnknown(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr/album/demo?ogb[]=unknown&t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertCountFilter($crawler, 0, '.album-case');

        $this->assertCountFilter($crawler, 0, 'h2.box');

        $this->assertCountFilter($crawler, 13, 'table a');
        $this->assertEquals(
            '/fr/album/demo?cs=no&ogb%5B0%5D=unknown&t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $crawler->filter('table a')->first()->attr('href')
        );
        $this->assertEquals(
            '/fr/album/demo?ogb%5B0%5D=unknown&t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $crawler->filter('table a')->last()->attr('href')
        );

        $this->assertSelectedOptions($crawler, 'select#any_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#primary_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#secondary_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#category_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#regional_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#special_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#variant_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#original_game_bundle', []);
        $this->assertSelectedOptions($crawler, 'select#game_bundle_availability', ['']);
        $this->assertCount(0, $crawler->filter('select#game_bundle_shiny_availability'));
        $this->assertSelectedOptions($crawler, 'select#collection_availability', ['']);
    }

    public function testFilterSwordShieldGameBundle(): void
    {
        $client = static::createClient();

        $crawler = $client->request(
            'GET',
            '/fr/album/demo?gba[]=swordshield&t=7b52009b64fd0a2a49e6d8a939753077792b0554'
        );

        $this->assertCountFilter($crawler, 18, '.album-case');

        $this->assertCountFilter($crawler, 0, 'h2.box');
        $this->assertCountFilter($crawler, 1, '#bulbasaur');
        $this->assertCountFilter($crawler, 1, '#venusaur-f');
        $this->assertCountFilter($crawler, 0, '#venusaur-mega');
        $this->assertCountFilter($crawler, 1, '#venusaur-gmax');
        $this->assertCountFilter($crawler, 1, '#charmander');
        $this->assertCountFilter($crawler, 1, '#tauros');
        $this->assertCountFilter($crawler, 0, '#tauros-paldea');
        $this->assertCountFilter($crawler, 0, '#tauros-paldea-blaze');
        $this->assertCountFilter($crawler, 0, '#tauros-paldea-aqua');

        $this->assertCountFilter($crawler, 0, '.toast');

        $this->assertCountFilter($crawler, 13, 'table a');
        $this->assertEquals(
            '/fr/album/demo?cs=no&gba%5B0%5D=swordshield&t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $crawler->filter('table a')->first()->attr('href')
        );
        $this->assertEquals(
            '/fr/album/demo?gba%5B0%5D=swordshield&t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $crawler->filter('table a')->last()->attr('href')
        );

        $this->assertSelectedOptions($crawler, 'select#any_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#primary_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#secondary_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#category_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#regional_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#special_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#variant_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#original_game_bundle', ['']);
        $this->assertSelectedOptions($crawler, 'select#game_bundle_availability', ['swordshield']);
        $this->assertCount(0, $crawler->filter('select#game_bundle_shiny_availability'));
        $this->assertSelectedOptions($crawler, 'select#collection_availability', ['']);
    }

    public function testFilterSwordShieldGameBundleShiny(): void
    {
        $client = static::createClient();

        $crawler = $client->request(
            'GET',
            '/fr/album/demoshiny?gbsa[]=swordshield&t=7b52009b64fd0a2a49e6d8a939753077792b0554'
        );

        $this->assertCountFilter($crawler, 18, '.album-case');

        $this->assertCountFilter($crawler, 0, 'h2.box');
        $this->assertCountFilter($crawler, 1, '#bulbasaur');
        $this->assertCountFilter($crawler, 1, '#venusaur-f');
        $this->assertCountFilter($crawler, 0, '#venusaur-mega');
        $this->assertCountFilter($crawler, 1, '#venusaur-gmax');
        $this->assertCountFilter($crawler, 1, '#charmander');
        $this->assertCountFilter($crawler, 1, '#tauros');
        $this->assertCountFilter($crawler, 0, '#tauros-paldea');
        $this->assertCountFilter($crawler, 0, '#tauros-paldea-blaze');
        $this->assertCountFilter($crawler, 0, '#tauros-paldea-aqua');

        $this->assertCountFilter($crawler, 0, '.toast');

        $this->assertCountFilter($crawler, 13, 'table a');
        $this->assertEquals(
            '/fr/album/demoshiny?cs=no&gbsa%5B0%5D=swordshield&t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $crawler->filter('table a')->first()->attr('href')
        );
        $this->assertEquals(
            '/fr/album/demoshiny?gbsa%5B0%5D=swordshield&t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $crawler->filter('table a')->last()->attr('href')
        );

        $this->assertSelectedOptions($crawler, 'select#any_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#primary_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#secondary_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#category_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#regional_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#special_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#variant_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#original_game_bundle', ['']);
        $this->assertCount(0, $crawler->filter('select#game_bundle_availability'));
        $this->assertSelectedOptions($crawler, 'select#game_bundle_shiny_availability', ['swordshield']);
        $this->assertSelectedOptions($crawler, 'select#collection_availability', ['']);
    }

    public function testFilterNotSwordShieldGameBundle(): void
    {
        $client = static::createClient();

        $crawler = $client->request(
            'GET',
            '/fr/album/demo?gba[]=!swordshield&t=7b52009b64fd0a2a49e6d8a939753077792b0554'
        );

        $this->assertCountFilter($crawler, 7, '.album-case');

        $this->assertCountFilter($crawler, 0, 'h2.box');
        $this->assertCountFilter($crawler, 0, '#bulbasaur');
        $this->assertCountFilter($crawler, 0, '#venusaur-f');
        $this->assertCountFilter($crawler, 1, '#venusaur-mega');
        $this->assertCountFilter($crawler, 0, '#venusaur-gmax');
        $this->assertCountFilter($crawler, 0, '#charmander');
        $this->assertCountFilter($crawler, 0, '#tauros');
        $this->assertCountFilter($crawler, 1, '#tauros-paldea');
        $this->assertCountFilter($crawler, 1, '#tauros-paldea-blaze');
        $this->assertCountFilter($crawler, 1, '#tauros-paldea-aqua');

        $this->assertCountFilter($crawler, 0, '.toast');

        $this->assertCountFilter($crawler, 13, 'table a');
        $this->assertEquals(
            '/fr/album/demo?cs=no&gba%5B0%5D=!swordshield&t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $crawler->filter('table a')->first()->attr('href')
        );
        $this->assertEquals(
            '/fr/album/demo?gba%5B0%5D=!swordshield&t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $crawler->filter('table a')->last()->attr('href')
        );

        $this->assertSelectedOptions($crawler, 'select#any_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#primary_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#secondary_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#category_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#regional_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#special_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#variant_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#original_game_bundle', ['']);
        $this->assertSelectedOptions($crawler, 'select#game_bundle_availability', ['!swordshield']);
        $this->assertCount(0, $crawler->filter('select#game_bundle_shiny_availability'));
        $this->assertSelectedOptions($crawler, 'select#collection_availability', ['']);
    }

    public function testFilterNotSwordShieldGameBundleShiny(): void
    {
        $client = static::createClient();

        $crawler = $client->request(
            'GET',
            '/fr/album/demoshiny?gbsa[]=!swordshield&t=7b52009b64fd0a2a49e6d8a939753077792b0554'
        );

        $this->assertCountFilter($crawler, 7, '.album-case');

        $this->assertCountFilter($crawler, 0, 'h2.box');
        $this->assertCountFilter($crawler, 0, '#bulbasaur');
        $this->assertCountFilter($crawler, 0, '#venusaur-f');
        $this->assertCountFilter($crawler, 1, '#venusaur-mega');
        $this->assertCountFilter($crawler, 0, '#venusaur-gmax');
        $this->assertCountFilter($crawler, 0, '#charmander');
        $this->assertCountFilter($crawler, 0, '#tauros');
        $this->assertCountFilter($crawler, 1, '#tauros-paldea');
        $this->assertCountFilter($crawler, 1, '#tauros-paldea-blaze');
        $this->assertCountFilter($crawler, 1, '#tauros-paldea-aqua');

        $this->assertCountFilter($crawler, 0, '.toast');

        $this->assertCountFilter($crawler, 13, 'table a');
        $this->assertEquals(
            '/fr/album/demoshiny?cs=no&gbsa%5B0%5D=!swordshield&t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $crawler->filter('table a')->first()->attr('href')
        );
        $this->assertEquals(
            '/fr/album/demoshiny?gbsa%5B0%5D=!swordshield&t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $crawler->filter('table a')->last()->attr('href')
        );

        $this->assertSelectedOptions($crawler, 'select#any_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#primary_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#secondary_type', ['']);
        $this->assertSelectedOptions($crawler, 'select#category_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#regional_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#special_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#variant_form', ['']);
        $this->assertSelectedOptions($crawler, 'select#original_game_bundle', ['']);
        $this->assertCount(0, $crawler->filter('select#game_bundle_availability'));
        $this->assertSelectedOptions($crawler, 'select#game_bundle_shiny_availability', ['!swordshield']);
        $this->assertSelectedOptions($crawler, 'select#collection_availability', ['']);
    }
}
