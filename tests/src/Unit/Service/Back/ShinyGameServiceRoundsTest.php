<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\ResponseObject\ShinyGame\ShinyGame;
use App\ResponseObject\ShinyGame\ShinyGameDexSummary;
use App\ResponseObject\ShinyGame\ShinyGameRounds;
use App\Security\UserTokenServiceInterface;
use App\Service\Back\AbstractBackService;
use App\Service\Back\ShinyGameService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * The answered rounds of a game, kept apart to limit the coupling of ShinyGameServiceTest.
 *
 * @internal
 */
#[CoversClass(ShinyGameService::class)]
final class ShinyGameServiceRoundsTest extends AbstractTestBackService
{
    private const string JSON = '{"doesnt": "matter"}';

    #[Test]
    public function getRounds(): void
    {
        $rounds = new ShinyGameRounds($this->makeGame(), []);

        /** @var ShinyGameService $service */
        $service = $this->getServiceWithLoggedUser(
            'GET',
            self::JSON,
            'shiny_game/games/g1/rounds',
            [],
            $this->getSerializerMock(ShinyGameRounds::class, $rounds),
        );

        $this->assertSame($rounds, $service->getRounds('g1'));
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
        return new ShinyGameService(
            $logger,
            $client,
            $url,
            $cafilePath,
            $userTokenService,
            $serializer,
        );
    }

    private function getSerializerMock(string $type, mixed $result): SerializerInterface
    {
        $serializer = $this->createMock(SerializerInterface::class);
        $serializer
            ->expects($this->once())
            ->method('deserialize')
            ->with(self::JSON, $type, 'json')
            ->willReturn($result)
        ;

        return $serializer;
    }

    private function makeGame(): ShinyGame
    {
        return new ShinyGame(
            'g1',
            new ShinyGameDexSummary('demo', 'Demo', 'Démo'),
            'in_progress',
            3,
            1,
            1,
            '2026-10-20T10:00:00+00:00',
            null,
            null,
        );
    }
}
