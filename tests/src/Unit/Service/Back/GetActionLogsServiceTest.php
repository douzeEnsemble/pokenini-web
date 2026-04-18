<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\Security\UserTokenService;
use App\Service\Back\BackServiceInterface;
use App\Service\Back\GetActionLogsService;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @internal
 */
#[CoversClass(GetActionLogsService::class)]
final class GetActionLogsServiceTest extends AbstractTestBackService
{
    public const ENDPOINT = 'istration/action_logs';
    public const RESPONSE_CONTENT = '/app/tests/resources/unit/service/back/action-logs.json';

    public function testGet(): void
    {
        /** @var GetActionLogsService $service */
        $service = $this->getServiceWithLoggedUser(
            'GET',
            (string) file_get_contents(self::RESPONSE_CONTENT),
            self::ENDPOINT,
        );

        $this->assertServiceGet($service);
    }

    public function testWithoutLoggedUser(): void
    {
        /** @var GetActionLogsService $service */
        $service = $this->getServiceWithoutLoggedUser(
            'GET',
            (string) file_get_contents(self::RESPONSE_CONTENT),
            self::ENDPOINT,
        );

        $this->assertServiceGet($service);
    }

    #[\Override]
    protected function instanciateService(
        LoggerInterface $logger,
        HttpClientInterface $client,
        string $url,
        string $cafilePath,
        UserTokenService $userTokenService,
        SerializerInterface $serializer,
    ): BackServiceInterface {
        return new GetActionLogsService(
            $logger,
            $client,
            $url,
            $cafilePath,
            $userTokenService,
            $serializer,
        );
    }

    private function assertServiceGet(GetActionLogsService $service): void
    {
        $actionLogs = $service->get();

        $this->assertCount(10, $actionLogs);

        $expectedLogs = [
            'calculate_dex_availabilities',
            'calculate_pokemon_availabilities',
            'calculate_game_bundles_availabilities',
            'calculate_game_bundles_shinies_availabilities',
            'update_games_collections_and_dex',
            'update_games_availabilities',
            'update_games_shinies_availabilities',
            'update_labels',
            'update_pokemons',
            'update_collections_availabilities',
        ];

        foreach ($expectedLogs as $key) {
            $this->assertArrayHasKey($key, $actionLogs);
        }
    }
}
