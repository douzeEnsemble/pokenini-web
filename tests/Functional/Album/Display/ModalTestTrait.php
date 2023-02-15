<?php

declare(strict_types=1);

namespace App\Tests\Functional\Album\Display;

use Symfony\Component\DomCrawler\Crawler;

trait ModalTestTrait
{
    public function assertModalTitle(
        Crawler $crawler,
        string $pokemonSlug,
        string $primaryName,
        string $secondaryName,
    ): void {
        $this->assertCountFilter($crawler, 1, "#modal-$pokemonSlug h4.modal-title");
        $this->assertEquals(
            "$primaryName / $secondaryName",
            $crawler->filter("#modal-$pokemonSlug h4.modal-title")->text()
        );

        $this->assertCountFilter($crawler, 1, "#modal-$pokemonSlug h4.modal-title .modal-subtitle");
        $this->assertEquals(
            "/ $secondaryName",
            $crawler->filter("#modal-$pokemonSlug h4.modal-title .modal-subtitle")->text()
        );
    }

    public function assertModalImagesRegularAtFirst(
        Crawler $crawler,
        string $pokemonSlug,
    ): void {
        $this->assertCountFilter($crawler, 2, "#modal-$pokemonSlug .modal-body .album-modal-image");
        $this->assertCountFilter(
            $crawler,
            1,
            "#modal-$pokemonSlug .modal-body .album-modal-image-container-regular"
        );
        $this->assertCountFilter(
            $crawler,
            0,
            "#modal-$pokemonSlug .modal-body .album-modal-image-container-regular[hidden]"
        );
        $this->assertCountFilter(
            $crawler,
            1,
            "#modal-$pokemonSlug .modal-body .album-modal-image-container-shiny"
        );
        $this->assertCountFilter(
            $crawler,
            1,
            "#modal-$pokemonSlug .modal-body .album-modal-image-container-shiny[hidden]"
        );
    }

    public function assertModalImagesShinyAtFirst(
        Crawler $crawler,
        string $pokemonSlug,
    ): void {
        $this->assertCountFilter($crawler, 2, "#modal-$pokemonSlug .modal-body .album-modal-image");
        $this->assertCountFilter(
            $crawler,
            1,
            "#modal-$pokemonSlug .modal-body .album-modal-image-container-regular"
        );
        $this->assertCountFilter(
            $crawler,
            1,
            "#modal-$pokemonSlug .modal-body .album-modal-image-container-regular[hidden]"
        );
        $this->assertCountFilter(
            $crawler,
            1,
            "#modal-$pokemonSlug .modal-body .album-modal-image-container-shiny"
        );
        $this->assertCountFilter(
            $crawler,
            0,
            "#modal-$pokemonSlug .modal-body .album-modal-image-container-shiny[hidden]"
        );
    }

    public function assertModalItemNames(
        Crawler $crawler,
        string $pokemonSlug,
        string $primaryName,
        string $secondaryName,
    ): void {
        $this->assertCountFilter($crawler, 1, "#modal-$pokemonSlug .modal-body .list-group-item", 0, 'strong');
        $this->assertCountFilter($crawler, 1, "#modal-$pokemonSlug .modal-body .list-group-item", 0, 'em');

        $this->assertEquals(
            "$primaryName / $secondaryName",
            $crawler->filter("#modal-$pokemonSlug .modal-body .list-group-item")
                ->eq(0)
                ->text()
        );
        $this->assertEquals(
            $primaryName,
            $crawler->filter("#modal-$pokemonSlug .modal-body .list-group-item")
                ->eq(0)
                ->filter('strong')
                ->text()
        );
        $this->assertEquals(
            $secondaryName,
            $crawler->filter("#modal-$pokemonSlug .modal-body .list-group-item")
                ->eq(0)
                ->filter('em')
                ->text()
        );
    }

    public function assertModalItemForms(
        Crawler $crawler,
        string $pokemonSlug,
        string $lang,
        string $formsLabel,
    ): void {
        $this->assertCountFilter($crawler, 1, "#modal-$pokemonSlug .modal-body .list-group-item", 1, 'strong');
        $this->assertCountFilter($crawler, 1, "#modal-$pokemonSlug .modal-body .list-group-item", 1, 'span');

        $formsPrefix = (('fr' === $lang) ? 'Forme' : 'Form');

        $this->assertEquals(
            "$formsPrefix $formsLabel",
            $crawler->filter("#modal-$pokemonSlug .modal-body .list-group-item")
                ->eq(1)
                ->text()
        );
        $this->assertEquals(
            $formsPrefix,
            $crawler->filter("#modal-$pokemonSlug .modal-body .list-group-item")
                ->eq(1)
                ->filter('strong')
                ->text()
        );
        $this->assertEquals(
            $formsLabel,
            $crawler->filter("#modal-$pokemonSlug .modal-body .list-group-item")
                ->eq(1)
                ->filter('span')
                ->text()
        );
    }

