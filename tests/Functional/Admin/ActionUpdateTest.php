<?php

declare(strict_types=1);

namespace App\Tests\Functional\Admin;

use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ActionUpdateTest extends WebTestCase
{
    use TestNavTrait;

    public function testAdminUpdateLabels(): void
    {
        $this->testAdminUpdate('labels');
    }

    public function testAdminUpdateGamesAndDex(): void
    {
        $this->testAdminUpdate('games_and_dex');
    }

    public function testAdminUpdatePokemons(): void
    {
        $this->testAdminUpdate('pokemons');
    }

    public function testAdminUpdateRegionalDexNumbers(): void
    {
        $this->testAdminUpdate('regional_dex_numbers');
    }

    public function testAdminUpdateGamesAvailabilities(): void
    {
        $this->testAdminUpdate('games_availabilities');
    }


    public function testAdminUpdateGamesShiniesAvailabilities(): void
    {
        $this->testAdminUpdate('games_shinies_availabilities');
    }

    public function testAdminUpdateUnknown(): void
    {
        $client = static::createClient();

        $user = new User('8764532');
        $user->addAdminRole();
        $client->loginUser($user);

        $client->catchExceptions(false);

        $this->expectException(NotFoundHttpException::class);

        $client->request('GET', "/fr/istration/action/update/truc");
    }

    public function testAdminNonAdmin(): void
    {
        $client = static::createClient();

        $user = new User('8764532');
        $client->loginUser($user);

        $client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);

        $client->request('GET', "/fr/istration/action/update/labels");
    }

    public function testAdminUpdateThenGoToIndex(): void
    {
        $client = static::createClient();

        $user = new User('8764532');
        $user->addAdminRole();
        $client->loginUser($user);

        $client->request('GET', "/fr/istration/action/update/labels");

        $this->assertResponseStatusCodeSame(302);
        $crawler = $client->followRedirect();

        $this->assertCountFilter($crawler, 1, '.list-group-item-success');
        $this->assertCountFilter($crawler, 0, '.list-group-item-warning');

        $crawler = $client->request('GET', "/fr/istration");

        $this->assertCountFilter($crawler, 0, '.list-group-item-success');
        $this->assertCountFilter($crawler, 0, '.list-group-item-warning');
    }

    private function testAdminUpdate(string $name): void
    {
        $client = static::createClient();

        $user = new User('8764532');
        $user->addAdminRole();
        $client->loginUser($user);

        $client->request('GET', "/fr/istration/action/update/$name");

        $this->assertResponseStatusCodeSame(302);
        $crawler = $client->followRedirect();

        $this->assertCountFilter($crawler, 1, '.list-group-item-success');

        $this->assertConnectedNavBar($crawler);
        $this->assertFrenchLangSwitch($crawler);

        $this->assertCountFilter($crawler, 0, 'script[src="/js/album.js"]');

        $this->assertStringNotContainsString('const catchStates = JSON.parse', $crawler->outerHtml());
        $this->assertStringNotContainsString('watchCatchStates();', $crawler->outerHtml());
    }
}
