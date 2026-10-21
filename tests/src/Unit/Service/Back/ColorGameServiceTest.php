<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\ResponseObject\ColorGame\ColorGame;
use App\ResponseObject\ColorGame\ColorGameAnswer;
use App\ResponseObject\ColorGame\ColorGameDexSummary;
use App\ResponseObject\ColorGame\ColorGameState;
use App\Security\UserTokenServiceInterface;
use App\Service\Back\AbstractBackService;
use App\Service\Back\ColorGameService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @internal
 */
#[CoversClass(ColorGameService::class)]
final class ColorGameServiceTest extends AbstractTestBackService
{
    private const string JSON = '{"doesnt": "matter"}';

    #[Test]
    public function getDexList(): void
    {
        $items = [];

        /** @var ColorGameService $service */
        $service = $this->getServiceWithLoggedUser(
            'GET',
            self::JSON,
            'color_game/dex',
            [],
            $this->getSerializerMock('App\ResponseObject\ColorGame\ColorGameDexListItem[]', $items),
        );

        $this->assertSame($items, $service->getDexList());
    }

    #[Test]
    public function getGames(): void
    {
        $games = [$this->makeGame()];

        /** @var ColorGameService $service */
        $service = $this->getServiceWithLoggedUser(
            'GET',
            self::JSON,
            'color_game/games',
            [],
            $this->getSerializerMock(ColorGame::class.'[]', $games),
        );

        $this->assertSame($games, $service->getGames());
    }

    #[Test]
    public function create(): void
    {
        $game = $this->makeGame();

        /** @var ColorGameService $service */
        $service = $this->getServiceWithLoggedUser(
            'POST',
            self::JSON,
            'color_game/games',
            ['json' => ['dex_slug' => 'demo']],
            $this->getSerializerMock(ColorGame::class, $game),
        );

        $this->assertSame($game, $service->create('demo'));
    }

    #[Test]
    public function get(): void
    {
        $state = new ColorGameState($this->makeGame(), null);

        /** @var ColorGameService $service */
        $service = $this->getServiceWithLoggedUser(
            'GET',
            self::JSON,
            'color_game/games/g1',
            [],
            $this->getSerializerMock(ColorGameState::class, $state),
        );

        $this->assertSame($state, $service->get('g1'));
    }

    #[Test]
    public function answer(): void
    {
        $answer = new ColorGameAnswer(2, true, 1, 1, $this->makeGame());

        /** @var ColorGameService $service */
        $service = $this->getServiceWithLoggedUser(
            'POST',
            self::JSON,
            'color_game/games/g1/answers',
            ['json' => ['position' => 2, 'choice' => 1]],
            $this->getSerializerMock(ColorGameAnswer::class, $answer),
        );

        $this->assertSame($answer, $service->answer('g1', 2, 1));
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
