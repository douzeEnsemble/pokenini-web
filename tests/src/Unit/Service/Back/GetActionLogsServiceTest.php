<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\ResponseObject\ActionLog;
use App\Security\UserTokenServiceInterface;
use App\Service\Back\AbstractBackService;
use App\Service\Back\GetActionLogsService;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\NameConverter\MetadataAwareNameConverter;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @internal
 *
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
#[CoversClass(GetActionLogsService::class)]
#[CoversClass(ActionLog::class)]
final class GetActionLogsServiceTest extends AbstractTestBackService
{
    public const ENDPOINT = 'istration/action-logs';
    public const RESPONSE_CONTENT = '/app/tests/resources/unit/service/back/action-logs.json';

    public function testGet(): void
    {
        /** @var GetActionLogsService $service */
        $service = $this->getServiceWithLoggedUser(
            'GET',
            (new Filesystem())->readFile(self::RESPONSE_CONTENT),
            self::ENDPOINT,
            [],
            $this->buildSerializer(),
        );

        $this->assertServiceGet($service);
    }

    public function testWithoutLoggedUser(): void
    {
        /** @var GetActionLogsService $service */
        $service = $this->getServiceWithoutLoggedUser(
            'GET',
            (new Filesystem())->readFile(self::RESPONSE_CONTENT),
            self::ENDPOINT,
            [],
            $this->buildSerializer(),
        );

        $this->assertServiceGet($service);
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
        return new GetActionLogsService(
            $logger,
            $client,
            $url,
            $cafilePath,
            $userTokenService,
            $serializer,
        );
    }

    private function buildSerializer(): SerializerInterface
    {
        $classMetadataFactory = new ClassMetadataFactory(new AttributeLoader());
        $nameConverter = new MetadataAwareNameConverter($classMetadataFactory);

        return new Serializer(
            [
                new DateTimeNormalizer(),
                new ObjectNormalizer($classMetadataFactory, $nameConverter),
            ],
            [new JsonEncoder()]
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

        // Cas minimal : last = null, current sans valeurs optionnelles
        $calcGameBundles = $actionLogs['calculate_game_bundles_availabilities'];
        $this->assertNull($calcGameBundles->last);
        $this->assertEquals(new \DateTime('2023-03-21T07:15:04+00:00'), $calcGameBundles->current->createdAt);
        $this->assertNull($calcGameBundles->current->doneAt);
        $this->assertNull($calcGameBundles->current->executionTime);
        $this->assertSame([], $calcGameBundles->current->details);
        $this->assertNull($calcGameBundles->current->errorTrace);

        // Cas complet : last avec toutes les valeurs non-null
        $calcDex = $actionLogs['calculate_dex_availabilities'];
        $this->assertNotNull($calcDex->last);
        $this->assertEquals(new \DateTime('2023-03-20T09:14:36+00:00'), $calcDex->last->createdAt);
        $this->assertEquals(new \DateTime('2023-03-20T10:05:08+00:00'), $calcDex->last->doneAt);
        $this->assertSame(3032, $calcDex->last->executionTime);
        $this->assertSame(['dex_availabilities' => 22472], $calcDex->last->details);
        $this->assertNull($calcDex->last->errorTrace);
    }
}
