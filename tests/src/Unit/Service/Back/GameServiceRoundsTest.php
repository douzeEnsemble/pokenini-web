<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\Security\UserTokenServiceInterface;
use App\Service\Back\AbstractBackService;
use App\Service\Back\ColorGameService;
use App\Service\Back\ShinyGameService;
use App\Service\Back\WhichShinyGameService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * The answered rounds of a game, for the three games.
 *
 * Kept apart from the services tests to limit their coupling, and in one class
 * to limit the number of children of AbstractTestBackService.
 *
 * @internal
 */
#[CoversClass(ColorGameService::class)]
#[CoversClass(ShinyGameService::class)]
#[CoversClass(WhichShinyGameService::class)]
final class GameServiceRoundsTest extends AbstractTestBackService
{
    private const string JSON = '{"doesnt": "matter"}';

    private string $prefix = 'color_game';

    #[Test]
    #[DataProvider('providerGames')]
    public function getRounds(string $prefix, string $roundsClass): void
    {
        $this->prefix = $prefix;

        /** @var class-string $class */
        $class = $roundsClass;
        $rounds = new \ReflectionClass($class)->newInstanceWithoutConstructor();

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer
            ->expects($this->once())
            ->method('deserialize')
            ->with(self::JSON, $roundsClass, 'json')
            ->willReturn($rounds)
        ;

        /** @var ColorGameService|ShinyGameService|WhichShinyGameService $service */
        $service = $this->getServiceWithLoggedUser(
            'GET',
            self::JSON,
            $prefix.'/games/g1/rounds',
            [],
            $serializer,
        );

        $this->assertSame($rounds, $service->getRounds('g1'));
    }

    /**
     * @return array<string, list{string, string}>
     */
    public static function providerGames(): array
    {
        return [
            'color' => ['color_game', 'App\ResponseObject\ColorGame\ColorGameRounds'],
            'shiny' => ['shiny_game', 'App\ResponseObject\ShinyGame\ShinyGameRounds'],
            'which_shiny' => ['which_shiny_game', 'App\ResponseObject\WhichShinyGame\WhichShinyGameRounds'],
        ];
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
        return match ($this->prefix) {
            'shiny_game' => new ShinyGameService($logger, $client, $url, $cafilePath, $userTokenService, $serializer),
            'which_shiny_game' => new WhichShinyGameService($logger, $client, $url, $cafilePath, $userTokenService, $serializer),
            default => new ColorGameService($logger, $client, $url, $cafilePath, $userTokenService, $serializer),
        };
    }
}
