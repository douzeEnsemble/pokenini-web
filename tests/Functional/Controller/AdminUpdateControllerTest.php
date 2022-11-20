<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdminUpdateControllerTest extends WebTestCase
{
    use TestNavTrait;

    public function testAdminUpdateLabels(): void
    {
        $this->testAdminUpdate('labels');
    }

    public function testAdminUpdateGamesAndDexes(): void
    {
        $this->testAdminUpdate('games_and_dexes');
    }

    public function testAdminUpdatePokemons(): void
    {
        $this->testAdminUpdate('pokemons');
    }

    public function testAdminUpdateGameBundleAvailability(): void
    {
        $this->testAdminUpdate('game_bundle_availability');
    }

    public function testAdminUpdateDexAvailability(): void
    {
        $client = static::createClient();

        $user = new User('8764532');
        $user->addAdminRole();
        $client->loginUser($user);

        # For testing purpose, this case will fail in API side
        $client->request('GET', "/fr/istration/update/dex_availability");

        $this->assertResponseStatusCodeSame(302);
        $crawler = $client->followRedirect();

        $this->assertCount(1, $crawler->filter('.flash-danger'));
    }

    public function testAdminUpdateUnknown(): void
    {
        $client = static::createClient();

        $user = new User('8764532');
        $user->addAdminRole();
        $client->loginUser($user);

        $client->catchExceptions(false);

        $this->expectException(NotFoundHttpException::class);

        $client->request('GET', "/fr/istration/update/truc");
    }

    private function testAdminUpdate(string $name): void
    {
        $client = static::createClient();

        $user = new User('8764532');
        $user->addAdminRole();
        $client->loginUser($user);

        $client->request('GET', "/fr/istration/update/$name");

        $this->assertResponseStatusCodeSame(302);
        $crawler = $client->followRedirect();

        $this->assertCount(1, $crawler->filter('.flash-success'));

        $this->assertConnectedNavBar($crawler);
        $this->assertFrenchLangSwitch($crawler);

        $this->assertCount(0, $crawler->filter('script[src="/js/album_edit.js"]'));

        $this->assertStringNotContainsString('const catchStates = JSON.parse', $crawler->outerHtml());
        $this->assertStringNotContainsString('watchCatchStates();', $crawler->outerHtml());
    }
}
