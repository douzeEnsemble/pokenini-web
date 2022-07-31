<?php

namespace functionnal\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PokedexControllerTest extends WebTestCase
{
    public function testList(): void
    {
        $client = static::createClient();

        $client->request('GET', '/pokedex/demo');

        $this->assertResponseIsSuccessful();
        $this->assertPageTitleSame('Pokédex Demo');

        $mainCrawler = $client->getCrawler();

        $expectedPokemonCount = 1734;

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

        $this->assertEquals(
            1,
            $mainCrawler
                ->filter('#bulbasaur.card.red.lighten-2')->count());
        $this->assertEquals(
            1,
            $mainCrawler
                ->filter('#ivysaur.card.red.lighten-2')
                ->count()
        );
        $this->assertEquals(
            1,
            $mainCrawler
                ->filter('#venusaur.card.green.lighten-4')
                ->count()
        );
        $this->assertEquals(
            1,
            $mainCrawler
                ->filter('#venusaur-f.card.light-blue.lighten-2')
                ->count()
        );
        $this->assertEquals(
            1,
            $mainCrawler
                ->filter('#venusaur-mega.card.amber.lighten-2')
                ->count()
        );
        $this->assertEquals(
            1,
            $mainCrawler
                ->filter('#venusaur-gmax.card.green.lighten-1')
                ->count()
        );

        $this->assertEquals(
            1734,
            $mainCrawler
                ->filter('.pokedex-case.card.s2')
                ->count()
        );
        $this->assertEquals(
            289,
            $mainCrawler
                ->filter('div.row')
                ->count()
        );
        $this->assertEquals(
            58,
            $mainCrawler
                ->filter('h2')
                ->count()
        );
    }
}
