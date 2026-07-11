<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Trainer;

use App\Controller\TrainerIndexController;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @internal
 */
#[CoversClass(TrainerIndexController::class)]
#[Group('api-mocked-testing')]
final class TrainerPageTest extends WebTestCase
{
    use TestNavTrait;

    public function testTrainerPage(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/trainer');

        $this->assertResponseStatusCodeSame(200);

        $this->assertSame(
            'Pokénini Ton espace de dresseur',
            $crawler->filter('title')->text()
        );

        $this->assertSame(
            'Ton espace de dresseur',
            $crawler->filter('h1')->text()
        );

        $this->assertCountFilter($crawler, 1, 'h1');
        $this->assertCountFilter($crawler, 2, 'table thead th');
        $this->assertCountFilter($crawler, 2, 'table tbody tr');
        $this->assertEquals('Identifiant 789465465489', $crawler->filter('table tbody tr')->eq(0)->text());
        $this->assertEquals("Service d'identification TestProvider", $crawler->filter('table tbody tr')->eq(1)->text());

        $this->assertCustomizeAlbumSection($crawler, false, false, 5);

        $this->assertStringContainsString(
            '/connect/logout',
            $crawler->filter('.accordion-item')->last()->filter('a')->attr('href') ?? ''
        );

        $this->assertCount(0, $crawler->filter('.navbar-link'));

        $this->assertCountFilter($crawler, 1, '.dex_is_shiny');
        $this->assertCountFilter($crawler, 2, '.dex_is_premium');
        $this->assertCountFilter($crawler, 0, '.dex_not_is_released');
        $this->assertCountFilter($crawler, 1, '.dex_is_custom');
    }

    public function testCollectorPage(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $user->addCollectorRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/trainer');

        $this->assertResponseStatusCodeSame(200);

        $this->assertSame(
            'Pokénini Ton espace de dresseur',
            $crawler->filter('title')->text()
        );

        $this->assertSame(
            'Ton espace de dresseur',
            $crawler->filter('h1')->text()
        );

        $this->assertCountFilter($crawler, 1, 'h1');
        $this->assertCountFilter($crawler, 2, 'table thead th');
        $this->assertCountFilter($crawler, 2, 'table tbody tr');
        $this->assertEquals('Identifiant 789465465489', $crawler->filter('table tbody tr')->eq(0)->text());
        $this->assertEquals("Service d'identification TestProvider", $crawler->filter('table tbody tr')->eq(1)->text());

        $this->assertCustomizeAlbumSection($crawler, false, true, 5);

        $this->assertStringContainsString(
            '/connect/logout',
            $crawler->filter('.accordion-item')->last()->filter('a')->attr('href') ?? ''
        );

        $this->assertCount(0, $crawler->filter('.navbar-link'));

        $this->assertCountFilter($crawler, 1, '.dex_is_shiny');
        $this->assertCountFilter($crawler, 2, '.dex_is_premium');
        $this->assertCountFilter($crawler, 0, '.dex_not_is_released');
        $this->assertCountFilter($crawler, 1, '.dex_is_custom');
    }

    public function testAdminTrainerPage(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestAdminProvider');
        $user->addTrainerRole();
        $user->addCollectorRole();
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/trainer');

        $this->assertResponseStatusCodeSame(200);

        $this->assertSame(
            'Pokénini Ton espace de dresseur',
            $crawler->filter('title')->text()
        );

        $this->assertSame(
            'Ton espace de dresseur',
            $crawler->filter('h1')->text()
        );

        $this->assertCountFilter($crawler, 1, 'h1');
        $this->assertCountFilter($crawler, 2, 'table thead th');
        $this->assertCountFilter($crawler, 2, 'table tbody tr');
        $this->assertEquals('Identifiant 8764532', $crawler->filter('table tbody tr')->eq(0)->text());
        $this->assertEquals("Service d'identification TestAdminProvider", $crawler->filter('table tbody tr')->eq(1)->text());

        $this->assertCustomizeAlbumSection($crawler, true, true, 0);

        $this->assertStringContainsString(
            '/connect/logout',
            $crawler->filter('.accordion-item')->last()->filter('a')->attr('href') ?? ''
        );

        $this->assertCount(0, $crawler->filter('.navbar-link'));

        $this->assertCountFilter($crawler, 2, '.dex_is_shiny');
        $this->assertCountFilter($crawler, 3, '.dex_is_premium');
        $this->assertCountFilter($crawler, 2, '.dex_not_is_released');
        $this->assertCountFilter($crawler, 0, '.dex_is_custom');
    }

