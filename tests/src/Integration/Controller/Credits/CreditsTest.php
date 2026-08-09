<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Credits;

use App\Controller\CreditsController;
use App\Tests\Common\Traits\TestNavTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[Group('api-mocked-testing')]
#[CoversClass(CreditsController::class)]
final class CreditsTest extends WebTestCase
{
    use TestNavTrait;

    #[Test]
    public function index(): void
    {
        $client = self::createClient();

        $crawler = $client->request('GET', '/fr/credits');

        $this->assertResponseIsSuccessful();

        $this->assertSame('Pokénini Crédits', $crawler->filter('title')->text());
        $this->assertSame('Crédits', $crawler->filter('h1')->text());

        // Order and content come from tests/resources/moco/Back/responses/credits.json:
        // bulbasaur (4/4 slots credited), ivysaur (1/4), venusaur (0/4).
        // One tile per credited image, plus one "no credit" tile for venusaur: 4 + 1 + 1 = 6.
        $tiles = $crawler->filter('.credit-tile');
        $this->assertCount(6, $tiles);

        $bulbaSmallRegular = $tiles->eq(0);
        $this->assertStringContainsString('Bulbizarre', $bulbaSmallRegular->text());
        $this->assertStringContainsString(
            'petit sprite, Normal',
            $bulbaSmallRegular->filter('.credit-tile-type')->text(),
        );
        $this->assertStringContainsString(
            'PokéSprite',
            $bulbaSmallRegular->filter('.credit-tile-credit')->text(),
        );
        $this->assertStringContainsString(
            '/pokemon/small/regular/bulbasaur.png',
            (string) $bulbaSmallRegular->filter('img.credit-tile-image')->attr('src'),
        );

        $bulbaSmallShiny = $tiles->eq(1);
        $this->assertStringContainsString(
            'petit sprite, Chromatique',
            $bulbaSmallShiny->filter('.credit-tile-type')->text(),
        );
        $this->assertStringContainsString(
            'PokéSprite',
            $bulbaSmallShiny->filter('.credit-tile-credit')->text(),
        );
        $this->assertStringContainsString(
            '/pokemon/small/shiny/bulbasaur.png',
            (string) $bulbaSmallShiny->filter('img.credit-tile-image')->attr('src'),
        );

        $bulbaBigRegular = $tiles->eq(2);
        $this->assertStringContainsString(
            'grand sprite, Normal',
            $bulbaBigRegular->filter('.credit-tile-type')->text(),
        );
        // Distinct source from the small-slot tiles above - guards against
        // a slot mix-up (e.g. the big-regular tile silently reusing the
        // small-regular credit).
        $this->assertStringContainsString(
            'PokemonDB',
            $bulbaBigRegular->filter('.credit-tile-credit')->text(),
        );
        $this->assertStringContainsString(
            '/pokemon/big/regular/bulbasaur.png',
            (string) $bulbaBigRegular->filter('img.credit-tile-image')->attr('src'),
        );

        $bulbaBigShiny = $tiles->eq(3);
        $this->assertStringContainsString(
            'grand sprite, Chromatique',
            $bulbaBigShiny->filter('.credit-tile-type')->text(),
        );
        $this->assertStringContainsString(
            'Bulbapedia',
            $bulbaBigShiny->filter('.credit-tile-credit')->text(),
        );
        $this->assertStringContainsString(
            '/pokemon/big/shiny/bulbasaur.png',
            (string) $bulbaBigShiny->filter('img.credit-tile-image')->attr('src'),
        );

        $ivysaurTile = $tiles->eq(4);
        $this->assertStringContainsString('Herbizarre', $ivysaurTile->text());
        $this->assertStringContainsString(
            'petit sprite, Normal',
            $ivysaurTile->filter('.credit-tile-type')->text(),
        );
        $this->assertStringContainsString(
            'Serebii',
            $ivysaurTile->filter('.credit-tile-credit')->text(),
        );

        $venusaurTile = $tiles->eq(5);
        $this->assertStringContainsString('Florizarre', $venusaurTile->text());
        $this->assertCount(0, $venusaurTile->filter('.credit-tile-type'));
        $this->assertStringContainsString(
            'Aucun crédit',
            $venusaurTile->filter('.credit-tile-credit')->text(),
        );
    }

    #[Test]
    public function creditLinkPointsToTheSourceUrl(): void
    {
        $client = self::createClient();

        $crawler = $client->request('GET', '/fr/credits');

        $link = $crawler->filter('.credit-tile')->eq(0)->filter('.credit-tile-credit a');

        $this->assertSame('https://github.com/msikma/pokesprite', $link->attr('href'));
        $this->assertSame('_blank', $link->attr('target'));
    }
}
