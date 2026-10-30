<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\ResponseObject\WhichShinyGame\WhichShinyGame;
use App\ResponseObject\WhichShinyGame\WhichShinyGameDexSummary;
use App\ResponseObject\WhichShinyGame\WhichShinyGameRounds;
use App\Security\UserTokenServiceInterface;
use App\Service\Back\AbstractBackService;
use App\Service\Back\WhichShinyGameService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * The answered rounds of a game, kept apart to limit the coupling of WhichShinyGameServiceTest.
 *
 * @internal
 */
#[CoversClass(WhichShinyGameService::class)]
final class WhichShinyGameServiceRoundsTest extends AbstractTestBackService
{
    private const string JSON = '{"doesnt": "matter"}';

    #[Test]
    public function getRounds(): void
    {
        $rounds = new WhichShinyGameRounds($this->makeGame(), []);

        /** @var WhichShinyGameService $service */
        $service = $this->getServiceWithLoggedUser(
            'GET',
            self::JSON,
            'which_shiny_game/games/g1/rounds',
            [],
            $this->getSerializerMock(WhichShinyGameRounds::class, $rounds),
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
        return new WhichShinyGameService(
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

    private function makeGame(): WhichShinyGame
    {
        return new WhichShinyGame(
            'g1',
            new WhichShinyGameDexSummary('demo', 'Demo', 'Démo'),
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
