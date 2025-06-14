<?php

declare(strict_types=1);

namespace App\Tests\Functional\Common;

use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use PHPUnit\Framework\Attributes\CoversNothing;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @internal
 */
#[CoversNothing]
class TrackerTest extends WebTestCase
{
    use TestNavTrait;

    public function testTrackerAsAdmin(): void
    {
        $client = static::createClient();

        $user = new User('8764532', 'TestProvider');
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr');

        $this->assertTarteAuCitron($crawler);
        $this->assertMatomo($crawler);

        $this->assertStringContainsString(
            "_paq.push(['setCustomDimension', customDimensionId = 1, customDimensionValue = 'admin']);",
            $crawler->outerHtml()
        );
        $this->assertStringContainsString(
            "_paq.push(['setUserId', '159bb9b6d090a313087d2f26135970c2db49ee72']);",
            $crawler->outerHtml()
        );
    }

    public function testTrackerAsCollector(): void
    {
        $client = static::createClient();

        $user = new User('4568465464', 'TestProvider');
        $user->addCollectorRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr');

        $this->assertTarteAuCitron($crawler);
        $this->assertMatomo($crawler);

        $this->assertStringContainsString(
            "_paq.push(['setCustomDimension', customDimensionId = 1, customDimensionValue = 'collector']);",
            $crawler->outerHtml()
        );
        $this->assertStringContainsString(
            "_paq.push(['setUserId', '13dde860e25a0f972170570d25f48e10cacd5cb3']);",
            $crawler->outerHtml()
        );
    }

    public function testTrackerAsTrainer(): void
    {
        $client = static::createClient();

        $user = new User('daz5d4az6d4a6z4d6az5d', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr');

        $this->assertTarteAuCitron($crawler);
        $this->assertMatomo($crawler);

        $this->assertStringContainsString(
            "_paq.push(['setCustomDimension', customDimensionId = 1, customDimensionValue = 'trainer']);",
            $crawler->outerHtml()
        );
        $this->assertStringContainsString(
            "_paq.push(['setUserId', '88f4a8011adfd1868148addb265ef2683fdf40d8']);",
            $crawler->outerHtml()
        );
    }

    public function testTrackerAsGuest(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr');

        $this->assertTarteAuCitron($crawler);
        $this->assertMatomo($crawler);

        $this->assertStringNotContainsString(
            "_paq.push(['setCustomDimension', customDimensionId = 1, customDimensionValue = '",
            $crawler->outerHtml()
        );
        $this->assertStringNotContainsString(
            "_paq.push(['setUserId'",
            $crawler->outerHtml()
        );
    }

    private function assertTarteAuCitron(Crawler $crawler): void
    {
        $this->assertStringContainsString(
            '<script src="/lib/tarteaucitron/tarteaucitron.min.js"></script>',
            $crawler->outerHtml()
        );
        $this->assertStringContainsString('tarteaucitron.init({', $crawler->outerHtml());
    }

    private function assertMatomo(Crawler $crawler): void
    {
        $this->assertStringContainsString('tarteaucitron.user.matomoId = 3;', $crawler->outerHtml());
        $this->assertStringContainsString(
            "tarteaucitron.user.matomoHost = 'http://moco.matomo.gbl/';",
            $crawler->outerHtml()
        );
    }
}
