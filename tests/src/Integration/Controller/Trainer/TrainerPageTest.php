<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Trainer;

use App\Controller\TrainerIndexController;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
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

    #[Test]
    public function trainerPage(): void
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

        $this->assertStringContainsString(
            'Tu peux y personnaliser tes albums, consulter les liens entre tes dex et tes données personnelles.',
            $crawler->filter('#main-container')->text()
        );

        $this->assertCustomizeAlbumSection($crawler, false, false, 5);

        $this->assertLogoutNavBar($crawler);

        $this->assertCount(3, $crawler->filter('#trainer-section-tab .nav-link'));
        $this->assertSame(
            '/fr/trainer',
            $crawler->filter('#trainer-section-tab .nav-link.active')->attr('href')
        );

        $this->assertCountFilter($crawler, 3, '.navbar-nav .trainer-link .dropdown-menu .dropdown-item');
        $this->assertCountFilter($crawler, 1, '.navbar-nav .trainer-link .dropdown-item.active');
        $this->assertStringContainsString(
            '/fr/trainer',
            $crawler->filter('.navbar-nav .trainer-link .dropdown-item.active')->attr('href') ?? ''
        );
        $this->assertStringContainsString(
            '/fr/trainer/links',
            $crawler->filter('.navbar-nav .trainer-link .dropdown-item')->eq(1)->attr('href') ?? ''
        );
        $this->assertStringContainsString(
            '/fr/trainer/personnal_data',
            $crawler->filter('.navbar-nav .trainer-link .dropdown-item')->eq(2)->attr('href') ?? ''
        );

        $this->assertCount(0, $crawler->filter('.navbar-link'));

        $this->assertCountFilter($crawler, 1, '.dex_is_shiny');
        $this->assertCountFilter($crawler, 2, '.dex_is_premium');
        $this->assertCountFilter($crawler, 0, '.dex_not_is_released');
        $this->assertCountFilter($crawler, 1, '.dex_is_custom');
    }

    #[Test]
    public function collectorPage(): void
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

        $this->assertCustomizeAlbumSection($crawler, false, true, 5);

        $this->assertLogoutNavBar($crawler);

        $this->assertCount(0, $crawler->filter('.navbar-link'));

        $this->assertCountFilter($crawler, 1, '.dex_is_shiny');
        $this->assertCountFilter($crawler, 2, '.dex_is_premium');
        $this->assertCountFilter($crawler, 0, '.dex_not_is_released');
        $this->assertCountFilter($crawler, 1, '.dex_is_custom');
    }

    #[Test]
    public function adminTrainerPage(): void
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

        $this->assertCustomizeAlbumSection($crawler, true, true, 0);

        $this->assertLogoutNavBar($crawler);

        $this->assertCount(0, $crawler->filter('.navbar-link'));

        $this->assertCountFilter($crawler, 2, '.dex_is_shiny');
        $this->assertCountFilter($crawler, 3, '.dex_is_premium');
        $this->assertCountFilter($crawler, 2, '.dex_not_is_released');
        $this->assertCountFilter($crawler, 0, '.dex_is_custom');
    }

    #[Test]
    public function trainerPageNotAllowed(): void
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
            'https://icon.pokenini.fr/dex/',
            (string) $crawler->filter('.trainer-dex-item img')->eq(0)->attr('src')
        );
    }
}
