<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\AlbumController;

use App\Tests\Common\Traits\TestNavTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AlbumControllerCustomeIconTest extends WebTestCase
{
    use TestNavTrait;

    public function testCustomPokemonIconForm(): void
    {
        $client = static::createClient();

        $client->request('GET', '/fr/album/r/demolite?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $mainCrawler = $client->getCrawler();

        $this->assertEquals(
            'https://raw.githubusercontent.com/msikma/pokesprite/master/pokemon-gen8/regular/bulbasaur.png',
            $mainCrawler->filter('#bulbasaur .album-image')->attr('src')
        );

        $this->assertEquals(
            'https://raw.githubusercontent.com/msikma/pokesprite/master/pokemon-gen8/regular/charmander.png',
            $mainCrawler->filter('#charmander .album-image')->attr('src')
        );

        $this->assertEquals(
            'https://archives.bulbagarden.net/media/upload/0/0b/HOME007.png',
            $mainCrawler->filter('#squirtle .album-image')->attr('src')
        );
    }

    public function testCustomPokemonShinyIconForm(): void
    {
        $client = static::createClient();

        $client->request('GET', '/fr/album/r/demoliteshiny?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $mainCrawler = $client->getCrawler();

        $this->assertEquals(
            'https://raw.githubusercontent.com/msikma/pokesprite/master/pokemon-gen8/shiny/bulbasaur.png',
            $mainCrawler->filter('#bulbasaur .album-image')->attr('src')
        );

        $this->assertEquals(
            'https://raw.githubusercontent.com/msikma/pokesprite/master/pokemon-gen8/shiny/charmander.png',
            $mainCrawler->filter('#charmander .album-image')->attr('src')
        );

        $this->assertEquals(
            'https://archives.bulbagarden.net/media/upload/0/0b/HOME007_s.png',
            $mainCrawler->filter('#squirtle .album-image')->attr('src')
        );
    }
}
