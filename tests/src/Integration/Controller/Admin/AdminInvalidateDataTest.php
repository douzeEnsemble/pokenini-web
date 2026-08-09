<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Admin;

use App\Controller\AdminInvalidateDataController;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @internal
 */
#[CoversClass(AdminInvalidateDataController::class)]
#[Group('api-mocked-testing')]
final class AdminInvalidateDataTest extends WebTestCase
{
    use TestNavTrait;

    public function testInvalidateDataNotConnected(): void
    {
        $client = self::createClient();

        $client->request('GET', '/fr/istration/invalidate_data');

        $this->assertResponseStatusCodeSame(307);
    }

    public function testInvalidateDataNotAllowed(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $client->loginUser($user, 'web');

        $client->request('GET', '/fr/istration/invalidate_data');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testInvalidateData(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/istration/invalidate_data');

        $this->assertResponseStatusCodeSame(200);

        $this->assertSame(
            'Pokénini Administration',
            $crawler->filter('title')->text()
        );

        $this->assertSame(
            'Administration',
            $crawler->filter('h1')->text()
        );

        $this->assertConnectedNavBar($crawler);
        $this->assertFrenchLangSwitch($crawler);

        $this->assertCountFilter($crawler, 5, '.admin-item-description');
        $this->assertCountFilter($crawler, 5, '.admin-item button.admin-item-cta');
        $this->assertCountFilter($crawler, 0, '.admin-item-cta.disabled');
        $this->assertCountFilter($crawler, 0, '.admin-item-cta[data-confirm-message]');
        $this->assertCountFilter($crawler, 0, '.admin-item-refresh');

        foreach ($this->getExpectedDescriptions() as $itemId => $description) {
            $this->assertSame(
                $description,
                $crawler->filter("#{$itemId} .admin-item-description")->text()
            );

            $this->assertNoReport($crawler, $itemId, 'current');
        }

        $this->assertCountFilter($crawler, 0, 'script[src="/js/album.js"]');

        $this->assertStringNotContainsString('const catchStates = JSON.parse', $crawler->outerHtml());
        $this->assertStringNotContainsString('watchCatchStates();', $crawler->outerHtml());
        $this->assertStringNotContainsString('const types = JSON.parse', $crawler->outerHtml());
    }

    /**
     * @return array<string, string>
     */
    private function getExpectedDescriptions(): array
    {
        return [
            'invalidate_labels' => "Vide le cache des labels pour qu'ils soient rechargés au prochain accès.",
            'invalidate_catch_states' => "Vide le cache des statuts pour qu'ils soient rechargés au prochain accès.",
            'invalidate_types' => "Vide le cache des types pour qu'ils soient rechargés au prochain accès.",
            'invalidate_dex' => "Vide le cache des pages de dex pour qu'elles soient rechargées au prochain accès.",
            'invalidate_albums' => "Vide le cache des pages d'album pour qu'elles soient rechargées au prochain accès.",
        ];
    }

    private function assertNoReport(
        Crawler $crawler,
        string $item,
        string $type,
    ): void {
        $this->assertCountFilter(
            $crawler,
            0,
            ".admin-item-{$item} .admin-item-{$type}"
        );
    }
}
