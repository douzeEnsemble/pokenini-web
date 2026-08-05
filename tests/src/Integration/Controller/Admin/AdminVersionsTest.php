<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Admin;

use App\Controller\AdminVersionsController;
use App\DTO\VersionsOverview;
use App\Service\VersionsOverviewService;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(AdminVersionsController::class)]
#[Group('api-mocked-testing')]
final class AdminVersionsTest extends WebTestCase
{
    public function testVersionsTabShowsAllFourBricks(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/istration/versions');

        $this->assertResponseStatusCodeSame(200);

        $expectedWebVersion = trim((string) file_get_contents(dirname(__DIR__, 5).'/resources/metadata/version'));

        $this->assertSame($expectedWebVersion, trim($crawler->filter('#versions-row-web td')->eq(1)->text()));
        $this->assertSame('1.9.9', trim($crawler->filter('#versions-row-back td')->eq(1)->text()));
        $this->assertSame('1.9.8', trim($crawler->filter('#versions-row-api td')->eq(1)->text()));
        $this->assertSame('1.9.7', trim($crawler->filter('#versions-row-resources td')->eq(1)->text()));
    }

    public function testVersionsTabShowsUnavailableBadgeWhenBricksCannotBeFetched(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $expectedWebVersion = trim((string) file_get_contents(dirname(__DIR__, 5).'/resources/metadata/version'));

        $versionsOverviewService = $this->createStub(VersionsOverviewService::class);
        $versionsOverviewService->method('get')->willReturn(
            new VersionsOverview(
                web: $expectedWebVersion,
                back: null,
                api: null,
                resources: null,
            )
        );
        self::getContainer()->set(VersionsOverviewService::class, $versionsOverviewService);

        $crawler = $client->request('GET', '/fr/istration/versions');

        $this->assertResponseStatusCodeSame(200);

        $this->assertSame($expectedWebVersion, trim($crawler->filter('#versions-row-web td')->eq(1)->text()));
        $this->assertSame('Indisponible', trim($crawler->filter('#versions-row-back td')->eq(1)->text()));
        $this->assertSame('Indisponible', trim($crawler->filter('#versions-row-api td')->eq(1)->text()));
        $this->assertSame('Indisponible', trim($crawler->filter('#versions-row-resources td')->eq(1)->text()));
    }

    public function testVersionsNotConnected(): void
    {
        $client = self::createClient();

        $client->request('GET', '/fr/istration/versions');

        $this->assertResponseStatusCodeSame(307);
    }

    public function testVersionsNotAllowed(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $client->loginUser($user, 'web');

        $client->request('GET', '/fr/istration/versions');

        $this->assertResponseStatusCodeSame(403);
    }
}
