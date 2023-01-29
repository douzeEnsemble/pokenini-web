<?php

declare(strict_types=1);

namespace App\Tests\Functional\Admin;

use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ActionUpdateTest extends WebTestCase
{
    use TestNavTrait;
    use ReportsAssertionTrait;

    public function testAdminUpdateLabels(): void
    {
        $this->testAdminUpdate(
            'labels',
            [
                'Statuts' => '6',
                'Régions' => '0',
                'Catégories' => '4',
                'Formes régionales' => '4',
                'Formes spéciales' => '5',
                'Variantes' => '8',
            ]
        );
    }

    public function testAdminUpdateGamesAndDex(): void
    {
        $this->testAdminUpdate(
            'games_and_dex',
            [
                'Générations' => '9',
                'Bundles de jeux' => '17',
                'Jeux' => '36',
                'Dex' => '21',
            ]
        );
    }

    public function testAdminUpdatePokemons(): void
    {
        $this->testAdminUpdate(
            'pokemons',
            [
                'Pokémons' => '1 815',
            ]
        );
    }

    public function testAdminUpdateRegionalDexNumbers(): void
    {
        $this->testAdminUpdate(
            'regional_dex_numbers',
            [
                // Empty for testing purpose
            ]
        );
    }

    public function testAdminUpdateGamesAvailabilities(): void
    {
        $this->testAdminUpdate(
            'games_availabilities',
            [
                'Dispo des jeux' => '7 980',
            ]
        );
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

    /**
     * @param array<string, string> $expectedReport
     */
    private function testAdminUpdate(string $name, array $expectedReport = []): void
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

        $this->assertReport($crawler, $expectedReport);
    }
}
