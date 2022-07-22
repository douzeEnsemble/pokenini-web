<?php

namespace App\Tests\Functionnal\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class PokemonControllerTest extends WebTestCase
{
    public function testList(): void
    {
        $client = static::createClient();

        $client->request('GET', '/pokemon/');

        $mainCrawler = $client->getCrawler();

        $this->assertResponseIsSuccessful();
        $this->assertPageTitleSame('Liste des pokémons');

        // Table Header
        $this->assertSelectorTextSame(
            'table thead tr',
            '# Name Icon Shiny Icon Origin Generation Origin Game Bundle Family'
        );

        $this->assertSelectorTextSame(
            'table tbody tr:nth-child(1)',
            '0 Egg 2 Gold, Silver, Crystal'
        );
        $this->assertTrHasId($mainCrawler, 1, 'egg');
        $this->assertHasNoFamilyLink($mainCrawler, 1);
        $this->assertNonPokemonRegularIconImage($mainCrawler, 1, 'egg');
        $this->assertNonPokemonShinyIconImage($mainCrawler, 1, 'egg');

        $this->assertSelectorTextSame(
            'table tbody tr:nth-child(2)',
            '1 Bulbasaur 1 Red, Green, Blue, Yellow'
        );
        $this->assertTrHasId($mainCrawler, 2, 'bulbasaur');
        $this->assertHasNoFamilyLink($mainCrawler, 2);
        $this->assertPokemonRegularIconImage($mainCrawler, 2, 'bulbasaur');
        $this->assertPokemonShinyIconImage($mainCrawler, 2, 'bulbasaur');

        $this->assertSelectorTextSame(
            'table tbody tr:nth-child(12)',
            '6 Mega Charizard Y 6 X, Y Charmander'
        );
        $this->assertTrHasId($mainCrawler, 12, 'charizard-mega-y');
        $this->assertFamilyLink($mainCrawler, 12, 'charmander');
        $this->assertPokemonRegularIconImage($mainCrawler, 12, 'charizard-mega-y');
        $this->assertPokemonShinyIconImage($mainCrawler, 12, 'charizard-mega-y');

        $this->assertSelectorTextSame(
            'table tbody tr:nth-child(120)',
            '55 Alpha Golduck 8 Legend Arceus Psyduck'
        );
        $this->assertTrHasId($mainCrawler, 120, 'golduck-alpha');
        $this->assertFamilyLink($mainCrawler, 120, 'psyduck');
        $this->assertPokemonRegularIconImage($mainCrawler, 120, 'golduck');
        $this->assertPokemonShinyIconImage($mainCrawler, 120, 'golduck');

        $this->assertSelectorTextSame(
            'table tbody tr:nth-child(1200)',
            '598 Ferrothorn 5 Black, White Ferroseed'
        );
        $this->assertTrHasId($mainCrawler, 1200, 'ferrothorn');
        $this->assertFamilyLink($mainCrawler, 1200, 'ferroseed');
        $this->assertPokemonRegularIconImage($mainCrawler, 1200, 'ferrothorn');
        $this->assertPokemonShinyIconImage($mainCrawler, 1200, 'ferrothorn');
    }

    private function assertPokemonRegularIconImage(
        Crawler $mainCrawler,
        int $rowIndex,
        string $iconName
    ): void {
        $this->assertIconImage($mainCrawler, 3, $rowIndex, "regular/{$iconName}.png", 'Icon of ');
    }

    private function assertNonPokemonRegularIconImage(
        Crawler $mainCrawler,
        int $rowIndex,
        string $iconName
    ): void {
        $this->assertIconImage($mainCrawler, 3, $rowIndex, "{$iconName}.png", 'Icon of ');
    }

    private function assertPokemonShinyIconImage(
        Crawler $mainCrawler,
        int $rowIndex,
        string $iconName
    ): void {
        $this->assertIconImage($mainCrawler, 4, $rowIndex, "shiny/{$iconName}.png", 'Shiny icon of ');
    }

    private function assertNonPokemonShinyIconImage(
        Crawler $mainCrawler,
        int $rowIndex,
        string $iconName
    ): void {
        $this->assertIconImage($mainCrawler, 4, $rowIndex, "{$iconName}.png", 'Shiny icon of ');
    }

    private function assertIconImage(
        Crawler $mainCrawler,
        int $columnIndex,
        int $rowIndex,
        string $imageEndPath,
        string $expectedAlt,
    ): void {
        $crawler = $mainCrawler->filter(
            'table tbody tr:nth-child(' . $rowIndex . ') td:nth-child(' . $columnIndex . ')'
        );
        // To be sure there is only img tag node and no empty text node
        $this->assertEquals(1, $crawler->getNode(0)?->childNodes->count());

        $node = $crawler->getNode(0)?->childNodes->item(0);
        $this->assertEquals('img', $node?->nodeName);
        $this->assertEquals(
            "https://raw.githubusercontent.com/msikma/pokesprite/master/pokemon-gen8/{$imageEndPath}",
            $node?->attributes?->getNamedItem('src')?->textContent
        );
        $this->assertStringContainsString(
            $expectedAlt,
            $node?->attributes?->getNamedItem('alt')?->textContent ?? ''
        );
    }

    private function assertTrHasId(Crawler $mainCrawler, int $rowIndex, string $expectedValue): void
    {
        $crawler = $mainCrawler->filter('table tbody tr:nth-child(' . $rowIndex . ')');

        $node = $crawler->getNode(0);

        $this->assertEquals($expectedValue, $node?->attributes?->getNamedItem('id')?->textContent);
    }

    private function assertFamilyLink(Crawler $mainCrawler, int $rowIndex, string $expectedValue): void
    {
        $crawler = $mainCrawler->filter('table tbody tr:nth-child(' . $rowIndex . ') td:nth-child(7)');

        $node = $crawler->getNode(0);

        $this->assertEquals(1, $node?->childNodes->count());

        $familyLink = $node?->childNodes->item(0);

        $this->assertEquals("#{$expectedValue}", $familyLink?->attributes?->getNamedItem('href')?->textContent);
    }

    private function assertHasNoFamilyLink(Crawler $mainCrawler, int $rowIndex): void
    {
        $crawler = $mainCrawler->filter('table tbody tr:nth-child(' . $rowIndex . ') td:nth-child(7)');

        $node = $crawler->getNode(0);

        $this->assertEquals('', $node?->textContent);
    }
}
