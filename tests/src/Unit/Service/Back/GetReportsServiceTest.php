<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\Service\Back\GetReportsService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(GetReportsService::class)]
class GetReportsServiceTest extends TestCase
{
    use BackServiceTrait;

    public const ENDPOINT = 'istration/reports';
    public const RESPONSE_CONTENT = '/var/www/html/tests/resources/unit/service/back/reports.json';

    public function testGet(): void
    {
        /** @var GetReportsService $service */
        $service = $this->getServiceWithLoggedUser(
            GetReportsService::class,
            'GET',
            (string) file_get_contents(self::RESPONSE_CONTENT),
            self::ENDPOINT,
        );

        $reports = $service->get();

        $this->assertArrayHasKey('catch_state_counts_defined_by_trainer', $reports);
        $this->assertCount(3, $reports['catch_state_counts_defined_by_trainer']);
        $this->assertArrayHasKey('dex_usage', $reports);
        $this->assertCount(12, $reports['dex_usage']);
        $this->assertArrayHasKey('catch_state_usage', $reports);
        $this->assertCount(6, $reports['catch_state_usage']);
    }

    public function testGetWithoutLoggedUser(): void
    {
        /** @var GetReportsService $service */
        $service = $this->getServiceWithoutLoggedUser(
            GetReportsService::class,
            'GET',
            (string) file_get_contents(self::RESPONSE_CONTENT),
            self::ENDPOINT,
        );

        $reports = $service->get();

        $this->assertArrayHasKey('catch_state_counts_defined_by_trainer', $reports);
        $this->assertCount(3, $reports['catch_state_counts_defined_by_trainer']);
        $this->assertArrayHasKey('dex_usage', $reports);
        $this->assertCount(12, $reports['dex_usage']);
        $this->assertArrayHasKey('catch_state_usage', $reports);
        $this->assertCount(6, $reports['catch_state_usage']);
    }
}