    public function testTrainerPageNotAllowed(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $client->loginUser($user, 'web');

        $client->request('GET', '/fr/trainer');

        $this->assertResponseStatusCodeSame(403);
    }

    private function assertCustomizeAlbumSection(Crawler $crawler, bool $isAdmin, bool $isCollector, int $reportCount): void
    {
        $this->assertCountFilter($crawler, 1, 'form#dexFilters');

        $this->assertCountFilter($crawler, 1, 'form#dexFilters', 0, '#filter-privacy');
        $this->assertCountFilter($crawler, 3, 'form#dexFilters #filter-privacy', 0, 'option');
        $this->assertSelectedOptions($crawler, 'select#filter-privacy', ['']);

        $this->assertCountFilter($crawler, 1, 'form#dexFilters', 0, '#filter-homepaged');
        $this->assertCountFilter($crawler, 3, 'form#dexFilters #filter-homepaged', 0, 'option');
        $this->assertSelectedOptions($crawler, 'select#filter-homepaged', ['']);

        $this->assertCountFilter($crawler, $isAdmin ? 1 : 0, 'form#dexFilters', 0, '#filter-released');
        if ($isAdmin) {
            $this->assertCountFilter($crawler, 3, 'form#dexFilters #filter-released', 0, 'option');
            $this->assertSelectedOptions($crawler, 'select#filter-released', ['']);
        }

        $this->assertCountFilter($crawler, $isCollector ? 1 : 0, 'form#dexFilters', 0, '#filter-premium');
        if ($isCollector) {
            $this->assertCountFilter($crawler, 3, 'form#dexFilters #filter-premium', 0, 'option');
            $this->assertSelectedOptions($crawler, 'select#filter-premium', ['']);
        }

        $this->assertCountFilter($crawler, 1, 'form#dexFilters', 0, '#filter-shiny');
        $this->assertCountFilter($crawler, 3, 'form#dexFilters #filter-shiny', 0, 'option');
        $this->assertSelectedOptions($crawler, 'select#filter-shiny', ['']);

        $this->assertCountFilter($crawler, 21, '.trainer-dex-item');
        $this->assertCountFilter($crawler, 21, '.trainer-dex-item img');
        $this->assertCountFilter($crawler, 21, '.trainer-dex-item a');
        $this->assertCountFilter($crawler, 21, '.trainer-dex-item h5');
        $this->assertCountFilter($crawler, 0, '.trainer-dex-item h6');
        $this->assertCountFilter($crawler, 42, '.trainer-dex-item input[type="checkbox"]');
        $this->assertCountFilter($crawler, $reportCount, '.trainer-dex-item .progress');

        $this->assertEmpty($crawler->filter('#goldsilvercrystal-is_private')->attr('checked'));
        $this->assertEmpty($crawler->filter('#goldsilvercrystal-is_on_home')->attr('checked'));

        $this->assertNull($crawler->filter('#home-is_private')->attr('checked'));
        $this->assertEmpty($crawler->filter('#home-is_on_home')->attr('checked'));

        $this->assertStringContainsString(
            'https://icon.pokenini.fr/banner/',
            (string) $crawler->filter('.trainer-dex-item img')->eq(0)->attr('src')
        );
    }
}
