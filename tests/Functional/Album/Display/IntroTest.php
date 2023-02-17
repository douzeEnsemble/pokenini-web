<?php

declare(strict_types=1);

namespace App\Tests\Functional\Album\Display;

use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Common\Traits\TestSetUp;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class IntroTest extends WebTestCase
{
    use TestNavTrait;
    use TestSetUp;

    public function testIntroHome(): void
    {
        $client = static::createClient();

        $user = new User('12');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/home');

        $this->assertCountFilter($crawler, 1, 'h1#album-title');
        $this->assertEquals(
            'Home',
            $crawler->filter('h1#album-title')->text()
        );

        $this->assertCountFilter($crawler, 1, '#intro .list-group');
        $this->assertCountFilter($crawler, 9, '#intro .list-group .list-group-item');
        $this->assertCountFilter($crawler, 4, '#intro .list-group .list-group-item[hidden]');

        $index = 0;
        $this->assertEquals(
            '#',
            $crawler->filter('#intro .list-group .list-group-item')->eq($index++)->attr('href')
        );
        $this->assertCountFilter($crawler, 1, '#album-description');
        $this->assertStringContainsString(
            'Tous les pokémons pouvant être transférés sur Pokémon Home.',
            $crawler->filter('#album-description')->text()
        );
        $this->assertStringContainsString(
            'Incluant les mâles/femelles, les formes différentes et les transformations',
            $crawler->filter('#album-description')->text()
        );

        $this->assertEquals(
            '#',
            $crawler->filter('#intro .list-group .list-group-item')->eq($index++)->attr('href')
        );

        $this->assertEquals(
            '#',
            $crawler->filter('#intro .list-group .list-group-item')->eq($index++)->attr('href')
        );

        $this->assertEquals(
            '#box-1',
            $crawler->filter('#intro .list-group .list-group-item')->eq($index++)->attr('href')
        );

        $this->assertEquals(
            'Album privé',
            $crawler->filter('#intro .list-group .list-group-item')->eq($index++)->text()
        );

        $this->assertListGroupItemWithValue($crawler, $index++, 'National');

        $this->assertEquals(
            'Formes normales',
            $crawler->filter('#intro .list-group .list-group-item')->eq($index++)->text()
        );

        $this->assertEquals(
            'Affichage par boîte de 6 par 5 pokémons comme dans les jeux',
            $crawler->filter('#intro .list-group .list-group-item')->eq($index++)->text()
        );

        $this->assertListGroupItemWithValue($crawler, $index++, '4');
    }

    public function testIntroDemoList3(): void
    {
        $client = static::createClient();

        $user = new User('12');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/demolist3');

        $this->assertCountFilter($crawler, 1, 'h1#album-title');
        $this->assertEquals(
            'Démo',
            $crawler->filter('h1#album-title')->text()
        );

        $this->assertCountFilter($crawler, 1, '#intro .list-group');
        $this->assertCountFilter($crawler, 9, '#intro .list-group .list-group-item');
        $this->assertCountFilter($crawler, 4, '#intro .list-group .list-group-item[hidden]');

        $index = 0;
        $this->assertEquals(
            '#',
            $crawler->filter('#intro .list-group .list-group-item')->eq($index++)->attr('href')
        );

        $this->assertCountFilter($crawler, 1, '#album-description');
        $this->assertEquals(
            'Tous les pokémons de la démo affiché en liste, 3 éléments par colonnes',
            $crawler->filter('#album-description')->text()
        );

        $this->assertEquals(
            '#',
            $crawler->filter('#intro .list-group .list-group-item')->eq($index++)->attr('href')
        );

        $this->assertEquals(
            '#',
            $crawler->filter('#intro .list-group .list-group-item')->eq($index++)->attr('href')
        );

        $this->assertEquals(
            '#top-of-the-list',
            $crawler->filter('#intro .list-group .list-group-item')->eq($index++)->attr('href')
        );

        $this->assertEquals(
            'Album privé',
            $crawler->filter('#intro .list-group .list-group-item')->eq($index++)->text()
        );

        $this->assertListGroupItemWithValue($crawler, $index++, 'National');

        $this->assertEquals(
            'Formes normales',
            $crawler->filter('#intro .list-group .list-group-item')->eq($index++)->text()
        );

        $this->assertEquals(
            'Liste de 3 pokémons par lignes',
            $crawler->filter('#intro .list-group .list-group-item')->eq($index++)->text()
        );

        $this->assertListGroupItemWithValue($crawler, $index++, '412');
    }

    public function testIntroDemoLiteShiny(): void
    {
        $client = static::createClient();

        $user = new User('12');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/demoliteshiny');

        $this->assertCountFilter($crawler, 1, 'h1#album-title');
        $this->assertEquals(
            'Démo, extrait, chromatique',
            $crawler->filter('h1#album-title')->text()
        );

        $this->assertCountFilter($crawler, 1, '#intro .list-group');
        $this->assertCountFilter($crawler, 9, '#intro .list-group .list-group-item');
        $this->assertCountFilter($crawler, 4, '#intro .list-group .list-group-item[hidden]');

        $index = 0;
        $this->assertEquals(
            '#',
            $crawler->filter('#intro .list-group .list-group-item')->eq($index++)->attr('href')
        );

        $this->assertCountFilter($crawler, 1, '#album-description');
        $this->assertEquals(
            '',
            $crawler->filter('#album-description')->text()
        );

        $this->assertEquals(
            '#',
            $crawler->filter('#intro .list-group .list-group-item')->eq($index++)->attr('href')
        );

        $this->assertEquals(
            '#',
            $crawler->filter('#intro .list-group .list-group-item')->eq($index++)->attr('href')
        );

        $this->assertEquals(
            '#box-1',
            $crawler->filter('#intro .list-group .list-group-item')->eq($index++)->attr('href')
        );

        $this->assertEquals(
            '/fr/album/demoliteshiny?t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $crawler->filter('#intro .list-group .list-group-item')->eq($index++)->attr('href')
        );

        $this->assertListGroupItemWithValue($crawler, $index++, 'National');

        $this->assertEquals(
            'Formes chromatiques',
            $crawler->filter('#intro .list-group .list-group-item')->eq($index++)->text()
        );

        $this->assertEquals(
            'Affichage par boîte de 6 par 5 pokémons comme dans les jeux',
            $crawler->filter('#intro .list-group .list-group-item')->eq($index++)->text()
        );

        $this->assertListGroupItemWithValue($crawler, $index++, '0');
    }

    public function testIntroGoldSilverCrystal(): void
    {
        $client = static::createClient();

        $user = new User('12');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/goldsilvercrystal');

        $this->assertCountFilter($crawler, 1, 'h1#album-title');
        $this->assertEquals(
            'Or, Argent, Cristal',
            $crawler->filter('h1#album-title')->text()
        );

        $this->assertCountFilter($crawler, 1, '#intro .list-group');
        $this->assertCountFilter($crawler, 9, '#intro .list-group .list-group-item');
        $this->assertCountFilter($crawler, 4, '#intro .list-group .list-group-item[hidden]');

        $index = 0;
        $this->assertEquals(
            '#',
            $crawler->filter('#intro .list-group .list-group-item')->eq($index++)->attr('href')
        );

        $this->assertCountFilter($crawler, 1, '#album-description');
        $this->assertStringContainsString(
            'La liste des pokémons obtenable dans les jeux Or, Argent et Cristal.',
            $crawler->filter('#album-description')->text()
        );
        $this->assertStringContainsString(
            "Seul les Zarbi ont des formes différentes, seulement les 26 lettres de l'alphabet.",
            $crawler->filter('#album-description')->text()
        );

        $this->assertEquals(
            '#',
            $crawler->filter('#intro .list-group .list-group-item')->eq($index++)->attr('href')
        );

        $this->assertEquals(
            '#',
            $crawler->filter('#intro .list-group .list-group-item')->eq($index++)->attr('href')
        );

        $this->assertEquals(
            '#box-1',
            $crawler->filter('#intro .list-group .list-group-item')->eq($index++)->attr('href')
        );

        $this->assertEquals(
            'Album privé',
            $crawler->filter('#intro .list-group .list-group-item')->eq($index++)->text()
        );

        $this->assertListGroupItemWithValue($crawler, $index++, 'Johto');

        $this->assertEquals(
            'Formes normales',
            $crawler->filter('#intro .list-group .list-group-item')->eq($index++)->text()
        );

        $this->assertEquals(
            'Affichage par boîte de 6 par 5 pokémons comme dans les jeux',
            $crawler->filter('#intro .list-group .list-group-item')->eq($index++)->text()
        );

        $this->assertListGroupItemWithValue($crawler, $index++, '3');
    }

    public function testIntroBlackWhiteFrench(): void
    {
        $client = static::createClient();

        $user = new User('12');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/blackwhite');

        $this->assertCountFilter($crawler, 1, 'h1#album-title');
        $this->assertEquals(
            'Noire, Blanche',
            $crawler->filter('h1#album-title')->text()
        );

        $this->assertCountFilter($crawler, 1, '#intro .list-group');
        $this->assertCountFilter($crawler, 9, '#intro .list-group .list-group-item');
        $this->assertCountFilter($crawler, 4, '#intro .list-group .list-group-item[hidden]');

        $index = 0;
        $this->assertEquals(
            '#',
            $crawler->filter('#intro .list-group .list-group-item')->eq($index++)->attr('href')
        );

        $this->assertCountFilter($crawler, 1, '#album-description');
        $this->assertStringContainsString(
            'La liste des pokémons obtenable dans les jeux Noire et Blanche.',
            $crawler->filter('#album-description')->text()
        );
        $this->assertStringContainsString(
            "Les pokémons ont des formes différentes en fonction du genre ou pas.",
            $crawler->filter('#album-description')->text()
        );

        $this->assertEquals(
            '#',
            $crawler->filter('#intro .list-group .list-group-item')->eq($index++)->attr('href')
        );

        $this->assertEquals(
            '#',
            $crawler->filter('#intro .list-group .list-group-item')->eq($index++)->attr('href')
        );

        $this->assertEquals(
            '#box-1',
            $crawler->filter('#intro .list-group .list-group-item')->eq($index++)->attr('href')
        );

        $this->assertEquals(
            'Album privé',
            $crawler->filter('#intro .list-group .list-group-item')->eq($index++)->text()
        );

        $this->assertListGroupItemWithValue($crawler, $index++, 'Unys');

        $this->assertEquals(
            'Formes normales',
            $crawler->filter('#intro .list-group .list-group-item')->eq($index++)->text()
        );

        $this->assertEquals(
            'Affichage par boîte de 6 par 5 pokémons comme dans les jeux',
            $crawler->filter('#intro .list-group .list-group-item')->eq($index++)->text()
        );

        $this->assertListGroupItemWithValue($crawler, $index++, '2');
    }

    public function testIntroBlackWhiteEnglish(): void
    {
        $client = static::createClient();

        $user = new User('12');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/en/album/blackwhite');

        $this->assertCountFilter($crawler, 1, 'h1#album-title');
        $this->assertEquals(
            'Black, White',
            $crawler->filter('h1#album-title')->text()
        );

        $this->assertCountFilter($crawler, 1, '#intro .list-group');
        $this->assertCountFilter($crawler, 9, '#intro .list-group .list-group-item');
        $this->assertCountFilter($crawler, 4, '#intro .list-group .list-group-item[hidden]');

        $index = 0;
        $this->assertEquals(
            '#',
            $crawler->filter('#intro .list-group .list-group-item')->eq($index++)->attr('href')
        );

        $this->assertCountFilter($crawler, 1, '#album-description');
        $this->assertStringContainsString(
            'The list of obtainable Pokémons in Black and White games.',
            $crawler->filter('#album-description')->text()
        );
        $this->assertStringContainsString(
            "Pokémons have different shapes depending on the gender or not.",
            $crawler->filter('#album-description')->text()
        );

        $this->assertEquals(
            '#',
            $crawler->filter('#intro .list-group .list-group-item')->eq($index++)->attr('href')
        );

        $this->assertEquals(
            '#',
            $crawler->filter('#intro .list-group .list-group-item')->eq($index++)->attr('href')
        );

        $this->assertEquals(
            '#box-1',
            $crawler->filter('#intro .list-group .list-group-item')->eq($index++)->attr('href')
        );

        $this->assertEquals(
            'Private album',
            $crawler->filter('#intro .list-group .list-group-item')->eq($index++)->text()
        );

        $this->assertListGroupItemWithValue($crawler, $index++, 'Unova');

        $this->assertEquals(
            'Regular forms',
            $crawler->filter('#intro .list-group .list-group-item')->eq($index++)->text()
        );

        $this->assertEquals(
            'Display by box of 6 by 5 pokémons as in the games',
            $crawler->filter('#intro .list-group .list-group-item')->eq($index++)->text()
        );

        $this->assertListGroupItemWithValue($crawler, $index++, '2');
    }

    public function testIntroDemoAnotherTrainer(): void
    {
        $client = static::createClient();

        $user = new User('13');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/demo?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertCountFilter($crawler, 1, 'h1#album-title');
        $this->assertEquals(
            'Démo',
            $crawler->filter('h1#album-title')->text()
        );

        $this->assertCountFilter($crawler, 1, '#intro .list-group');
        $this->assertCountFilter($crawler, 8, '#intro .list-group .list-group-item');
        $this->assertCountFilter($crawler, 3, '#intro .list-group .list-group-item[hidden]');

        $index = 0;
        $this->assertEquals(
            '#',
            $crawler->filter('#intro .list-group .list-group-item')->eq($index++)->attr('href')
        );

        $this->assertCountFilter($crawler, 1, '#album-description');
        $this->assertEquals(
            'Tous les pokémons de la démo',
            $crawler->filter('#album-description')->text()
        );

        $this->assertEquals(
            '#box-1',
            $crawler->filter('#intro .list-group .list-group-item')->eq($index++)->attr('href')
        );

        $this->assertEquals(
            '/fr/album/demo?t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $crawler->filter('#intro .list-group .list-group-item')->eq($index++)->attr('href')
        );

        $this->assertEquals(
            '#',
            $crawler->filter('#intro .list-group .list-group-item')->eq($index)->attr('href')
        );
        $this->assertEquals(
            "Album d'un autre dresseur",
            $crawler->filter('#intro .list-group .list-group-item')->eq($index++)->text()
        );

        $this->assertListGroupItemWithValue($crawler, $index++, 'National');

        $this->assertEquals(
            'Formes normales',
            $crawler->filter('#intro .list-group .list-group-item')->eq($index++)->text()
        );

        $this->assertEquals(
            'Affichage par boîte de 6 par 5 pokémons comme dans les jeux',
            $crawler->filter('#intro .list-group .list-group-item')->eq($index++)->text()
        );

        $this->assertListGroupItemWithValue($crawler, $index++, '412');
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
