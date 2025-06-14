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
        $this->assertCountFilter($crawler, 1, "#modal-{$pokemonSlug} h4.modal-title");
        $this->assertEquals(
            "{$primaryName} / {$secondaryName}",
            $crawler->filter("#modal-{$pokemonSlug} h4.modal-title")->text()
        );

        $this->assertCountFilter($crawler, 1, "#modal-{$pokemonSlug} h4.modal-title .modal-subtitle");
        $this->assertEquals(
            "/ {$secondaryName}",
            $crawler->filter("#modal-{$pokemonSlug} h4.modal-title .modal-subtitle")->text()
        );
    }

    public function assertModalImagesRegularAtFirst(
        Crawler $crawler,
        string $pokemonSlug,
    ): void {
        $this->assertCountFilter(
            $crawler,
            2,
            "#modal-{$pokemonSlug} .modal-body .album-modal-image",
        );
        $this->assertCountFilter(
            $crawler,
            1,
            "#modal-{$pokemonSlug} .modal-body .album-modal-image-container-regular"
        );
        $this->assertCountFilter(
            $crawler,
            0,
            "#modal-{$pokemonSlug} .modal-body .album-modal-image-container-regular[hidden]"
        );
        $this->assertCountFilter(
            $crawler,
            1,
            "#modal-{$pokemonSlug} .modal-body .album-modal-image-container-shiny"
        );
        $this->assertCountFilter(
            $crawler,
            1,
            "#modal-{$pokemonSlug} .modal-body .album-modal-image-container-shiny[hidden]"
        );
    }

    public function assertModalImagesShinyAtFirst(
        Crawler $crawler,
        string $pokemonSlug,
    ): void {
        $this->assertCountFilter(
            $crawler,
            2,
            "#modal-{$pokemonSlug} .modal-body .album-modal-image",
        );
        $this->assertCountFilter(
            $crawler,
            1,
            "#modal-{$pokemonSlug} .modal-body .album-modal-image-container-regular"
        );
        $this->assertCountFilter(
            $crawler,
            1,
            "#modal-{$pokemonSlug} .modal-body .album-modal-image-container-regular[hidden]"
        );
        $this->assertCountFilter(
            $crawler,
            1,
            "#modal-{$pokemonSlug} .modal-body .album-modal-image-container-shiny"
        );
        $this->assertCountFilter(
            $crawler,
            0,
            "#modal-{$pokemonSlug} .modal-body .album-modal-image-container-shiny[hidden]"
        );
    }

    public function assertModalItemNames(
        Crawler $crawler,
        string $pokemonSlug,
        string $primaryName,
        string $secondaryName,
    ): void {
        $groupItemIndex = 0;

        $this->assertCountFilter(
            $crawler,
            1,
            "#modal-{$pokemonSlug} .modal-body .list-group-item",
            $groupItemIndex,
            'strong',
        );
        $this->assertCountFilter(
            $crawler,
            1,
            "#modal-{$pokemonSlug} .modal-body .list-group-item",
            $groupItemIndex,
            'em',
        );

        $this->assertEquals(
            "{$primaryName} / {$secondaryName}",
            $crawler->filter("#modal-{$pokemonSlug} .modal-body .list-group-item")
                ->eq($groupItemIndex)
                ->text()
        );
        $this->assertEquals(
            $primaryName,
            $crawler->filter("#modal-{$pokemonSlug} .modal-body .list-group-item")
                ->eq($groupItemIndex)
                ->filter('strong')
                ->text()
        );
        $this->assertEquals(
            $secondaryName,
            $crawler->filter("#modal-{$pokemonSlug} .modal-body .list-group-item")
                ->eq($groupItemIndex)
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
        $groupItemIndex = 1;

        $this->assertCountFilter(
            $crawler,
            1,
            "#modal-{$pokemonSlug} .modal-body .list-group-item",
            $groupItemIndex,
            'strong',
        );
        $this->assertCountFilter(
            $crawler,
            1,
            "#modal-{$pokemonSlug} .modal-body .list-group-item",
            $groupItemIndex,
            'span',
        );

        $formsPrefix = (('fr' === $lang) ? 'Forme' : 'Form');

        $this->assertEquals(
            "{$formsPrefix} {$formsLabel}",
            $crawler->filter("#modal-{$pokemonSlug} .modal-body .list-group-item")
                ->eq($groupItemIndex)
                ->text()
        );
        $this->assertEquals(
            $formsPrefix,
            $crawler->filter("#modal-{$pokemonSlug} .modal-body .list-group-item")
                ->eq($groupItemIndex)
                ->filter('strong')
                ->text()
        );
        $this->assertEquals(
            $formsLabel,
            $crawler->filter("#modal-{$pokemonSlug} .modal-body .list-group-item")
                ->eq($groupItemIndex)
                ->filter('span')
                ->text()
        );
    }

    public function assertModalItemTypes(
        Crawler $crawler,
        string $pokemonSlug,
        string $primaryType,
        ?string $secondaryType = null,
    ): void {
        $nbTypes = (null !== $secondaryType) ? 2 : 1;

        $this->assertCountFilter(
            $crawler,
            $nbTypes,
            "#modal-{$pokemonSlug} .modal-body .list-group-item",
            2,
            'span',
        );
        $this->assertCountFilter(
            $crawler,
            $nbTypes,
            "#modal-{$pokemonSlug} .modal-body .list-group-item",
            2,
            'span.album-modal-types',
        );

        $this->assertEquals(
            $primaryType,
            $crawler->filter("#modal-{$pokemonSlug} .modal-body .list-group-item span.album-modal-type-primary")
                ->eq(0)
                ->text()
        );

        if (null !== $secondaryType) {
            $this->assertEquals(
                $secondaryType,
                $crawler->filter("#modal-{$pokemonSlug} .modal-body .list-group-item span.album-modal-type-secondary")
                    ->eq(0)
                    ->text()
            );
        }
    }

    public function assertModalItemNationalDexNumber(
        Crawler $crawler,
        string $pokemonSlug,
        string $lang,
        int $dexNumber,
    ): void {
        $groupItemIndex = 3;

        $this->assertCountFilter(
            $crawler,
            1,
            "#modal-{$pokemonSlug} .modal-body .list-group-item",
            $groupItemIndex,
            'strong',
        );
        $this->assertCountFilter(
            $crawler,
            1,
            "#modal-{$pokemonSlug} .modal-body .list-group-item",
            $groupItemIndex,
            'span',
        );

        $dexNumberPrefix = (('fr' === $lang) ? 'Numéro de dex national' : 'National dex number');

        $this->assertEquals(
            "{$dexNumberPrefix} {$dexNumber}",
            $crawler->filter("#modal-{$pokemonSlug} .modal-body .list-group-item")
                ->eq($groupItemIndex)
                ->text()
        );
        $this->assertEquals(
            $dexNumberPrefix,
            $crawler->filter("#modal-{$pokemonSlug} .modal-body .list-group-item")
                ->eq($groupItemIndex)
                ->filter('strong')
                ->text()
        );
        $this->assertEquals(
            $dexNumber,
            $crawler->filter("#modal-{$pokemonSlug} .modal-body .list-group-item")
                ->eq($groupItemIndex)
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
        $groupItemIndex = 4;

        $this->assertCountFilter(
            $crawler,
            1,
            "#modal-{$pokemonSlug} .modal-body .list-group-item",
            $groupItemIndex,
            'strong',
        );
        $this->assertCountFilter(
            $crawler,
            1,
            "#modal-{$pokemonSlug} .modal-body .list-group-item",
            $groupItemIndex,
            'span',
        );

        $dexNumberPrefix = (('fr' === $lang) ? 'Numéro de dex régional' : 'Regional dex number');

        $this->assertEquals(
            "{$dexNumberPrefix} {$dexNumber}",
            $crawler->filter("#modal-{$pokemonSlug} .modal-body .list-group-item")
                ->eq($groupItemIndex)
                ->text()
        );
        $this->assertEquals(
            $dexNumberPrefix,
            $crawler->filter("#modal-{$pokemonSlug} .modal-body .list-group-item")
                ->eq($groupItemIndex)
                ->filter('strong')
                ->text()
        );
        $this->assertEquals(
            $dexNumber,
            $crawler->filter("#modal-{$pokemonSlug} .modal-body .list-group-item")
                ->eq($groupItemIndex)
                ->filter('span')
                ->text()
        );
    }

    public function assertModalItemFamilyLink(
        Crawler $crawler,
        string $pokemonSlug,
        string $lang,
        string $familyLeadSlug,
        bool $withRegionalItem,
    ): void {
        $groupIntemIndex = $withRegionalItem ? 5 : 4;

        $this->assertCountFilter(
            $crawler,
            1,
            "#modal-{$pokemonSlug} .modal-body .list-group-item",
            $groupIntemIndex,
            'a',
        );

        $this->assertEquals(
            'fr' === $lang ? 'Afficher la famille ' : 'Filter this family only',
            $crawler->filter("#modal-{$pokemonSlug} .modal-body .list-group-item")
                ->eq($groupIntemIndex)
                ->text()
        );
        $this->assertEquals(
            "/fr/album/demo?f={$familyLeadSlug}",
            $crawler->filter("#modal-{$pokemonSlug} .modal-body .list-group-item")
                ->eq($groupIntemIndex)
                ->filter('a')
                ->attr('href')
        );
        $this->assertNull(
            $crawler->filter("#modal-{$pokemonSlug} .modal-body .list-group-item")
                ->eq($groupIntemIndex)
                ->filter('a')
                ->attr('target')
        );
    }

    public function assertModalItemPokepediaLink(
        Crawler $crawler,
        string $pokemonSlug,
        string $lang,
        string $pokemonFrenchName,
        bool $withRegionalItem,
    ): void {
        $groupIntemIndex = $withRegionalItem ? 6 : 5;

        $this->assertCountFilter(
            $crawler,
            1,
            "#modal-{$pokemonSlug} .modal-body .list-group-item",
            $groupIntemIndex,
            'a',
        );

        $this->assertEquals(
            'fr' === $lang ? 'Fiche Poképédia' : "Poképédia's page (french)",
            $crawler->filter("#modal-{$pokemonSlug} .modal-body .list-group-item")
                ->eq($groupIntemIndex)
                ->text()
        );
        $this->assertEquals(
            "https://www.pokepedia.fr/{$pokemonFrenchName}",
            $crawler->filter("#modal-{$pokemonSlug} .modal-body .list-group-item")
                ->eq($groupIntemIndex)
                ->filter('a')
                ->attr('href')
        );
        $this->assertEquals(
            '_blank',
            $crawler->filter("#modal-{$pokemonSlug} .modal-body .list-group-item")
                ->eq($groupIntemIndex)
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
        $groupIntemIndex = $withRegionalItem ? 7 : 6;

        $this->assertCountFilter(
            $crawler,
            1,
            "#modal-{$pokemonSlug} .modal-body .list-group-item",
            $groupIntemIndex,
            'a',
        );

        $this->assertEquals(
            'fr' === $lang ? 'Fiche Bulbapedia (anglais)' : "Bulbapedia's page",
            $crawler->filter("#modal-{$pokemonSlug} .modal-body .list-group-item")
                ->eq($groupIntemIndex)
                ->text()
        );
        $this->assertEquals(
            "https://bulbapedia.bulbagarden.net/wiki/{$pokemonEnglishName}_(Pokémon)",
            $crawler->filter("#modal-{$pokemonSlug} .modal-body .list-group-item")
                ->eq($groupIntemIndex)
                ->filter('a')
                ->attr('href')
        );
        $this->assertEquals(
            '_blank',
            $crawler->filter("#modal-{$pokemonSlug} .modal-body .list-group-item")
                ->eq($groupIntemIndex)
                ->filter('a')
                ->attr('target')
        );
    }

    public function assertModalItemIcons(
        Crawler $crawler,
        string $pokemonSlug,
        string $lang,
    ): void {
        $this->assertCountFilter($crawler, 4, "#modal-{$pokemonSlug} a.album-modal-icon");
        $this->assertCountFilter(
            $crawler,
            1,
            "#modal-{$pokemonSlug} a.album-modal-icon.album-modal-icon-regular",
        );
        $this->assertCountFilter(
            $crawler,
            1,
            "#modal-{$pokemonSlug} a.album-modal-icon.album-modal-icon-shiny",
        );
        $this->assertCountFilter(
            $crawler,
            1,
            "#modal-{$pokemonSlug} a.album-modal-icon.album-modal-icon-previous",
        );
        $this->assertCountFilter(
            $crawler,
            1,
            "#modal-{$pokemonSlug} a.album-modal-icon.album-modal-icon-next",
        );
        $this->assertCountFilter($crawler, 4, "#modal-{$pokemonSlug} img.pokemon-icon");

        $this->assertEquals(
            'fr' === $lang ? 'Normal' : 'Regular',
            $crawler->filter("#modal-{$pokemonSlug} .album-modal-icon-regular")
                ->text()
        );
        $this->assertEquals(
            'fr' === $lang ? 'Chromatique' : 'Shiny',
            $crawler->filter("#modal-{$pokemonSlug} .album-modal-icon-shiny")
                ->text()
        );
        $this->assertEquals(
            'fr' === $lang ? 'Précédent' : 'Previous',
            $crawler->filter("#modal-{$pokemonSlug} .album-modal-icon-previous")
                ->text()
        );
        $this->assertEquals(
            'fr' === $lang ? 'Suivant' : 'Next',
            trim($crawler->filter("#modal-{$pokemonSlug} .album-modal-icon-next")
                ->text())
        );
    }
}
