<?php

declare(strict_types=1);

namespace App\Tests\Functional\Album\Display;

use App\Controller\AlbumIndexController;
use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(AlbumIndexController::class)]
class ModalTest extends WebTestCase
{
    use TestNavTrait;
    use ModalTestTrait;

    public function testModals(): void
    {
        $client = static::createClient();

        $user = new User('12', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/home');

        $this->assertCountFilter($crawler, 6, '.modal');
    }

    public function testRegularModal(): void
    {
        $client = static::createClient();

        $user = new User('12', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/home');

        $this->assertCountFilter($crawler, 1, '#modal-bulbasaur');

        $this->assertModalTitle($crawler, 'bulbasaur', 'Bulbizarre', 'Bulbasaur');

        $this->assertModalImagesRegularAtFirst($crawler, 'bulbasaur');

        $this->assertModalItemIcons($crawler, 'bulbasaur', 'fr');

        $this->assertCountFilter($crawler, 7, '#modal-bulbasaur .modal-body .list-group-item');

        $this->assertModalItemNames($crawler, 'bulbasaur', 'Bulbizarre', 'Bulbasaur');

        $this->assertModalItemForms($crawler, 'bulbasaur', 'fr', 'Normale');

        $this->assertModalItemTypes($crawler, 'bulbasaur', 'Plante', 'Poison');

        $this->assertModalItemNationalDexNumber($crawler, 'bulbasaur', 'fr', 1);

        $this->assertModalItemPokepediaLink($crawler, 'bulbasaur', 'fr', 'Bulbizarre', false);
        $this->assertModalItemBulbapediaLink($crawler, 'bulbasaur', 'fr', 'Bulbasaur', false);
    }

    public function testRegularModalInEnglish(): void
    {
        $client = static::createClient();

        $user = new User('12', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/en/album/home');

        $this->assertCountFilter($crawler, 1, '#modal-bulbasaur');

        $this->assertModalTitle($crawler, 'bulbasaur', 'Bulbasaur', 'Bulbizarre');

        $this->assertModalImagesRegularAtFirst($crawler, 'bulbasaur');

        $this->assertModalItemIcons($crawler, 'bulbasaur', 'en');

        $this->assertCountFilter($crawler, 7, '#modal-bulbasaur .modal-body .list-group-item');

        $this->assertModalItemNames($crawler, 'bulbasaur', 'Bulbasaur', 'Bulbizarre');

        $this->assertModalItemForms($crawler, 'bulbasaur', 'en', 'Regular');

        $this->assertModalItemTypes($crawler, 'bulbasaur', 'Grass', 'Poison');

        $this->assertModalItemNationalDexNumber($crawler, 'bulbasaur', 'en', 1);

        $this->assertModalItemPokepediaLink($crawler, 'bulbasaur', 'en', 'Bulbizarre', false);
        $this->assertModalItemBulbapediaLink($crawler, 'bulbasaur', 'en', 'Bulbasaur', false);
    }

    public function testShinyModal(): void
    {
        $client = static::createClient();

        $user = new User('12', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/demoliteshiny');

        $this->assertCountFilter($crawler, 1, '#modal-bulbasaur');

        $this->assertModalTitle($crawler, 'bulbasaur', 'Bulbizarre', 'Bulbasaur');

        $this->assertModalImagesShinyAtFirst($crawler, 'bulbasaur');

        $this->assertModalItemIcons($crawler, 'bulbasaur', 'fr');

        $this->assertCountFilter($crawler, 7, '#modal-bulbasaur .modal-body .list-group-item');

        $this->assertModalItemNames($crawler, 'bulbasaur', 'Bulbizarre', 'Bulbasaur');

        $this->assertModalItemForms($crawler, 'bulbasaur', 'fr', 'Normale');

        $this->assertModalItemTypes($crawler, 'bulbasaur', 'Plante', 'Poison');

        $this->assertModalItemNationalDexNumber($crawler, 'bulbasaur', 'fr', 1);

        $this->assertModalItemPokepediaLink($crawler, 'bulbasaur', 'fr', 'Bulbizarre', false);
        $this->assertModalItemBulbapediaLink($crawler, 'bulbasaur', 'fr', 'Bulbasaur', false);
    }

    public function testRegionalWithFormsModal(): void
    {
        $client = static::createClient();

        $user = new User('12', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/goldsilvercrystal');

        $this->assertCountFilter($crawler, 1, '#modal-meganium ');

        $this->assertModalTitle($crawler, 'meganium', 'Méganium ♂️', 'Meganium ♂️');

        $this->assertCountFilter($crawler, 2, '#modal-meganium .modal-body .album-modal-image');

        $this->assertModalImagesRegularAtFirst($crawler, 'meganium');

        $this->assertModalItemIcons($crawler, 'meganium', 'fr');

        $this->assertCountFilter($crawler, 8, '#modal-meganium .modal-body .list-group-item');

        $this->assertModalItemNames($crawler, 'meganium', 'Méganium', 'Meganium');

        $this->assertModalItemForms($crawler, 'meganium', 'fr', '♂️');

        $this->assertModalItemTypes($crawler, 'meganium', 'Plante');

        $this->assertModalItemNationalDexNumber($crawler, 'meganium', 'fr', 154);
        $this->assertModalItemRegionalDexNumber($crawler, 'meganium', 'fr', 3);

        $this->assertModalItemPokepediaLink($crawler, 'meganium', 'fr', 'Méganium', true);
        $this->assertModalItemBulbapediaLink($crawler, 'meganium', 'fr', 'Meganium', true);
    }

    public function testWithFormsModal(): void
    {
        $client = static::createClient();

        $user = new User('12', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/home');

        $this->assertCountFilter($crawler, 1, '#modal-venusaur-mega');

        $this->assertModalTitle($crawler, 'venusaur-mega', 'Mega Florizarre', 'Mega Venusaur');

        $this->assertModalImagesRegularAtFirst($crawler, 'venusaur-mega');

        $this->assertModalItemIcons($crawler, 'venusaur-mega', 'fr');

        $this->assertCountFilter($crawler, 7, '#modal-venusaur-mega .modal-body .list-group-item');

        $this->assertModalItemNames($crawler, 'venusaur-mega', 'Florizarre', 'Venusaur');

        $this->assertModalItemForms($crawler, 'venusaur-mega', 'fr', 'mega');

        $this->assertModalItemTypes($crawler, 'venusaur-mega', 'Plante', 'Poison');

        $this->assertModalItemNationalDexNumber($crawler, 'venusaur-mega', 'fr', 3);

        $this->assertModalItemPokepediaLink($crawler, 'venusaur-mega', 'fr', 'Florizarre', false);
        $this->assertModalItemBulbapediaLink($crawler, 'venusaur-mega', 'fr', 'Venusaur', false);
    }
}
