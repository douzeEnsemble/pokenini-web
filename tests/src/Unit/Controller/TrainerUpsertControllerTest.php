<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\TrainerUpsertController;
use App\Exception\EmptyContentException;
use App\Exception\InvalidJsonException;
use App\Exception\ModifyFailedException;
use App\Service\GetTrainerPokedexService;
use App\Service\ModifyTrainerDexService;
use App\Service\RequestedContentService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraints\Json;

/**
 * @internal
 */
#[CoversClass(TrainerUpsertController::class)]
class TrainerUpsertControllerTest extends TestCase
{
    public function testUpsert(): void
    {
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

        $this->assertInstanceOf(Response::class, $response);
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

    public function testUpsertPokedexNull(): void
    {
        $getTrainerPokedexService = $this->createMock(GetTrainerPokedexService::class);
        $getTrainerPokedexService
            ->expects($this->once())
            ->method('getPokedexData')
            ->with('douze', [])
            ->willReturn(null)
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

    public function testUpsertDexNotDefined(): void
    {
        $getTrainerPokedexService = $this->createMock(GetTrainerPokedexService::class);
        $getTrainerPokedexService
            ->expects($this->once())
            ->method('getPokedexData')
            ->with('douze', [])
            ->willReturn([
                'pokemons' => [],
            ])
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
            ->willReturn([
                'dex' => [
                    'slug' => 'douze',
                    'is_premium' => false,
                ],
                'pokemons' => [],
            ])
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
            ->willReturn([
                'dex' => [
                    'slug' => 'douze',
                    'is_premium' => true,
                ],
                'pokemons' => [],
            ])
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
            ->willReturn([
                'dex' => [
                    'slug' => 'douze',
                    'is_premium' => true,
                ],
                'pokemons' => [],
            ])
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
