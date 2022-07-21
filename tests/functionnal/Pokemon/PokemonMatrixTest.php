<?php

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class PokemonMatrixTest extends WebTestCase
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
        $this->assertRegularIconImage($mainCrawler, 1, 'egg', false);
        $this->assertShinyIconImage($mainCrawler, 1, 'egg', false);

        $this->assertSelectorTextSame(
            'table tbody tr:nth-child(2)',
            '1 Bulbasaur 1 Red, Green, Blue, Yellow'
        );
        $this->assertTrHasId($mainCrawler, 2, 'bulbasaur');
        $this->assertHasNoFamilyLink($mainCrawler, 2);
        $this->assertRegularIconImage($mainCrawler, 2, 'bulbasaur');
        $this->assertShinyIconImage($mainCrawler, 2, 'bulbasaur');

        $this->assertSelectorTextSame(
            'table tbody tr:nth-child(12)',
            '6 Mega Charizard Y 6 X, Y Charmander'
        );
        $this->assertTrHasId($mainCrawler, 12, 'charizard-mega-y');
        $this->assertFamilyLink($mainCrawler, 12, 'charmander');
        $this->assertRegularIconImage($mainCrawler, 12, 'charizard-mega-y');
        $this->assertShinyIconImage($mainCrawler, 12, 'charizard-mega-y');

        $this->assertSelectorTextSame(
            'table tbody tr:nth-child(120)',
            '55 Alpha Golduck 8 Legend Arceus Psyduck'
        );
        $this->assertTrHasId($mainCrawler, 120, 'golduck-alpha');
        $this->assertFamilyLink($mainCrawler, 120, 'psyduck');
        $this->assertRegularIconImage($mainCrawler, 120, 'golduck');
        $this->assertShinyIconImage($mainCrawler, 120, 'golduck');

        $this->assertSelectorTextSame(
            'table tbody tr:nth-child(1200)',
            '598 Ferrothorn 5 Black, White Ferroseed'
        );
        $this->assertTrHasId($mainCrawler, 1200, 'ferrothorn');
        $this->assertFamilyLink($mainCrawler, 1200, 'ferroseed');
        $this->assertRegularIconImage($mainCrawler, 1200, 'ferrothorn');
        $this->assertShinyIconImage($mainCrawler, 1200, 'ferrothorn');
    }

    private function assertRegularIconImage(
        Crawler $mainCrawler,
        int $rowIndex,
        string $iconName,
        bool $hasSubdir = true
    ): void {
        $iconPath = $hasSubdir ? "regular/{$iconName}.png" : "{$iconName}.png";

        $this->assertIconImage($mainCrawler, 3, $rowIndex, $iconPath, 'Icon of ');
    }

    private function assertShinyIconImage(
        Crawler $mainCrawler,
        int $rowIndex,
        string $iconName,
        bool $hasSubdir = true
    ): void {
        $iconPath = $hasSubdir ? "shiny/{$iconName}.png" : "{$iconName}.png";

        $this->assertIconImage($mainCrawler, 4, $rowIndex, $iconPath, 'Shiny icon of ');
    }

    private function assertIconImage(
        Crawler $mainCrawler,
        int $columnIndex,
        int $rowIndex,
        string $imageEndPath,
        string $expectedAlt,
    ): void {
        $crawler = $mainCrawler->filter('table tbody tr:nth-child('.$rowIndex.') td:nth-child('.$columnIndex.')');
        // To be sure there is only img tag node and no empty text node
        $this->assertEquals(1, $crawler->getNode(0)?->childNodes->count());

        $imgNode = $crawler->getNode(0)?->childNodes->item(0);
        $this->assertEquals('img', $imgNode?->nodeName);
        $this->assertEquals(
            "https://raw.githubusercontent.com/msikma/pokesprite/master/pokemon-gen8/{$imageEndPath}",
            $imgNode?->attributes?->getNamedItem('src')?->textContent
        );
        $this->assertStringContainsString(
            $expectedAlt,
            $imgNode?->attributes?->getNamedItem('alt')?->textContent ?? ''
        );
    }

    private function assertTrHasId(Crawler $mainCrawler, int $rowIndex, string $expectedValue): void
    {
        $crawler = $mainCrawler->filter('table tbody tr:nth-child('.$rowIndex.')');

        $tr = $crawler->getNode(0);

        $this->assertEquals($expectedValue, $tr?->attributes?->getNamedItem('id')?->textContent);
    }

    private function assertFamilyLink(Crawler $mainCrawler, int $rowIndex, string $expectedValue): void
    {
        $crawler = $mainCrawler->filter('table tbody tr:nth-child('.$rowIndex.') td:nth-child(7)');

        $tdElement = $crawler->getNode(0);

        $this->assertEquals(1, $tdElement?->childNodes->count());

        $familyLink = $tdElement?->childNodes->item(0);

        $this->assertEquals("#{$expectedValue}", $familyLink?->attributes?->getNamedItem('href')?->textContent);
    }

    private function assertHasNoFamilyLink(Crawler $mainCrawler, int $rowIndex): void
    {
        $crawler = $mainCrawler->filter('table tbody tr:nth-child('.$rowIndex.') td:nth-child(7)');

        $tdElement = $crawler->getNode(0);

        $this->assertEquals('', $tdElement?->textContent);
    }
}
