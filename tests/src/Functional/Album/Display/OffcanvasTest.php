<?php

declare(strict_types=1);

namespace App\Tests\Functional\Album\Display;

use App\Controller\AlbumIndexController;
use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @internal
 */
#[CoversClass(AlbumIndexController::class)]
class OffcanvasTest extends WebTestCase
{
    use TestNavTrait;

    public function testOffcanvasHome(): void
    {
        $client = static::createClient();

        $user = new User('12', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/home');

        $this->assertCountFilter($crawler, 1, '#offcanvas #album-description');
        $this->assertStringContainsString(
            'Tous les pokémons pouvant être transférés sur Pokémon Home.',
            $crawler->filter('#album-description')->text()
        );
        $this->assertStringContainsString(
            'Incluant les mâles/femelles, les formes différentes et les transformations',
            $crawler->filter('#album-description')->text()
        );
        $this->assertCountFilter($crawler, 1, '#offcanvas .album-private');
        $this->assertEquals(
            'Album privé',
            $crawler->filter('#offcanvas .album-private')->attr('title')
        );
        $this->assertCountFilter($crawler, 0, '#offcanvas .album-another-trainer');

        $this->assertEquals(
            'Dex National',
            $crawler->filter('#offcanvas .dex-type')->attr('title')
        );

        $this->assertEquals(
            'Formes normales',
            $crawler->filter('#offcanvas .dex-shiny-or-not')->attr('title')
        );

        $this->assertEquals(
            'Affichage par boîte de 6 par 5 pokémons comme dans les jeux',
            $crawler->filter('#offcanvas .dex-template')->text()
        );

        $this->assertEquals(
            'Version 4',
            $crawler->filter('#offcanvas .dex-version')->text()
        );

        $this->assertFilters($crawler, 'fr');
        $this->assertResetLink($crawler, '/fr/album/home');
    }

    public function testIntroDemoList3(): void
    {
        $client = static::createClient();

        $user = new User('12', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/demolist3');

        $this->assertCountFilter($crawler, 1, '#offcanvas #album-description');
        $this->assertEquals(
            'Tous les pokémons de la démo affiché en liste, 3 éléments par colonnes',
            $crawler->filter('#album-description')->text()
        );

        $this->assertCountFilter($crawler, 1, '#offcanvas .album-private');
        $this->assertEquals(
            'Album privé',
            $crawler->filter('#offcanvas .album-private')->attr('title')
        );
        $this->assertCountFilter($crawler, 0, '#offcanvas .album-another-trainer');

        $this->assertEquals(
            'Dex National',
            $crawler->filter('#offcanvas .dex-type')->attr('title')
        );

        $this->assertEquals(
            'Formes normales',
            $crawler->filter('#offcanvas .dex-shiny-or-not')->attr('title')
        );

        $this->assertEquals(
            'Liste de 3 pokémons par lignes',
            $crawler->filter('#offcanvas .dex-template')->text()
        );

        $this->assertEquals(
            'Version 412',
            $crawler->filter('#offcanvas .dex-version')->text()
        );

        $this->assertFilters($crawler, 'fr');
        $this->assertResetLink($crawler, '/fr/album/demolist3');
    }

    public function testIntroDemoLiteShiny(): void
    {
        $client = static::createClient();

        $user = new User('12', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/demoliteshiny');

        $this->assertCountFilter($crawler, 1, '#offcanvas #album-description');
        $this->assertEquals(
            '',
            $crawler->filter('#album-description')->text()
        );

        $this->assertCountFilter($crawler, 0, '#offcanvas .album-private');

        $this->assertEquals(
            'Dex National',
            $crawler->filter('#offcanvas .dex-type')->attr('title')
        );

        $this->assertEquals(
            'Formes chromatiques',
            $crawler->filter('#offcanvas .dex-shiny-or-not')->attr('title')
        );

        $this->assertEquals(
            'Affichage par boîte de 6 par 5 pokémons comme dans les jeux',
            $crawler->filter('#offcanvas .dex-template')->text()
        );

        $this->assertEquals(
            'Version 0',
            $crawler->filter('#offcanvas .dex-version')->text()
        );

        $this->assertFilters($crawler, 'fr');
        $this->assertResetLink($crawler, '/fr/album/demoliteshiny');
    }

    public function testIntroGoldSilverCrystal(): void
    {
        $client = static::createClient();

        $user = new User('12', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/goldsilvercrystal');

        $this->assertCountFilter($crawler, 1, '#offcanvas #album-description');
        $this->assertStringContainsString(
            'La liste des pokémons obtenable dans les jeux Or, Argent et Cristal.',
            $crawler->filter('#album-description')->text()
        );
        $this->assertStringContainsString(
            "Seul les Zarbi ont des formes différentes, seulement les 26 lettres de l'alphabet.",
            $crawler->filter('#album-description')->text()
        );

        $this->assertCountFilter($crawler, 1, '#offcanvas .album-private');
        $this->assertEquals(
            'Album privé',
            $crawler->filter('#offcanvas .album-private')->attr('title')
        );
        $this->assertCountFilter($crawler, 0, '#offcanvas .album-another-trainer');

        $this->assertEquals(
            'Région Johto',
            $crawler->filter('#offcanvas .dex-type')->attr('title')
        );

        $this->assertEquals(
            'Formes normales',
            $crawler->filter('#offcanvas .dex-shiny-or-not')->attr('title')
        );

        $this->assertEquals(
            'Affichage par boîte de 6 par 5 pokémons comme dans les jeux',
            $crawler->filter('#offcanvas .dex-template')->text()
        );

        $this->assertEquals(
            'Version 3',
            $crawler->filter('#offcanvas .dex-version')->text()
        );

        $this->assertFilters($crawler, 'fr');
        $this->assertResetLink($crawler, '/fr/album/goldsilvercrystal');
    }

    public function testIntroBlackWhiteFrench(): void
    {
        $client = static::createClient();

        $user = new User('12', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/blackwhite');

        $this->assertCountFilter($crawler, 1, '#offcanvas #album-description');
        $this->assertStringContainsString(
            'La liste des pokémons obtenable dans les jeux Noire et Blanche.',
            $crawler->filter('#album-description')->text()
        );
        $this->assertStringContainsString(
            'Les pokémons ont des formes différentes en fonction du genre ou pas.',
            $crawler->filter('#album-description')->text()
        );

        $this->assertCountFilter($crawler, 1, '#offcanvas .album-private');
        $this->assertEquals(
            'Album privé',
            $crawler->filter('#offcanvas .album-private')->attr('title')
        );
        $this->assertCountFilter($crawler, 0, '#offcanvas .album-another-trainer');

        $this->assertEquals(
            'Région Unys',
            $crawler->filter('#offcanvas .dex-type')->attr('title')
        );

        $this->assertEquals(
            'Formes normales',
            $crawler->filter('#offcanvas .dex-shiny-or-not')->attr('title')
        );

        $this->assertEquals(
            'Affichage par boîte de 6 par 5 pokémons comme dans les jeux',
            $crawler->filter('#offcanvas .dex-template')->text()
        );

        $this->assertEquals(
            'Version 2',
            $crawler->filter('#offcanvas .dex-version')->text()
        );

        $this->assertFilters($crawler, 'fr');
        $this->assertResetLink($crawler, '/fr/album/blackwhite');
    }

    public function testIntroBlackWhiteEnglish(): void
    {
        $client = static::createClient();

        $user = new User('12', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/en/album/blackwhite');

        $this->assertCountFilter($crawler, 1, '#offcanvas #album-description');
        $this->assertStringContainsString(
            'The list of obtainable Pokémons in Black and White games.',
            $crawler->filter('#album-description')->text()
        );
        $this->assertStringContainsString(
            'Pokémons have different shapes depending on the gender or not.',
            $crawler->filter('#album-description')->text()
        );

        $this->assertCountFilter($crawler, 1, '#offcanvas .album-private');
        $this->assertEquals(
            'Private album',
            $crawler->filter('#offcanvas .album-private')->attr('title')
        );
        $this->assertCountFilter($crawler, 0, '#offcanvas .album-another-trainer');

        $this->assertEquals(
            'Region Unova',
            $crawler->filter('#offcanvas .dex-type')->attr('title')
        );

        $this->assertEquals(
            'Regular forms',
            $crawler->filter('#offcanvas .dex-shiny-or-not')->attr('title')
        );

        $this->assertEquals(
            'Display by box of 6 by 5 pokémons as in the games',
            $crawler->filter('#offcanvas .dex-template')->text()
        );

        $this->assertEquals(
            'Version 2',
            $crawler->filter('#offcanvas .dex-version')->text()
        );

        $this->assertFilters($crawler, 'en');
        $this->assertResetLink($crawler, '/en/album/blackwhite');
    }

    public function testIntroDemoAnotherTrainer(): void
    {
        $client = static::createClient();

        $user = new User('13', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/demo?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertCountFilter($crawler, 1, '#offcanvas #album-description');
        $this->assertEquals(
            'Tous les pokémons de la démo',
            $crawler->filter('#album-description')->text()
        );

        $this->assertCountFilter($crawler, 0, '#offcanvas .album-private');
        $this->assertCountFilter($crawler, 1, '#offcanvas .album-another-trainer');

        $this->assertEquals(
            'Dex National',
            $crawler->filter('#offcanvas .dex-type')->attr('title')
        );

        $this->assertEquals(
            'Formes normales',
            $crawler->filter('#offcanvas .dex-shiny-or-not')->attr('title')
        );

        $this->assertEquals(
            'Affichage par boîte de 6 par 5 pokémons comme dans les jeux',
            $crawler->filter('#offcanvas .dex-template')->text()
        );

        $this->assertEquals(
            'Version 412',
            $crawler->filter('#offcanvas .dex-version')->text()
        );

        $this->assertFilters($crawler, 'fr');
        $this->assertResetLink($crawler, '/fr/album/demo?t=7b52009b64fd0a2a49e6d8a939753077792b0554');
    }

    private function assertFiltersPrimaryType(Crawler $crawler, string $lang): void
    {
        $this->assertCount(1, $crawler->filter('select#primary_type'));
        $this->assertCount(19, $crawler->filter('select#primary_type option'));
        $this->assertEquals(
            'fr' === $lang ? 'Tous' : 'All',
            $crawler->filter('select#primary_type option')->eq(0)->text()
        );
        $this->assertEquals(
            'fr' === $lang ? 'Normal' : 'Normal',
            $crawler->filter('select#primary_type option')->eq(1)->text()
        );
    }

    private function assertFiltersSecondaryType(Crawler $crawler, string $lang): void
    {
        $this->assertCount(1, $crawler->filter('select#secondary_type'));
        $this->assertCount(20, $crawler->filter('select#secondary_type option'));
        $this->assertEquals(
            'fr' === $lang ? 'Tous' : 'All',
            $crawler->filter('select#secondary_type option')->eq(0)->text()
        );
        $this->assertEquals(
            'fr' === $lang ? 'Aucun' : 'None',
            $crawler->filter('select#secondary_type option')->eq(1)->text()
        );
        $this->assertEquals(
            'fr' === $lang ? 'Normal' : 'Normal',
            $crawler->filter('select#secondary_type option')->eq(2)->text()
        );
    }

    private function assertFiltersCategoryForm(Crawler $crawler, string $lang): void
    {
        $this->assertCount(1, $crawler->filter('select#category_form'));
        $this->assertCount(8, $crawler->filter('select#category_form option'));
        $this->assertEquals(
            'fr' === $lang ? 'Toutes' : 'All',
            $crawler->filter('select#category_form option')->eq(0)->text()
        );
        $this->assertEquals(
            'fr' === $lang ? 'Aucune' : 'None',
            $crawler->filter('select#category_form option')->eq(1)->text()
        );
        $this->assertEquals(
            'fr' === $lang ? 'de Départ' : 'Starter',
            $crawler->filter('select#category_form option')->eq(2)->text()
        );
    }

    private function assertFiltersRegionalForm(Crawler $crawler, string $lang): void
    {
        $this->assertCount(1, $crawler->filter('select#regional_form'));
        $this->assertCount(6, $crawler->filter('select#regional_form option'));
        $this->assertEquals(
            'fr' === $lang ? 'Toutes' : 'All',
            $crawler->filter('select#regional_form option')->eq(0)->text()
        );
        $this->assertEquals(
            'fr' === $lang ? 'Aucune' : 'None',
            $crawler->filter('select#regional_form option')->eq(1)->text()
        );
        $this->assertEquals(
            'fr' === $lang ? "d'Alola" : 'Alolan',
            $crawler->filter('select#regional_form option')->eq(2)->text()
        );
    }

    private function assertFiltersSpecialForm(Crawler $crawler, string $lang): void
    {
        $this->assertCount(1, $crawler->filter('select#special_form'));
        $this->assertCount(9, $crawler->filter('select#special_form option'));
        $this->assertEquals(
            'fr' === $lang ? 'Toutes' : 'All',
            $crawler->filter('select#special_form option')->eq(0)->text()
        );
        $this->assertEquals(
            'fr' === $lang ? 'Aucune' : 'None',
            $crawler->filter('select#special_form option')->eq(1)->text()
        );
        $this->assertEquals(
            'fr' === $lang ? 'Mega' : 'Mega',
            $crawler->filter('select#special_form option')->eq(2)->text()
        );
    }

    private function assertFiltersVariantForm(Crawler $crawler, string $lang): void
    {
        $this->assertCount(1, $crawler->filter('select#variant_form'));
        $this->assertCount(9, $crawler->filter('select#variant_form option'));
        $this->assertEquals(
            'fr' === $lang ? 'Toutes' : 'All',
            $crawler->filter('select#variant_form option')->eq(0)->text()
        );
        $this->assertEquals(
            'fr' === $lang ? 'Aucune' : 'None',
            $crawler->filter('select#variant_form option')->eq(1)->text()
        );
        $this->assertEquals(
            'fr' === $lang ? 'Genre' : 'Gender',
            $crawler->filter('select#variant_form option')->eq(2)->text()
        );
    }

    private function assertResetLink(Crawler $crawler, string $link): void
    {
        $this->assertEquals(
            $link,
            $crawler->filter('#offcanvas form a.form-filter-reset')->attr('href')
        );
    }

    private function assertFilters(Crawler $crawler, string $lang): void
    {
        $this->assertFiltersPrimaryType($crawler, $lang);
        $this->assertFiltersSecondaryType($crawler, $lang);
        $this->assertFiltersCategoryForm($crawler, $lang);
        $this->assertFiltersRegionalForm($crawler, $lang);
        $this->assertFiltersSpecialForm($crawler, $lang);
        $this->assertFiltersVariantForm($crawler, $lang);

        $this->assertCount(1, $crawler->filter('#offcanvas form button[type="submit"]'));
        $this->assertCount(1, $crawler->filter('#offcanvas form a.form-filter-reset'));
    }
}
