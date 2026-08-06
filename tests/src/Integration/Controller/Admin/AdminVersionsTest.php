<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Admin;

use App\Controller\AdminVersionsController;
use App\DTO\VersionsOverview;
use App\ResponseObject\BrickVersion;
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

        $versionFilePath = dirname(__DIR__, 5).'/resources/metadata/version';
        $expectedWebVersion = trim((string) file_get_contents($versionFilePath));
        $expectedWebUpdatedAt = (new \DateTimeImmutable())
            ->setTimestamp((int) filemtime($versionFilePath))
            ->setTimezone(new \DateTimeZone('Europe/Paris'))
            ->format('d/m/Y \\à H:i')
        ;
        $expectedBackUpdatedAt = (new \DateTimeImmutable('2026-08-04T21:47:00+00:00'))
            ->setTimezone(new \DateTimeZone('Europe/Paris'))
            ->format('d/m/Y \\à H:i')
        ;
        $expectedApiUpdatedAt = (new \DateTimeImmutable('2026-01-01T00:00:00+00:00'))
            ->setTimezone(new \DateTimeZone('Europe/Paris'))
            ->format('d/m/Y \\à H:i')
        ;
        $expectedResourcesUpdatedAt = (new \DateTimeImmutable('Wed, 05 Aug 2026 09:12:00 GMT'))
            ->setTimezone(new \DateTimeZone('Europe/Paris'))
            ->format('d/m/Y \\à H:i')
        ;

        $this->assertSame($expectedWebVersion, trim($crawler->filter('#versions-row-web .versions-version')->text()));
        $this->assertSame($expectedWebUpdatedAt, trim($crawler->filter('#versions-row-web .versions-date')->text()));

        $this->assertSame('1.9.9', trim($crawler->filter('#versions-row-back .versions-version')->text()));
        $this->assertSame($expectedBackUpdatedAt, trim($crawler->filter('#versions-row-back .versions-date')->text()));

        $this->assertSame('1.9.8', trim($crawler->filter('#versions-row-api .versions-version')->text()));
        $this->assertSame($expectedApiUpdatedAt, trim($crawler->filter('#versions-row-api .versions-date')->text()));

        $this->assertSame('1.9.7', trim($crawler->filter('#versions-row-resources .versions-version')->text()));
        $this->assertSame($expectedResourcesUpdatedAt, trim($crawler->filter('#versions-row-resources .versions-date')->text()));
    }

    public function testVersionsTabShowsUnavailableBadgeWhenBricksCannotBeFetched(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $versionFilePath = dirname(__DIR__, 5).'/resources/metadata/version';
        $expectedWebVersion = trim((string) file_get_contents($versionFilePath));
        $expectedWebUpdatedAt = (new \DateTimeImmutable())->setTimestamp((int) filemtime($versionFilePath));

        $versionsOverviewService = $this->createStub(VersionsOverviewService::class);
        $versionsOverviewService->method('get')->willReturn(
            new VersionsOverview(
                web: new BrickVersion($expectedWebVersion, $expectedWebUpdatedAt),
                back: new BrickVersion(null, null),
                api: new BrickVersion(null, null),
                resources: new BrickVersion(null, null),
            )
        );
        self::getContainer()->set(VersionsOverviewService::class, $versionsOverviewService);

        $crawler = $client->request('GET', '/fr/istration/versions');

        $this->assertResponseStatusCodeSame(200);

        $this->assertSame($expectedWebVersion, trim($crawler->filter('#versions-row-web .versions-version')->text()));
        $this->assertSame('Indisponible', trim($crawler->filter('#versions-row-back .versions-version')->text()));
        $this->assertSame('', trim($crawler->filter('#versions-row-back .versions-date')->text()));
        $this->assertSame('Indisponible', trim($crawler->filter('#versions-row-api .versions-version')->text()));
        $this->assertSame('', trim($crawler->filter('#versions-row-api .versions-date')->text()));
        $this->assertSame('Indisponible', trim($crawler->filter('#versions-row-resources .versions-version')->text()));
        $this->assertSame('', trim($crawler->filter('#versions-row-resources .versions-date')->text()));
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
