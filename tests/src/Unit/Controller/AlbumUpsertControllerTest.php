<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\AlbumUpsertController;
use App\Exception\EmptyContentException;
use App\Exception\InvalidJsonException;
use App\Service\GetTrainerPokedexService;
use App\Service\ModifyTrainerAlbumService;
use App\Service\RequestedContentService;
use App\Validator\CatchStates;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @internal
 */
#[CoversClass(AlbumUpsertController::class)]
#[CoversClass(ModifyTrainerAlbumService::class)]
class AlbumUpsertControllerTest extends TestCase
{
    public function testUpsert(): void
    {
        $requestedContentService = $this->createMock(RequestedContentService::class);
        $requestedContentService
            ->expects($this->once())
            ->method('getContent')
            ->with(new CatchStates())
            ->willReturn('{"key": "value"}')
        ;

        $getTrainerPokedexService = $this->createMock(GetTrainerPokedexService::class);
        $getTrainerPokedexService
            ->expects($this->once())
            ->method('getPokedexData')
            ->with('douze', [])
            ->willReturn([
                'dex' => [
                    'slug' => 'douze',
                    'is_premium' => true,
                ],
                'pokemons' => [],
            ])
        ;

        $modifyTrainerAlbumService = $this->createMock(ModifyTrainerAlbumService::class);
        $modifyTrainerAlbumService
            ->expects($this->once())
            ->method('modifyAlbum')
            ->with('douze', 'machi', '{"key": "value"}')
        ;

        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_COLLECTOR')
            ->willReturn(true)
        ;

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('has')
            ->willReturn(true)
        ;
        $container
            ->expects($this->once())
            ->method('get')
            ->willReturn($authorizationChecker)
        ;

        $controller = new AlbumUpsertController(
            $requestedContentService,
            $getTrainerPokedexService,
            $modifyTrainerAlbumService,
        );
        $controller->setContainer($container);

        $response = $controller->upsert('douze', 'machi');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEmpty($response->getContent());
    }

    public function testUpsertEmptyContentException(): void
    {
        $requestedContentService = $this->createMock(RequestedContentService::class);
        $requestedContentService
            ->expects($this->once())
            ->method('getContent')
            ->with(new CatchStates())
            ->willThrowException(new EmptyContentException())
        ;

        $getTrainerPokedexService = $this->createMock(GetTrainerPokedexService::class);
        $getTrainerPokedexService
            ->expects($this->never())
            ->method('getPokedexData')
        ;

        $modifyTrainerAlbumService = $this->createMock(ModifyTrainerAlbumService::class);
        $modifyTrainerAlbumService
            ->expects($this->never())
            ->method('modifyAlbum')
        ;

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->never())
            ->method('has')
        ;
        $container
            ->expects($this->never())
            ->method('get')
        ;

        $controller = new AlbumUpsertController(
            $requestedContentService,
            $getTrainerPokedexService,
            $modifyTrainerAlbumService,
        );
        $controller->setContainer($container);

        $response = $controller->upsert('douze', 'machi');

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertSame('{"error":"Content cannot be empty"}', $response->getContent());
    }

    public function testUpsertInvalidJsonException(): void
    {
        $requestedContentService = $this->createMock(RequestedContentService::class);
        $requestedContentService
            ->expects($this->once())
            ->method('getContent')
            ->with(new CatchStates())
            ->willThrowException(new InvalidJsonException())
        ;

        $getTrainerPokedexService = $this->createMock(GetTrainerPokedexService::class);
        $getTrainerPokedexService
            ->expects($this->never())
            ->method('getPokedexData')
        ;

        $modifyTrainerAlbumService = $this->createMock(ModifyTrainerAlbumService::class);
        $modifyTrainerAlbumService
            ->expects($this->never())
            ->method('modifyAlbum')
        ;

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->never())
            ->method('has')
        ;
        $container
            ->expects($this->never())
            ->method('get')
        ;

        $controller = new AlbumUpsertController(
            $requestedContentService,
            $getTrainerPokedexService,
            $modifyTrainerAlbumService,
        );
        $controller->setContainer($container);

        $response = $controller->upsert('douze', 'machi');

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertSame('{"error":"Json is invalid"}', $response->getContent());
    }

    public function testUpsertPokedexNull(): void
    {
        $requestedContentService = $this->createMock(RequestedContentService::class);
        $requestedContentService
            ->expects($this->once())
            ->method('getContent')
            ->with(new CatchStates())
            ->willReturn('{"key": "value"}')
        ;

        $getTrainerPokedexService = $this->createMock(GetTrainerPokedexService::class);
        $getTrainerPokedexService
            ->expects($this->once())
            ->method('getPokedexData')
            ->with('douze', [])
            ->willReturn(null)
        ;

        $modifyTrainerAlbumService = $this->createMock(ModifyTrainerAlbumService::class);
        $modifyTrainerAlbumService
            ->expects($this->never())
            ->method('modifyAlbum')
        ;

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->never())
            ->method('has')
        ;
        $container
            ->expects($this->never())
            ->method('get')
        ;

        $controller = new AlbumUpsertController(
            $requestedContentService,
            $getTrainerPokedexService,
            $modifyTrainerAlbumService,
        );
        $controller->setContainer($container);

        $response = $controller->upsert('douze', 'machi');

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertSame('[]', $response->getContent());
    }

    public function testUpsertDexNotDefined(): void
    {
        $requestedContentService = $this->createMock(RequestedContentService::class);
        $requestedContentService
            ->expects($this->once())
            ->method('getContent')
            ->with(new CatchStates())
            ->willReturn('{"key": "value"}')
        ;

        $getTrainerPokedexService = $this->createMock(GetTrainerPokedexService::class);
        $getTrainerPokedexService
            ->expects($this->once())
            ->method('getPokedexData')
            ->with('douze', [])
            ->willReturn([
                'pokemons' => [],
            ])
        ;

        $modifyTrainerAlbumService = $this->createMock(ModifyTrainerAlbumService::class);
        $modifyTrainerAlbumService
            ->expects($this->never())
            ->method('modifyAlbum')
        ;

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->never())
            ->method('has')
        ;
        $container
            ->expects($this->never())
            ->method('get')
        ;

        $controller = new AlbumUpsertController(
            $requestedContentService,
            $getTrainerPokedexService,
            $modifyTrainerAlbumService,
        );
        $controller->setContainer($container);

        $response = $controller->upsert('douze', 'machi');

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertSame('[]', $response->getContent());
    }
}
