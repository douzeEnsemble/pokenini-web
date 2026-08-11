<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Trainer;

use App\Controller\TrainerLinksController;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(TrainerLinksController::class)]
#[Group('api-mocked-testing')]
final class TrainerLinksPageTest extends WebTestCase
{
    #[Test]
    public function linksPage(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/trainer/links');

        $this->assertResponseStatusCodeSame(200);

        $this->assertSame(
            'Pokénini Ton espace de dresseur',
            $crawler->filter('title')->text()
        );

        $this->assertSame(
            'Ton espace de dresseur',
            $crawler->filter('h1')->text()
        );

        $this->assertCount(1, $crawler->filter('h1'));

        $mainText = $crawler->filter('#main-container')->text();

        $this->assertStringContainsString(
            'Voici tous les liens que tu as créés entre tes dex.',
            $mainText
        );

        // The Moco /album_link fixture returns the same 2 demo links (a "both" link to
        // goldsilvercrystal and a "to" link to rubysapphireemerald) for every dex slug
        // queried. GetTrainerDexLinksTreeService fans that call out to every dex in the
        // trainer's own dex list (21 dexes in the fixture) and deduplicates by unordered
        // (from, to) pair, so the tree is not empty: it ends up with 41 edges (21 "both",
        // 20 "to" — the goldsilvercrystal<->rubysapphireemerald pair is reported from both
        // sides and collapses to a single "both" edge).
        $this->assertCount(1, $crawler->filter('.dex-links-tree'));
        $this->assertCount(41, $crawler->filter('.dex-links-edge'));
        $this->assertCount(21, $crawler->filter('.dex-links-arrow .bi-arrow-left-right'));
        $this->assertCount(20, $crawler->filter('.dex-links-arrow .bi-arrow-right'));

        $firstEdgeText = $crawler->filter('.dex-links-edge')->first()->text();
        $this->assertStringContainsString('Rouge, Vert, Bleu, Jaune', $firstEdgeText);
        $this->assertStringContainsString('Or, Argent, Cristal', $firstEdgeText);
    }

    #[Test]
    public function linksPageNotAllowed(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $client->loginUser($user, 'web');

        $client->request('GET', '/fr/trainer/links');

        $this->assertResponseStatusCodeSame(403);
    }
}
