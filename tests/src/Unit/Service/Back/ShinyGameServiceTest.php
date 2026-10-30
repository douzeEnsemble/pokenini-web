<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\ResponseObject\ShinyGame\ShinyGame;
use App\ResponseObject\ShinyGame\ShinyGameAnswer;
use App\ResponseObject\ShinyGame\ShinyGameDexSummary;
use App\ResponseObject\ShinyGame\ShinyGameRounds;
use App\ResponseObject\ShinyGame\ShinyGameState;
use App\Security\UserTokenServiceInterface;
use App\Service\Back\AbstractBackService;
use App\Service\Back\ShinyGameService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @internal
 */
#[CoversClass(ShinyGameService::class)]
final class ShinyGameServiceTest extends AbstractTestBackService
{
    private const string JSON = '{"doesnt": "matter"}';

    #[Test]
    public function getDexList(): void
    {
        $items = [];

        /** @var ShinyGameService $service */
        $service = $this->getServiceWithLoggedUser(
            'GET',
            self::JSON,
            'shiny_game/dex',
            [],
            $this->getSerializerMock('App\ResponseObject\ShinyGame\ShinyGameDexListItem[]', $items),
        );

        $this->assertSame($items, $service->getDexList());
    }

    #[Test]
    public function getGames(): void
    {
        $games = [$this->makeGame()];

        /** @var ShinyGameService $service */
        $service = $this->getServiceWithLoggedUser(
            'GET',
            self::JSON,
            'shiny_game/games',
            [],
            $this->getSerializerMock(ShinyGame::class.'[]', $games),
        );

        $this->assertSame($games, $service->getGames());
    }

    #[Test]
    public function create(): void
    {
        $game = $this->makeGame();

        /** @var ShinyGameService $service */
        $service = $this->getServiceWithLoggedUser(
            'POST',
            self::JSON,
            'shiny_game/games',
            ['json' => ['dex_slug' => 'demo', 'round_count' => null]],
            $this->getSerializerMock(ShinyGame::class, $game),
        );

        $this->assertSame($game, $service->create('demo', null));
    }

    #[Test]
    public function createWithRoundCount(): void
    {
        $game = $this->makeGame();

        /** @var ShinyGameService $service */
        $service = $this->getServiceWithLoggedUser(
            'POST',
            self::JSON,
            'shiny_game/games',
            ['json' => ['dex_slug' => 'demo', 'round_count' => 20]],
            $this->getSerializerMock(ShinyGame::class, $game),
        );

        $this->assertSame($game, $service->create('demo', 20));
    }

    #[Test]
    public function get(): void
    {
        $state = new ShinyGameState($this->makeGame(), null);

        /** @var ShinyGameService $service */
        $service = $this->getServiceWithLoggedUser(
            'GET',
            self::JSON,
            'shiny_game/games/g1',
            [],
            $this->getSerializerMock(ShinyGameState::class, $state),
        );

        $this->assertSame($state, $service->get('g1'));
    }

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

    #[Test]
    public function answer(): void
    {
        $answer = new ShinyGameAnswer(2, true, true, true, $this->makeGame());

        /** @var ShinyGameService $service */
        $service = $this->getServiceWithLoggedUser(
            'POST',
            self::JSON,
            'shiny_game/games/g1/answers',
            ['json' => ['position' => 2, 'is_shiny' => true]],
            $this->getSerializerMock(ShinyGameAnswer::class, $answer),
        );

        $this->assertSame($answer, $service->answer('g1', 2, true));
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
