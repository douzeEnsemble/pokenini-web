<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdminActionControllerTest extends WebTestCase
{
    use TestNavTrait;

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

    public function testAdminCalculateGamesBundlesAvailabilities(): void
    {
        $this->testAdminCalculate(
            'game_bundles_availabilities',
            [
                'Dispo des bundles' => '18',
            ]
        );
    }

    public function testAdminCalculateDexAvailabilities(): void
    {
        $client = static::createClient();

        $user = new User('8764532');
        $user->addAdminRole();
        $client->loginUser($user);

        # For testing purpose, this case will fail in API side
        $client->request('GET', "/fr/istration/action/calculate/dex_availabilities");

        $this->assertResponseStatusCodeSame(302);
        $crawler = $client->followRedirect();

        $this->assertCount(1, $crawler->filter('.list-group-item-danger'));
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

    public function testAdminCalculateUnknown(): void
    {
        $client = static::createClient();

        $user = new User('8764532');
        $user->addAdminRole();
        $client->loginUser($user);

        $client->catchExceptions(false);

        $this->expectException(NotFoundHttpException::class);

        $client->request('GET', "/fr/istration/action/calculate/truc");
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

        $this->assertCount(1, $crawler->filter('.list-group-item-success'));

        $this->assertConnectedNavBar($crawler);
        $this->assertFrenchLangSwitch($crawler);

        $this->assertCount(0, $crawler->filter('script[src="/js/album_edit.js"]'));

        $this->assertStringNotContainsString('const catchStates = JSON.parse', $crawler->outerHtml());
        $this->assertStringNotContainsString('watchCatchStates();', $crawler->outerHtml());

        $this->assertReport($crawler, $expectedReport);
    }

    /**
     * @param array<string, string> $expectedReport
     */
    private function testAdminCalculate(string $name, array $expectedReport = []): void
    {
        $client = static::createClient();

        $user = new User('8764532');
        $user->addAdminRole();
        $client->loginUser($user);

        $client->request('GET', "/fr/istration/action/calculate/$name");

        $this->assertResponseStatusCodeSame(302);
        $crawler = $client->followRedirect();

        $this->assertCount(1, $crawler->filter('.list-group-item-success'));

        $this->assertConnectedNavBar($crawler);
        $this->assertFrenchLangSwitch($crawler);

        $this->assertCount(0, $crawler->filter('script[src="/js/album_edit.js"]'));

        $this->assertStringNotContainsString('const catchStates = JSON.parse', $crawler->outerHtml());
        $this->assertStringNotContainsString('watchCatchStates();', $crawler->outerHtml());

        $this->assertReport($crawler, $expectedReport);
    }

    /**
     * @param array<string, string> $expectedReport
     */
    private function assertReport(Crawler $crawler, array $expectedReport): void
    {
        if (empty($expectedReport)) {
            $this->assertCount(0, $crawler->filter('.admin-item-report'));

            return;
        }

        $this->assertCount(1, $crawler->filter('.admin-item-report'));

        $index = 0;
        foreach ($expectedReport as $label => $value) {
            $this->assertEquals(
                $label,
                $crawler->filter('.admin-item-report dt')->eq($index)->text()
            );
            $this->assertEquals(
                $value,
                $crawler->filter('.admin-item-report dd')->eq($index)->text()
            );

            $index++;
        }
    }
}
