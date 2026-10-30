<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\ResponseObject\ColorGame\ColorGame;
use App\ResponseObject\ColorGame\ColorGameDexSummary;
use App\ResponseObject\ColorGame\ColorGameRounds;
use App\Security\UserTokenServiceInterface;
use App\Service\Back\AbstractBackService;
use App\Service\Back\ColorGameService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * The answered rounds of a game, kept apart to limit the coupling of ColorGameServiceTest.
 *
 * @internal
 */
#[CoversClass(ColorGameService::class)]
final class ColorGameServiceRoundsTest extends AbstractTestBackService
{
    private const string JSON = '{"doesnt": "matter"}';

    #[Test]
    public function getRounds(): void
    {
        $rounds = new ColorGameRounds($this->makeGame(), []);

        /** @var ColorGameService $service */
        $service = $this->getServiceWithLoggedUser(
            'GET',
            self::JSON,
            'color_game/games/g1/rounds',
            [],
            $this->getSerializerMock(ColorGameRounds::class, $rounds),
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
        return new ColorGameService(
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

    private function makeGame(): ColorGame
    {
        return new ColorGame(
            'g1',
            new ColorGameDexSummary('demo', 'Demo', 'Démo'),
            false,
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
