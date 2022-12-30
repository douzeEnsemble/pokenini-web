<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\AlbumController;

use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class AlbumControllerIntroTest extends WebTestCase
{
    use TestNavTrait;

    public function testIntroHome(): void
    {
        $client = static::createClient();

        $user = new User('12');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/r/home');

        $this->assertCount(
            1,
            $crawler->filter('h1#album-title')
        );
        $this->assertEquals(
            'Home',
            $crawler->filter('h1#album-title')->text()
        );

        $this->assertCount(
            1,
            $crawler->filter('#album-description')
        );
        $this->assertStringContainsString(
            'Tous les pokémons pouvant être transférés sur Pokémon Home.',
            $crawler->filter('#album-description')->text()
        );
        $this->assertStringContainsString(
            'Incluant les mâles/femelles, les formes différentes et les transformations',
            $crawler->filter('#album-description')->text()
        );

        $this->assertCount(
            1,
            $crawler->filter('#intro .list-group')
        );
        $this->assertCount(
            7,
            $crawler->filter('#intro .list-group .list-group-item')
        );

        $this->assertEquals(
            '/fr/album/w/home',
            $crawler->filter('#intro .list-group .list-group-item')->first()->attr('href')
        );

        $this->assertEquals(
            '#box-1',
            $crawler->filter('#intro .list-group .list-group-item')->eq(1)->attr('href')
        );

        $this->assertEquals(
            'Album privé',
            $crawler->filter('#intro .list-group .list-group-item')->eq(2)->text()
        );

        $this->assertListGroupItemWithValue($crawler, 3, 'National');

        $this->assertEquals(
            'Formes normales',
            $crawler->filter('#intro .list-group .list-group-item')->eq(4)->text()
        );

        $this->assertEquals(
            'Affichage par boîte de 6 par 5 pokémons comme dans les jeux',
            $crawler->filter('#intro .list-group .list-group-item')->eq(5)->text()
        );

        $this->assertListGroupItemWithValue($crawler, 6, '4');
    }

    public function testIntroDemoList3(): void
    {
        $client = static::createClient();

        $user = new User('12');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/r/demolist3');

        $this->assertCount(
            1,
            $crawler->filter('h1#album-title')
        );
        $this->assertEquals(
            'Démo',
            $crawler->filter('h1#album-title')->text()
        );

        $this->assertCount(
            1,
            $crawler->filter('#album-description')
        );
        $this->assertEquals(
            'Tous les pokémons de la démo affiché en liste, 3 éléments par colonnes',
            $crawler->filter('#album-description')->text()
        );

        $this->assertCount(
            1,
            $crawler->filter('#intro .list-group')
        );
        $this->assertCount(
            7,
            $crawler->filter('#intro .list-group .list-group-item')
        );

        $this->assertEquals(
            '/fr/album/w/demolist3',
            $crawler->filter('#intro .list-group .list-group-item')->first()->attr('href')
        );

        $this->assertEquals(
            '#top-of-the-list',
            $crawler->filter('#intro .list-group .list-group-item')->eq(1)->attr('href')
        );

        $this->assertEquals(
            'Album privé',
            $crawler->filter('#intro .list-group .list-group-item')->eq(2)->text()
        );

        $this->assertListGroupItemWithValue($crawler, 3, 'National');

        $this->assertEquals(
            'Formes normales',
            $crawler->filter('#intro .list-group .list-group-item')->eq(4)->text()
        );

        $this->assertEquals(
            'Liste de 3 pokémons par lignes',
            $crawler->filter('#intro .list-group .list-group-item')->eq(5)->text()
        );

        $this->assertListGroupItemWithValue($crawler, 6, '412');
    }

    public function testIntroDemoLiteShiny(): void
    {
        $client = static::createClient();

        $user = new User('12');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/r/demoliteshiny');

        $this->assertCount(
            1,
            $crawler->filter('h1#album-title')
        );
        $this->assertEquals(
            'Démo, extrait, chromatique',
            $crawler->filter('h1#album-title')->text()
        );

        $this->assertCount(
            1,
            $crawler->filter('#album-description')
        );
        $this->assertEquals(
            '',
            $crawler->filter('#album-description')->text()
        );

        $this->assertCount(
            1,
            $crawler->filter('#intro .list-group')
        );
        $this->assertCount(
            7,
            $crawler->filter('#intro .list-group .list-group-item')
        );

        $this->assertEquals(
            '/fr/album/w/demoliteshiny',
            $crawler->filter('#intro .list-group .list-group-item')->first()->attr('href')
        );

        $this->assertEquals(
            '#box-1',
            $crawler->filter('#intro .list-group .list-group-item')->eq(1)->attr('href')
        );

        $this->assertEquals(
            '/fr/album/r/demoliteshiny?t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $crawler->filter('#intro .list-group .list-group-item')->eq(2)->attr('href')
        );

        $this->assertListGroupItemWithValue($crawler, 3, 'National');

        $this->assertEquals(
            'Formes chromatiques',
            $crawler->filter('#intro .list-group .list-group-item')->eq(4)->text()
        );

        $this->assertEquals(
            'Affichage par boîte de 6 par 5 pokémons comme dans les jeux',
            $crawler->filter('#intro .list-group .list-group-item')->eq(5)->text()
        );

        $this->assertListGroupItemWithValue($crawler, 6, '0');
    }

    public function testIntroGoldSilverCrystal(): void
    {
        $client = static::createClient();

        $user = new User('12');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/r/goldsilvercrystal');

        $this->assertCount(
            1,
            $crawler->filter('h1#album-title')
        );
        $this->assertEquals(
            'Or, Argent, Cristal',
            $crawler->filter('h1#album-title')->text()
        );

        $this->assertCount(
            1,
            $crawler->filter('#album-description')
        );
        $this->assertStringContainsString(
            'La liste des pokémons obtenable dans les jeux Or, Argent et Cristal.',
            $crawler->filter('#album-description')->text()
        );
        $this->assertStringContainsString(
            "Seul les Zarbi ont des formes différentes, seulement les 26 lettres de l'alphabet.",
            $crawler->filter('#album-description')->text()
        );

        $this->assertCount(
            1,
            $crawler->filter('#intro .list-group')
        );
        $this->assertCount(
            7,
            $crawler->filter('#intro .list-group .list-group-item')
        );

        $this->assertEquals(
            '/fr/album/w/goldsilvercrystal',
            $crawler->filter('#intro .list-group .list-group-item')->first()->attr('href')
        );

        $this->assertEquals(
            '#box-1',
            $crawler->filter('#intro .list-group .list-group-item')->eq(1)->attr('href')
        );

        $this->assertEquals(
            'Album privé',
            $crawler->filter('#intro .list-group .list-group-item')->eq(2)->text()
        );

        $this->assertListGroupItemWithValue($crawler, 3, 'Johto');

        $this->assertEquals(
            'Formes normales',
            $crawler->filter('#intro .list-group .list-group-item')->eq(4)->text()
        );

        $this->assertEquals(
            'Affichage par boîte de 6 par 5 pokémons comme dans les jeux',
            $crawler->filter('#intro .list-group .list-group-item')->eq(5)->text()
        );

        $this->assertListGroupItemWithValue($crawler, 6, '3');
    }

    public function testIntroBlackWhiteFrench(): void
    {
        $client = static::createClient();

        $user = new User('12');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/r/blackwhite');

        file_put_contents('tests/last.html', $crawler->html());

        $this->assertCount(
            1,
            $crawler->filter('h1#album-title')
        );
        $this->assertEquals(
            'Noire, Blanche',
            $crawler->filter('h1#album-title')->text()
        );

        $this->assertCount(
            1,
            $crawler->filter('#album-description')
        );
        $this->assertStringContainsString(
            'La liste des pokémons obtenable dans les jeux Noire et Blanche.',
            $crawler->filter('#album-description')->text()
        );
        $this->assertStringContainsString(
            "Les pokémons ont des formes différentes en fonction du genre ou pas.",
            $crawler->filter('#album-description')->text()
        );

        $this->assertCount(
            1,
            $crawler->filter('#intro .list-group')
        );
        $this->assertCount(
            7,
            $crawler->filter('#intro .list-group .list-group-item')
        );

        $this->assertEquals(
            '/fr/album/w/blackwhite',
            $crawler->filter('#intro .list-group .list-group-item')->first()->attr('href')
        );

        $this->assertEquals(
            '#box-1',
            $crawler->filter('#intro .list-group .list-group-item')->eq(1)->attr('href')
        );

        $this->assertEquals(
            'Album privé',
            $crawler->filter('#intro .list-group .list-group-item')->eq(2)->text()
        );

        $this->assertListGroupItemWithValue($crawler, 3, 'Unys');

        $this->assertEquals(
            'Formes normales',
            $crawler->filter('#intro .list-group .list-group-item')->eq(4)->text()
        );

        $this->assertEquals(
            'Affichage par boîte de 6 par 5 pokémons comme dans les jeux',
            $crawler->filter('#intro .list-group .list-group-item')->eq(5)->text()
        );

        $this->assertListGroupItemWithValue($crawler, 6, '2');
    }

    public function testIntroBlackWhiteEnglish(): void
    {
        $client = static::createClient();

        $user = new User('12');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/en/album/r/blackwhite');

        file_put_contents('tests/last.html', $crawler->html());

        $this->assertCount(
            1,
            $crawler->filter('h1#album-title')
        );
        $this->assertEquals(
            'Black, White',
            $crawler->filter('h1#album-title')->text()
        );

        $this->assertCount(
            1,
            $crawler->filter('#album-description')
        );
        $this->assertStringContainsString(
            'The list of obtainable Pokémons in Black and White games.',
            $crawler->filter('#album-description')->text()
        );
        $this->assertStringContainsString(
            "Pokémons have different shapes depending on the gender or not.",
            $crawler->filter('#album-description')->text()
        );

        $this->assertCount(
            1,
            $crawler->filter('#intro .list-group')
        );
        $this->assertCount(
            7,
            $crawler->filter('#intro .list-group .list-group-item')
        );

        $this->assertEquals(
            '/en/album/w/blackwhite',
            $crawler->filter('#intro .list-group .list-group-item')->first()->attr('href')
        );

        $this->assertEquals(
            '#box-1',
            $crawler->filter('#intro .list-group .list-group-item')->eq(1)->attr('href')
        );

        $this->assertEquals(
            'Private album',
            $crawler->filter('#intro .list-group .list-group-item')->eq(2)->text()
        );

        $this->assertListGroupItemWithValue($crawler, 3, 'Unova');

        $this->assertEquals(
            'Regular forms',
            $crawler->filter('#intro .list-group .list-group-item')->eq(4)->text()
        );

        $this->assertEquals(
            'Display by box of 6 by 5 pokémons as in the games',
            $crawler->filter('#intro .list-group .list-group-item')->eq(5)->text()
        );

        $this->assertListGroupItemWithValue($crawler, 6, '2');
    }

    public function testIntroDemoAnotherTrainer(): void
    {
        $client = static::createClient();

        $user = new User('13');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/r/demo?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertCount(
            1,
            $crawler->filter('h1#album-title')
        );
        $this->assertEquals(
            'Démo',
            $crawler->filter('h1#album-title')->text()
        );

        $this->assertCount(
            1,
            $crawler->filter('#album-description')
        );
        $this->assertEquals(
            'Tous les pokémons de la démo',
            $crawler->filter('#album-description')->text()
        );

        $this->assertCount(
            1,
            $crawler->filter('#intro .list-group')
        );
        $this->assertCount(
            7,
            $crawler->filter('#intro .list-group .list-group-item')
        );

        $this->assertEquals(
            '#box-1',
            $crawler->filter('#intro .list-group .list-group-item')->eq(0)->attr('href')
        );

        $this->assertEquals(
            '/fr/album/r/demo?t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $crawler->filter('#intro .list-group .list-group-item')->eq(1)->attr('href')
        );

        $this->assertEquals(
            '#',
            $crawler->filter('#intro .list-group .list-group-item')->eq(2)->attr('href')
        );
        $this->assertEquals(
            "Album d'un autre dresseur",
            $crawler->filter('#intro .list-group .list-group-item')->eq(2)->text()
        );

        $this->assertListGroupItemWithValue($crawler, 3, 'National');

        $this->assertEquals(
            'Formes normales',
            $crawler->filter('#intro .list-group .list-group-item')->eq(4)->text()
        );

        $this->assertEquals(
            'Affichage par boîte de 6 par 5 pokémons comme dans les jeux',
            $crawler->filter('#intro .list-group .list-group-item')->eq(5)->text()
        );

        $this->assertListGroupItemWithValue($crawler, 6, '412');
    }

    private function assertListGroupItemWithValue(Crawler $crawler, int $index, string $expectedValue): void
    {
        $this->assertEquals(
            '#',
            $crawler->filter('#intro .list-group .list-group-item')->eq($index)->attr('href')
        );
        $this->assertEquals(
            $expectedValue,
            $crawler
                ->filter('#intro .list-group .list-group-item')->eq($index)
                ->filter('.badge')->text()
        );
    }
}
