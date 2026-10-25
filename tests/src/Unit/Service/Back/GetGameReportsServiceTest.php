<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\Security\UserTokenServiceInterface;
use App\Service\Back\AbstractBackService;
use App\Service\Back\GetGameReportsService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @internal
 */
#[CoversClass(GetGameReportsService::class)]
final class GetGameReportsServiceTest extends AbstractTestBackService
{
    public const ENDPOINT = 'istration/reports/games';
    public const RESPONSE_CONTENT = '/app/tests/resources/unit/service/back/reports_games.json';

    #[Test]
    public function get(): void
    {
        /** @var GetGameReportsService $service */
        $service = $this->getServiceWithLoggedUser(
            'GET',
            (new Filesystem())->readFile(self::RESPONSE_CONTENT),
            self::ENDPOINT,
        );

        $reports = $service->get();

        $this->assertArrayHasKey('games', $reports);
        $this->assertCount(3, $reports['games']);
        $this->assertArrayHasKey('daily_games', $reports);
        $this->assertCount(30, $reports['daily_games']);
    }

    #[Test]
    public function getWithoutLoggedUser(): void
    {
        /** @var GetGameReportsService $service */
        $service = $this->getServiceWithoutLoggedUser(
            'GET',
            (new Filesystem())->readFile(self::RESPONSE_CONTENT),
            self::ENDPOINT,
        );

        $reports = $service->get();

        $this->assertArrayHasKey('games', $reports);
        $this->assertCount(3, $reports['games']);
        $this->assertArrayHasKey('daily_games', $reports);
        $this->assertCount(30, $reports['daily_games']);
    }

    #[\Override]
    protected function instanciateService(
        LoggerInterface $logger,
        HttpClientInterface $client,
        string $url,
        string $cafilePath,
        UserTokenServiceInterface $userTokenService,
        SerializerInterface $serializer,
    ): AbstractBackService {
        return new GetGameReportsService(
            $logger,
            $client,
            $url,
            $cafilePath,
            $userTokenService,
            $serializer,
        );
    }
}
