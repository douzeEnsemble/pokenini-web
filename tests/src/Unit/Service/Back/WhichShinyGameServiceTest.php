<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\ResponseObject\WhichShinyGame\WhichShinyGame;
use App\ResponseObject\WhichShinyGame\WhichShinyGameAnswer;
use App\ResponseObject\WhichShinyGame\WhichShinyGameDexSummary;
use App\ResponseObject\WhichShinyGame\WhichShinyGameState;
use App\Security\UserTokenServiceInterface;
use App\Service\Back\AbstractBackService;
use App\Service\Back\WhichShinyGameService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @internal
 */
#[CoversClass(WhichShinyGameService::class)]
final class WhichShinyGameServiceTest extends AbstractTestBackService
{
    private const string JSON = '{"doesnt": "matter"}';

    #[Test]
    public function getDexList(): void
    {
        $items = [];

        /** @var WhichShinyGameService $service */
        $service = $this->getServiceWithLoggedUser(
            'GET',
            self::JSON,
            'which_shiny_game/dex',
            [],
            $this->getSerializerMock('App\ResponseObject\WhichShinyGame\WhichShinyGameDexListItem[]', $items),
        );

        $this->assertSame($items, $service->getDexList());
    }

    #[Test]
    public function getGames(): void
    {
        $games = [$this->makeGame()];

        /** @var WhichShinyGameService $service */
        $service = $this->getServiceWithLoggedUser(
            'GET',
            self::JSON,
            'which_shiny_game/games',
            [],
            $this->getSerializerMock(WhichShinyGame::class.'[]', $games),
        );

        $this->assertSame($games, $service->getGames());
    }

    #[Test]
    public function create(): void
    {
        $game = $this->makeGame();

        /** @var WhichShinyGameService $service */
        $service = $this->getServiceWithLoggedUser(
            'POST',
            self::JSON,
            'which_shiny_game/games',
            ['json' => ['dex_slug' => 'demo', 'round_count' => null]],
            $this->getSerializerMock(WhichShinyGame::class, $game),
        );

        $this->assertSame($game, $service->create('demo', null));
    }

    #[Test]
    public function createWithRoundCount(): void
    {
        $game = $this->makeGame();

        /** @var WhichShinyGameService $service */
        $service = $this->getServiceWithLoggedUser(
            'POST',
            self::JSON,
            'which_shiny_game/games',
            ['json' => ['dex_slug' => 'demo', 'round_count' => 20]],
            $this->getSerializerMock(WhichShinyGame::class, $game),
        );

        $this->assertSame($game, $service->create('demo', 20));
    }

    #[Test]
    public function get(): void
    {
        $state = new WhichShinyGameState($this->makeGame(), null);

        /** @var WhichShinyGameService $service */
        $service = $this->getServiceWithLoggedUser(
            'GET',
            self::JSON,
            'which_shiny_game/games/g1',
            [],
            $this->getSerializerMock(WhichShinyGameState::class, $state),
        );

        $this->assertSame($state, $service->get('g1'));
    }

    #[Test]
    public function answer(): void
    {
        $answer = new WhichShinyGameAnswer(2, true, 2, 2, $this->makeGame());

        /** @var WhichShinyGameService $service */
        $service = $this->getServiceWithLoggedUser(
            'POST',
            self::JSON,
            'which_shiny_game/games/g1/answers',
            ['json' => ['position' => 2, 'slot' => 2]],
            $this->getSerializerMock(WhichShinyGameAnswer::class, $answer),
        );

        $this->assertSame($answer, $service->answer('g1', 2, 2));
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
