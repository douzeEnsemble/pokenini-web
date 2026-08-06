<?php

declare(strict_types=1);

namespace App\Tests\Unit\DTO;

use App\DTO\VersionsOverview;
use App\ResponseObject\BrickVersion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(VersionsOverview::class)]
final class VersionsOverviewTest extends TestCase
{
    public function testConstrutorAndGetters(): void
    {
        $webVersion = new BrickVersion(
            '1.0',
            new \DateTimeImmutable('1 day ago'),
        );
        $backVersion = new BrickVersion(
            '2.0',
            new \DateTimeImmutable('2 days ago'),
        );
        $apiVersion = new BrickVersion(
            '3.0',
            new \DateTimeImmutable('3 days ago'),
        );
        $resourcesVersion = new BrickVersion(
            '4.0',
            new \DateTimeImmutable('4 days ago'),
        );

        $versionsOverview = new VersionsOverview(
            $webVersion,
            $backVersion,
            $apiVersion,
            $resourcesVersion,
        );

        $this->assertSame(
            $webVersion,
            $versionsOverview->web,
        );
        $this->assertSame(
            $backVersion,
            $versionsOverview->back,
        );
        $this->assertSame(
            $apiVersion,
            $versionsOverview->api,
        );
        $this->assertSame(
            $resourcesVersion,
            $versionsOverview->resources,
        );
    }
}
