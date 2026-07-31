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

        // Order and content come from tests/resources/moco/Back/responses/credits.json:
        // bulbasaur (4/4 slots credited), ivysaur (1/4), venusaur (0/4).
        $items = $crawler->filter('.list-group-item');
        $this->assertCount(3, $items);

        $bulbasaur = $items->eq(0);
        $this->assertStringContainsString('Bulbizarre', $bulbasaur->text());
        $this->assertStringContainsString('4', $bulbasaur->filter('.credit-detail-toggle')->text());
        $this->assertCount(4, $bulbasaur->filter('.credit-detail-list li'));

        $ivysaur = $items->eq(1);
        $this->assertStringContainsString('Herbizarre', $ivysaur->text());
        $this->assertStringContainsString('1', $ivysaur->filter('.credit-detail-toggle')->text());
        $this->assertCount(1, $ivysaur->filter('.credit-detail-list li'));

        $venusaur = $items->eq(2);
        $this->assertStringContainsString('Florizarre', $venusaur->text());
        $this->assertCount(0, $venusaur->filter('.credit-detail-toggle'));
        $this->assertStringContainsString('Aucun crédit', $venusaur->text());
    }
}
