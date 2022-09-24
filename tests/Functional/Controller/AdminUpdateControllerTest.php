<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class AdminUpdateControllerTest extends WebTestCase
{
    public function testAdminUpdateLabels(): void
    {
        $this->testAdminUpdate('labels');
    }

    public function testAdminUpdateGamesAndDexes(): void
    {
        $this->testAdminUpdate('games_and_dexes');
    }

    public function testAdminUpdateGameBundleAvailability(): void
    {
        $this->testAdminUpdate('game_bundle_availability');
    }

    public function testAdminUpdateDexAvailability(): void
    {
        $client = static::createClient();

        # For testing purpose, this case will fail in API side
        $crawler = $client->request('GET', "/istrateur/update/dex_availability", [], [], [
            'PHP_AUTH_USER' => 'renaud',
            'PHP_AUTH_PW'   => 'douze',
        ]);

        $this->assertResponseStatusCodeSame(200);

        $this->assertCount(1, $crawler->filter('.flash-danger'));
        $this->assertStringContainsString('La MAJ a échoué.', $crawler->filter('.flash-danger')->text());
    }

    public function testAdminUpdateUnknown(): void
    {
        $client = static::createClient();

        $client->request('GET', "/istrateur/update/truc", [], [], [
            'PHP_AUTH_USER' => 'renaud',
            'PHP_AUTH_PW'   => 'douze',
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    private function testAdminUpdate(string $name): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', "/istrateur/update/$name", [], [], [
            'PHP_AUTH_USER' => 'renaud',
            'PHP_AUTH_PW'   => 'douze',
        ]);

        $this->assertResponseStatusCodeSame(200);

        $this->assertCount(1, $crawler->filter('.flash-success'));
        $this->assertEquals('La MAJ a bien fonctionné', $crawler->filter('.flash-success')->text());
    }
}
