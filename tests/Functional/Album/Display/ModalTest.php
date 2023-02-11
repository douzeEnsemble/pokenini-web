<?php

declare(strict_types=1);

namespace App\Tests\Functional\Album\Display;

use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class ModalTest extends WebTestCase
{
    use TestNavTrait;
    use ModalTestTrait;

    public function testModals(): void
    {
        $client = static::createClient();

        $user = new User('12');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/home');

        $this->assertCountFilter($crawler, 1740, '.modal');
    }

    public function testRegularModal(): void
    {
        $client = static::createClient();

        $user = new User('12');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/home');

        $this->assertCountFilter($crawler, 1, '#modal-charmander');

        $this->assertModalTitle($crawler, 'charmander', 'Salamèche', 'Charmander');

        $this->assertModalImages($crawler, 'charmander');

        $this->assertCountFilter($crawler, 6, '#modal-charmander .modal-body .list-group-item');

        $this->assertModalItemNames($crawler, 'charmander', 'Salamèche', 'Charmander');

        $this->assertModalItemForms($crawler, 'charmander', 'fr', 'Normale');

        $this->assertModalItemNationalDexNumber($crawler, 'charmander', 'fr', 4);

        $this->assertModalItemPokepediaLink($crawler, 'charmander', 'fr', 'Salamèche', false);
        $this->assertModalItemBulbapediaLink($crawler, 'charmander', 'fr', 'Charmander', false);

        $this->assertModalItemIcons($crawler, 'charmander', 'fr', false);
    }

    public function testRegularModalInEnglish(): void
    {
        $client = static::createClient();

        $user = new User('12');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/en/album/home');

        $this->assertCountFilter($crawler, 1, '#modal-charmander');

        $this->assertModalTitle($crawler, 'charmander', 'Charmander', 'Salamèche');

        $this->assertModalImages($crawler, 'charmander');

        $this->assertCountFilter($crawler, 6, '#modal-charmander .modal-body .list-group-item');

        $this->assertModalItemNames($crawler, 'charmander', 'Charmander', 'Salamèche');

        $this->assertModalItemForms($crawler, 'charmander', 'en', 'Regular');

        $this->assertModalItemNationalDexNumber($crawler, 'charmander', 'en', 4);

        $this->assertModalItemPokepediaLink($crawler, 'charmander', 'en', 'Salamèche', false);
        $this->assertModalItemBulbapediaLink($crawler, 'charmander', 'en', 'Charmander', false);

        $this->assertModalItemIcons($crawler, 'charmander', 'en', false);
    }

    public function testRegionalWithFormsModal(): void
    {
        $client = static::createClient();

        $user = new User('12');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/goldsilvercrystal');

        $this->assertCountFilter($crawler, 1, '#modal-meganium ');

        $this->assertModalTitle($crawler, 'meganium', 'Méganium ♂️', 'Meganium ♂️');

        $this->assertCountFilter($crawler, 2, '#modal-meganium .modal-body .album-modal-image');

        $this->assertModalImages($crawler, 'meganium');

        $this->assertCountFilter($crawler, 7, '#modal-meganium .modal-body .list-group-item');

        $this->assertModalItemNames($crawler, 'meganium', 'Méganium', 'Meganium');

        $this->assertModalItemForms($crawler, 'meganium', 'fr', '♂️');

        $this->assertModalItemNationalDexNumber($crawler, 'meganium', 'fr', 154);
        $this->assertModalItemRegionalDexNumber($crawler, 'meganium', 'fr', 3);

        $this->assertModalItemPokepediaLink($crawler, 'meganium', 'fr', 'Méganium', true);
        $this->assertModalItemBulbapediaLink($crawler, 'meganium', 'fr', 'Meganium', true);

        $this->assertModalItemIcons($crawler, 'meganium', 'fr', true);
    }

    public function testWithFormsModal(): void
    {
        $client = static::createClient();

        $user = new User('12');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/home');

        $this->assertCountFilter($crawler, 1, '#modal-charizard-mega-y');

        $this->assertModalTitle($crawler, 'charizard-mega-y', 'Mega Dracaufeu Y', 'Mega Charizard Y');

        $this->assertModalImages($crawler, 'charizard-mega-y');

        $this->assertCountFilter($crawler, 6, '#modal-charizard-mega-y .modal-body .list-group-item');

        $this->assertModalItemNames($crawler, 'charizard-mega-y', 'Dracaufeu', 'Charizard');

        $this->assertModalItemForms($crawler, 'charizard-mega-y', 'fr', 'Mega Y');

        $this->assertModalItemNationalDexNumber($crawler, 'charizard-mega-y', 'fr', 6);

        $this->assertModalItemPokepediaLink($crawler, 'charizard-mega-y', 'fr', 'Dracaufeu', false);
        $this->assertModalItemBulbapediaLink($crawler, 'charizard-mega-y', 'fr', 'Charizard', false);

        $this->assertModalItemIcons($crawler, 'charizard-mega-y', 'fr', false);
    }
}
