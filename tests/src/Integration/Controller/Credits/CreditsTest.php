<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Credits;

use App\Controller\CreditsController;
use App\Tests\Common\Traits\TestNavTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[Group('api-mocked-testing')]
#[CoversClass(CreditsController::class)]
final class CreditsTest extends WebTestCase
{
    use TestNavTrait;

    public function testIndex(): void
    {
        $client = self::createClient();

        $crawler = $client->request('GET', '/fr/credits');

        $this->assertResponseIsSuccessful();

        $this->assertSame('Pokénini Crédits', $crawler->filter('title')->text());
        $this->assertSame('Crédits', $crawler->filter('h1')->text());

        $items = $crawler->filter('.list-group-item');
        $this->assertCount(4, $items);

        // Sorted by image count descending: PokéSprite (2 images) comes first,
        // see tests/resources/moco/Back/responses/credits.json.
        $first = $items->eq(0);
        $this->assertSame('PokéSprite', $first->filter('.credit-source-link')->text());
        $this->assertSame('https://github.com/msikma/pokesprite', $first->filter('.credit-source-link')->attr('href'));
        $this->assertStringContainsString('2', $first->filter('.credit-detail-toggle')->text());

        $detailItems = $first->filter('.credit-detail-list li');
        $this->assertCount(2, $detailItems);
        $this->assertStringContainsString('Bulbizarre', $detailItems->eq(0)->text());

        // The second image (Herbizarre, size "big") is shiny, see
        // tests/resources/moco/Back/responses/credits.json.
        $second = $detailItems->eq(1);
        $this->assertStringContainsString('Chromatique', $second->text());
        $this->assertStringContainsString(
            'https://icon.pokenini.fr/big/shiny/ivysaur.png',
            (string) $second->attr('data-bs-title'),
        );
    }
}
