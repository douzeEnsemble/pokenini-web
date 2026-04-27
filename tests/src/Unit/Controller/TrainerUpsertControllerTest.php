<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\TrainerUpsertController;
use App\Exception\EmptyContentException;
use App\Exception\InvalidJsonException;
use App\Exception\ModifyFailedException;
use App\ResponseObject\Album\Album;
use App\ResponseObject\Album\Dex;
use App\ResponseObject\Album\Pokedex;
use App\ResponseObject\Album\Report;
use App\Service\GetTrainerPokedexService;
use App\Service\ModifyTrainerDexService;
use App\Service\RequestedContentService;
use App\Tests\Common\Traits\ResponseObjectTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraints\Json;

/**
 * @internal
 *
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
#[CoversClass(TrainerUpsertController::class)]
final class TrainerUpsertControllerTest extends TestCase
{
    use ResponseObjectTrait;

    public function testUpsert(): void
    {
        $getTrainerPokedexService = $this->createMock(GetTrainerPokedexService::class);
        $getTrainerPokedexService
            ->expects($this->once())
            ->method('getPokedexData')
            ->with('douze', [])
            ->willReturn(
                $this->getStubAlbum(),
            )
        ;

        $modifyTrainerDexService = $this->createMock(ModifyTrainerDexService::class);
        $modifyTrainerDexService
            ->expects($this->once())
            ->method('modifyDex')
            ->with(
                'douze',
                '{"key": "value"}',
            )
        ;

        $requestedContentService = $this->createMock(RequestedContentService::class);
        $requestedContentService
            ->expects($this->once())
            ->method('getContent')
            ->with(new Json())
            ->willReturn('{"key": "value"}')
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

        $controller = new TrainerUpsertController(
            $getTrainerPokedexService,
            $modifyTrainerDexService,
            $requestedContentService,
        );
        $controller->setContainer($container);

        $response = $controller->upsert('douze');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEmpty($response->getContent());
    }

    public function testUpsertEmptyContentException(): void
    {
        $getTrainerPokedexService = $this->createMock(GetTrainerPokedexService::class);
        $getTrainerPokedexService
            ->expects($this->never())
            ->method('getPokedexData')
        ;

        $modifyTrainerDexService = $this->createMock(ModifyTrainerDexService::class);
        $modifyTrainerDexService
            ->expects($this->never())
            ->method('modifyDex')
        ;

        $requestedContentService = $this->createMock(RequestedContentService::class);
        $requestedContentService
            ->expects($this->once())
            ->method('getContent')
            ->with(new Json())
            ->willThrowException(new EmptyContentException())
        ;

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->never())
            ->method('has')
            ->willReturn(true)
        ;
        $container
            ->expects($this->never())
            ->method('get')
        ;

        $controller = new TrainerUpsertController(
            $getTrainerPokedexService,
            $modifyTrainerDexService,
            $requestedContentService,
        );
        $controller->setContainer($container);

        $response = $controller->upsert('douze');

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertSame('{"error":"Content cannot be empty"}', $response->getContent());
    }

    public function testUpsertInvalidJsonException(): void
    {
        $getTrainerPokedexService = $this->createMock(GetTrainerPokedexService::class);
        $getTrainerPokedexService
            ->expects($this->never())
            ->method('getPokedexData')
        ;

        $modifyTrainerDexService = $this->createMock(ModifyTrainerDexService::class);
        $modifyTrainerDexService
            ->expects($this->never())
            ->method('modifyDex')
        ;

        $requestedContentService = $this->createMock(RequestedContentService::class);
        $requestedContentService
            ->expects($this->once())
            ->method('getContent')
            ->with(new Json())
            ->willThrowException(new InvalidJsonException())
        ;

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->never())
            ->method('has')
            ->willReturn(true)
        ;
        $container
            ->expects($this->never())
            ->method('get')
        ;

        $controller = new TrainerUpsertController(
            $getTrainerPokedexService,
            $modifyTrainerDexService,
            $requestedContentService,
        );
        $controller->setContainer($container);

        $response = $controller->upsert('douze');

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertSame('{"error":"Json is invalid"}', $response->getContent());
    }

    public function testUpsertDexNotDefined(): void
    {
        $getTrainerPokedexService = $this->createMock(GetTrainerPokedexService::class);
        $getTrainerPokedexService
            ->expects($this->once())
            ->method('getPokedexData')
            ->with('douze', [])
            ->willReturn(
                $this->getStubAlbumEmpty(),
            )
        ;

        $modifyTrainerDexService = $this->createMock(ModifyTrainerDexService::class);
        $modifyTrainerDexService
            ->expects($this->never())
            ->method('modifyDex')
        ;

        $requestedContentService = $this->createMock(RequestedContentService::class);
        $requestedContentService
            ->expects($this->once())
            ->method('getContent')
            ->with(new Json())
            ->willReturn('{"key": "value"}')
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

        $controller = new TrainerUpsertController(
            $getTrainerPokedexService,
            $modifyTrainerDexService,
            $requestedContentService,
        );
        $controller->setContainer($container);

        $response = $controller->upsert('douze');

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertSame('[]', $response->getContent());
    }

    public function testUpsertNonPremiumDex(): void
    {
        $getTrainerPokedexService = $this->createMock(GetTrainerPokedexService::class);
        $getTrainerPokedexService
            ->expects($this->once())
            ->method('getPokedexData')
            ->with('douze', [])
            ->willReturn(
                new Album(
                    new Pokedex(
                        new Dex(
                            'douze',
                            '',
                            '',
                            '',
                            false,
                            false,
                            false,
                            '',
                            '',
                            '',
                            '',
                            '',
                            '0.0',
                            false,
                            false,
                            false,
                        ),
                        [],
                        new Report(null, null, null, []),
                        new Report(null, null, null, []),
                    ),
                    [],
                )
            )
        ;

        $modifyTrainerDexService = $this->createMock(ModifyTrainerDexService::class);
        $modifyTrainerDexService
            ->expects($this->once())
            ->method('modifyDex')
            ->with(
                'douze',
                '{"key": "value"}',
            )
        ;

        $requestedContentService = $this->createMock(RequestedContentService::class);
        $requestedContentService
            ->expects($this->once())
            ->method('getContent')
            ->with(new Json())
            ->willReturn('{"key": "value"}')
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

        $controller = new TrainerUpsertController(
            $getTrainerPokedexService,
            $modifyTrainerDexService,
            $requestedContentService,
        );
        $controller->setContainer($container);

        $response = $controller->upsert('douze');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEmpty($response->getContent());
    }

    public function testUpsertPremiumDexNotCollector(): void
    {
        $getTrainerPokedexService = $this->createMock(GetTrainerPokedexService::class);
        $getTrainerPokedexService
            ->expects($this->once())
            ->method('getPokedexData')
            ->with('douze', [])
            ->willReturn(
                new Album(
                    new Pokedex(
                        new Dex(
                            'douze',
                            '',
                            '',
                            '',
                            false,
                            false,
                            false,
                            '',
                            '',
                            '',
                            '',
                            '',
                            '0.0',
                            false,
                            true,
                            false,
                        ),
                        [],
                        new Report(null, null, null, []),
                        new Report(null, null, null, []),
                    ),
                    [],
                )
            )
        ;

        $modifyTrainerDexService = $this->createMock(ModifyTrainerDexService::class);
        $modifyTrainerDexService
            ->expects($this->never())
            ->method('modifyDex')
        ;

        $requestedContentService = $this->createMock(RequestedContentService::class);
        $requestedContentService
            ->expects($this->once())
            ->method('getContent')
            ->with(new Json())
            ->willReturn('{"key": "value"}')
        ;

        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_COLLECTOR')
            ->willReturn(false)
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

        $controller = new TrainerUpsertController(
            $getTrainerPokedexService,
            $modifyTrainerDexService,
            $requestedContentService,
        );
        $controller->setContainer($container);

        $response = $controller->upsert('douze');

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertSame('[]', $response->getContent());
    }

    public function testUpsertModifyFail(): void
    {
        $getTrainerPokedexService = $this->createMock(GetTrainerPokedexService::class);
        $getTrainerPokedexService
            ->expects($this->once())
            ->method('getPokedexData')
            ->with('douze', [])
            ->willReturn(
                new Album(
                    new Pokedex(
                        new Dex(
                            'douze',
                            '',
                            '',
                            '',
                            false,
                            false,
                            false,
                            '',
                            '',
                            '',
                            '',
                            '',
                            '0.0',
                            false,
                            true,
                            false,
                        ),
                        [],
                        new Report(null, null, null, []),
                        new Report(null, null, null, []),
                    ),
                    []
                )
            )
        ;

        $modifyTrainerDexService = $this->createMock(ModifyTrainerDexService::class);
        $modifyTrainerDexService
            ->expects($this->once())
            ->method('modifyDex')
            ->with(
                'douze',
                '{"key": "value"}',
            )
            ->willThrowException(new ModifyFailedException())
        ;

        $requestedContentService = $this->createMock(RequestedContentService::class);
        $requestedContentService
            ->expects($this->once())
            ->method('getContent')
            ->with(new Json())
            ->willReturn('{"key": "value"}')
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

        $controller = new TrainerUpsertController(
            $getTrainerPokedexService,
            $modifyTrainerDexService,
            $requestedContentService,
        );
        $controller->setContainer($container);

        $response = $controller->upsert('douze');

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertSame('{"error":"Fail to modify resources"}', $response->getContent());
    }
}
