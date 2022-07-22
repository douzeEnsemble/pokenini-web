<?php

namespace functionnal\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PokedexControllerTest extends WebTestCase
{
    public function testList(): void
    {
        $client = static::createClient();

        $client->request('GET', '/pokedex/redgreenblueyellow');

        $this->assertResponseIsSuccessful();
        $this->assertPageTitleSame('Pokédex Red, Green, Blue, Yellow');

        $mainCrawler = $client->getCrawler();

        $expectedPokemonCount = 149;

        $this->assertEquals(
            $expectedPokemonCount,
            $mainCrawler->filter('.card')->count()
        );

        $this->assertEquals(
            $expectedPokemonCount,
            $mainCrawler->filter('.card .card-action select')->count()
        );

        $options = $mainCrawler->filter('#bulbasaur select option');
        $this->assertEquals(5, $options->count());

        $icon = $mainCrawler->filter('#bulbasaur .card-image img');
        $this->assertEquals(
            'https://raw.githubusercontent.com/msikma/pokesprite/master/pokemon-gen8/regular/bulbasaur.png',
            $icon->attr('src')
        );

        $label = $mainCrawler->filter('#bulbasaur .card-content p');
        $this->assertEquals(
            'Bulbasaur',
            $label->text()
        );

        $this->assertNotNull($mainCrawler->filter('#bulbasaur .card.green.lighten-4'));
        $this->assertNotNull($mainCrawler->filter('#ivysaur .card.light-blue.lighten-2'));
        $this->assertNotNull($mainCrawler->filter('#venusaur .card.amber.lighten-2'));
        $this->assertNotNull($mainCrawler->filter('#charmander .card.green.lighten-1'));
        $this->assertNotNull($mainCrawler->filter('#charmeleon .card.red.lighten-2'));
    }
}
