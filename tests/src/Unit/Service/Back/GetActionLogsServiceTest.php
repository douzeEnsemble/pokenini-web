<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\ResponseObject\ActionLog;
use App\Security\UserTokenServiceInterface;
use App\Service\Back\AbstractBackService;
use App\Service\Back\GetActionLogsService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
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

    #[Test]
    public function get(): void
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

    #[Test]
    public function withoutLoggedUser(): void
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

        self::assertIsList($actionLogs);
        self::assertCount(11, $actionLogs);

        $expectedActionTypes = [
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
            'update_regional_dex_numbers',
        ];

        $byActionType = [];
        foreach ($actionLogs as $entry) {
            $byActionType[$entry->getActionType()] = $entry;
        }

        foreach ($expectedActionTypes as $actionType) {
            $this->assertArrayHasKey($actionType, $byActionType);
        }

        // Branch: last = null, current non-null (last null branch)
        $calcGameBundles = $byActionType['calculate_game_bundles_availabilities'];
        $this->assertNull($calcGameBundles->getLast());
        $this->assertNotNull($calcGameBundles->getCurrent());
        $this->assertEquals(new \DateTimeImmutable('2023-03-21T07:15:04+00:00'), $calcGameBundles->getCurrent()->createdAt);
        $this->assertNull($calcGameBundles->getCurrent()->doneAt);
        $this->assertNull($calcGameBundles->getCurrent()->executionTime);
        $this->assertSame([], $calcGameBundles->getCurrent()->details);
        $this->assertNull($calcGameBundles->getCurrent()->errorTrace);

        // Branch: current = null (current null branch)
        $updateRegionalDex = $byActionType['update_regional_dex_numbers'];
        $this->assertNull($updateRegionalDex->getCurrent());
        $this->assertNull($updateRegionalDex->getLast());

        // Branch: current non-null, last non-null (all values present)
        $calcDex = $byActionType['calculate_dex_availabilities'];
        $this->assertNotNull($calcDex->getLast());
        $this->assertEquals(new \DateTimeImmutable('2023-03-20T09:14:36+00:00'), $calcDex->getLast()->createdAt);
        $this->assertEquals(new \DateTimeImmutable('2023-03-20T10:05:08+00:00'), $calcDex->getLast()->doneAt);
        $this->assertSame(3032, $calcDex->getLast()->executionTime);
        $this->assertSame(['dex_availabilities' => 22472], $calcDex->getLast()->details);
        $this->assertNull($calcDex->getLast()->errorTrace);
    }
}
