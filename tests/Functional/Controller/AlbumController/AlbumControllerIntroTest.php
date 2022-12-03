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
            6,
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

        $this->assertListGroupItemWithValue($crawler, 2, 'National');
        $this->assertListGroupItemWithValue($crawler, 3, 'Non');
        $this->assertListGroupItemWithValue($crawler, 4, 'Boîtes');
        $this->assertListGroupItemWithValue($crawler, 5, '4');
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
            6,
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

        $this->assertListGroupItemWithValue($crawler, 2, 'National');
        $this->assertListGroupItemWithValue($crawler, 3, 'Non');
        $this->assertListGroupItemWithValue($crawler, 4, 'Liste de 3 par lignes');
        $this->assertListGroupItemWithValue($crawler, 5, '412');
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
            6,
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

        $this->assertListGroupItemWithValue($crawler, 2, 'National');
        $this->assertListGroupItemWithValue($crawler, 3, 'Oui');
        $this->assertListGroupItemWithValue($crawler, 4, 'Boîtes');
        $this->assertListGroupItemWithValue($crawler, 5, '0');
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
            6,
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

        $this->assertListGroupItemWithValue($crawler, 2, 'Johto');
        $this->assertListGroupItemWithValue($crawler, 3, 'Non');
        $this->assertListGroupItemWithValue($crawler, 4, 'Boîtes');
        $this->assertListGroupItemWithValue($crawler, 5, '3');
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
            6,
            $crawler->filter('#intro .list-group .list-group-item')
        );

        $this->assertEquals(
            '#box-1',
            $crawler->filter('#intro .list-group .list-group-item')->eq(0)->attr('href')
        );

        $this->assertEquals(
            '#',
            $crawler->filter('#intro .list-group .list-group-item')->eq(1)->attr('href')
        );
        $this->assertEquals(
            "Album d'un autre dresseur",
            $crawler->filter('#intro .list-group .list-group-item')->eq(1)->text()
        );

        $this->assertListGroupItemWithValue($crawler, 2, 'National');
        $this->assertListGroupItemWithValue($crawler, 3, 'Non');
        $this->assertListGroupItemWithValue($crawler, 4, 'Boîtes');
        $this->assertListGroupItemWithValue($crawler, 5, '412');
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
