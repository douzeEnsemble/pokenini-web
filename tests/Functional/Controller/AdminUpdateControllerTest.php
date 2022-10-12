<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

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

        # For testing purpose, this case will fail in API side
        $client->request('GET', "/fr/istrateur/update/dex_availability", [], [], [
            'PHP_AUTH_USER' => 'renaud',
            'PHP_AUTH_PW'   => 'douze',
        ]);

        $this->assertResponseStatusCodeSame(302);
        $crawler = $client->followRedirect();

        $this->assertCount(1, $crawler->filter('.flash-danger'));
    }

    public function testAdminUpdateUnknown(): void
    {
        $client = static::createClient();
        $client->catchExceptions(false);

        $this->expectException(NotFoundHttpException::class);

        $client->request('GET', "/fr/istrateur/update/truc", [], [], [
            'PHP_AUTH_USER' => 'renaud',
            'PHP_AUTH_PW'   => 'douze',
        ]);
    }

    private function testAdminUpdate(string $name): void
    {
        $client = static::createClient();

        $client->request('GET', "/fr/istrateur/update/$name", [], [], [
            'PHP_AUTH_USER' => 'renaud',
            'PHP_AUTH_PW'   => 'douze',
        ]);

        $this->assertResponseStatusCodeSame(302);
        $crawler = $client->followRedirect();

        $this->assertCount(1, $crawler->filter('.flash-success'));

        $this->assertConnectedNavBar($crawler);
        $this->assertFrenchLangSwitch($crawler);
    }
}