    public function assertModalItemNationalDexNumber(
        Crawler $crawler,
        string $pokemonSlug,
        string $lang,
        int $dexNumber,
    ): void {
        $this->assertCountFilter($crawler, 1, "#modal-$pokemonSlug .modal-body .list-group-item", 2, 'strong');
        $this->assertCountFilter($crawler, 1, "#modal-$pokemonSlug .modal-body .list-group-item", 2, 'span');

        $dexNumberPrefix = (('fr' === $lang) ? 'Numéro de dex national' : 'National dex number');

        $this->assertEquals(
            "$dexNumberPrefix $dexNumber",
            $crawler->filter("#modal-$pokemonSlug .modal-body .list-group-item")
                ->eq(2)
                ->text()
        );
        $this->assertEquals(
            $dexNumberPrefix,
            $crawler->filter("#modal-$pokemonSlug .modal-body .list-group-item")
                ->eq(2)
                ->filter('strong')
                ->text()
        );
        $this->assertEquals(
            $dexNumber,
            $crawler->filter("#modal-$pokemonSlug .modal-body .list-group-item")
                ->eq(2)
                ->filter('span')
                ->text()
        );
    }

    public function assertModalItemRegionalDexNumber(
        Crawler $crawler,
        string $pokemonSlug,
        string $lang,
        int $dexNumber,
    ): void {
        $this->assertCountFilter($crawler, 1, "#modal-$pokemonSlug .modal-body .list-group-item", 3, 'strong');
        $this->assertCountFilter($crawler, 1, "#modal-$pokemonSlug .modal-body .list-group-item", 3, 'span');

        $dexNumberPrefix = (('fr' === $lang) ? 'Numéro de dex régional' : 'Regional dex number');

        $this->assertEquals(
            "$dexNumberPrefix $dexNumber",
            $crawler->filter("#modal-$pokemonSlug .modal-body .list-group-item")
                ->eq(3)
                ->text()
        );
        $this->assertEquals(
            $dexNumberPrefix,
            $crawler->filter("#modal-$pokemonSlug .modal-body .list-group-item")
                ->eq(3)
                ->filter('strong')
                ->text()
        );
        $this->assertEquals(
            $dexNumber,
            $crawler->filter("#modal-$pokemonSlug .modal-body .list-group-item")
                ->eq(3)
                ->filter('span')
                ->text()
        );
    }

    public function assertModalItemPokepediaLink(
        Crawler $crawler,
        string $pokemonSlug,
        string $lang,
        string $pokemonFrenchName,
        bool $withRegionalItem,
    ): void {
        $index = $withRegionalItem ? 4 : 3;

        $this->assertCountFilter($crawler, 1, "#modal-$pokemonSlug .modal-body .list-group-item", $index, 'a');

        $this->assertEquals(
            ('fr' === $lang ? 'Fiche Poképédia' : "Poképédia's page (french)"),
            $crawler->filter("#modal-$pokemonSlug .modal-body .list-group-item")
                ->eq($index)
                ->text()
        );
        $this->assertEquals(
            "https://www.pokepedia.fr/$pokemonFrenchName",
            $crawler->filter("#modal-$pokemonSlug .modal-body .list-group-item")
                ->eq($index)
                ->filter('a')
                ->attr('href')
        );
        $this->assertEquals(
            '_blank',
            $crawler->filter("#modal-$pokemonSlug .modal-body .list-group-item")
                ->eq($index)
                ->filter('a')
                ->attr('target')
        );
    }

    public function assertModalItemBulbapediaLink(
        Crawler $crawler,
        string $pokemonSlug,
        string $lang,
        string $pokemonEnglishName,
        bool $withRegionalItem,
    ): void {
        $index = $withRegionalItem ? 5 : 4;

        $this->assertCountFilter($crawler, 1, "#modal-$pokemonSlug .modal-body .list-group-item", $index, 'a');

        $this->assertEquals(
            ('fr' === $lang ? 'Fiche Bulbapedia (anglais)' : "Bulbapedia's page"),
            $crawler->filter("#modal-$pokemonSlug .modal-body .list-group-item")
                ->eq($index)
                ->text()
        );
        $this->assertEquals(
            "https://bulbapedia.bulbagarden.net/wiki/{$pokemonEnglishName}_(Pokémon)",
            $crawler->filter("#modal-$pokemonSlug .modal-body .list-group-item")
                ->eq($index)
                ->filter('a')
                ->attr('href')
        );
        $this->assertEquals(
            '_blank',
            $crawler->filter("#modal-$pokemonSlug .modal-body .list-group-item")
                ->eq($index)
                ->filter('a')
                ->attr('target')
        );
    }

    public function assertModalItemIcons(
        Crawler $crawler,
        string $pokemonSlug,
        string $lang,
        bool $withRegionalItem,
    ): void {
        $index = $withRegionalItem ? 6 : 5;

        $this->assertCountFilter($crawler, 2, "#modal-$pokemonSlug .modal-body .list-group-item", $index, 'a');
        $this->assertCountFilter($crawler, 2, "#modal-$pokemonSlug .modal-body .list-group-item", $index, 'img');

        $this->assertEquals(
            ('fr' === $lang ? 'Normal' : 'Regular'),
            $crawler->filter("#modal-$pokemonSlug .modal-body .list-group-item")
                ->eq($index)
                ->filter('a')
                ->eq(0)
                ->text()
        );
        $this->assertEquals(
            ('fr' === $lang ? 'Chromatique' : 'Shiny'),
            $crawler->filter("#modal-$pokemonSlug .modal-body .list-group-item")
                ->eq($index)
                ->filter('a')
                ->eq(1)
                ->text()
        );
    }
}
