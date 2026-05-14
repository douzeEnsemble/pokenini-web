<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\Security\UserTokenService;
use App\Service\Back\AbstractBackService;
use App\Service\Back\GetReportsService;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @internal
 */
#[CoversClass(GetReportsService::class)]
final class GetReportsServiceTest extends AbstractTestBackService
{
    public const ENDPOINT = 'istration/reports';
    public const RESPONSE_CONTENT = '/app/tests/resources/unit/service/back/reports.json';

    public function testGet(): void
    {
        /** @var GetReportsService $service */
        $service = $this->getServiceWithLoggedUser(
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

    #[\Override]
    protected function instanciateService(
        LoggerInterface $logger,
        HttpClientInterface $client,
        string $url,
        string $cafilePath,
        UserTokenService $userTokenService,
        SerializerInterface $serializer,
    ): AbstractBackService {
        return new GetReportsService(
            $logger,
            $client,
            $url,
            $cafilePath,
            $userTokenService,
            $serializer,
        );
    }
}
