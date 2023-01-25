<?php

declare(strict_types=1);

namespace App\Tests\Functional\Album\Display;

use App\Tests\Common\Traits\TestNavTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CustomIconTest extends WebTestCase
{
    use TestNavTrait;

    public function testCustomPokemonIconForm(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr/album/demolite?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertResponseIsSuccessful();

        $this->assertEquals(
            'https://raw.githubusercontent.com/msikma/pokesprite/master/pokemon-gen8/regular/bulbasaur.png',
            $crawler->filter('#bulbasaur img')->attr('src')
        );

        $this->assertEquals(
            'https://raw.githubusercontent.com/msikma/pokesprite/master/pokemon-gen8/regular/charmander.png',
            $crawler->filter('#charmander img')->attr('src')
        );

        $this->assertEquals(
            'https://archives.bulbagarden.net/media/upload/0/0b/HOME007.png',
            $crawler->filter('#squirtle img')->attr('src')
        );
    }

    public function testCustomPokemonShinyIconForm(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr/album/demoliteshiny?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertResponseIsSuccessful();

        $this->assertEquals(
            'https://raw.githubusercontent.com/msikma/pokesprite/master/pokemon-gen8/shiny/bulbasaur.png',
            $crawler->filter('#bulbasaur img')->attr('src')
        );

        $this->assertEquals(
            'https://raw.githubusercontent.com/msikma/pokesprite/master/pokemon-gen8/shiny/charmander.png',
            $crawler->filter('#charmander img')->attr('src')
        );

        $this->assertEquals(
            'https://archives.bulbagarden.net/media/upload/0/0b/HOME007_s.png',
            $crawler->filter('#squirtle img')->attr('src')
        );
    }
}
