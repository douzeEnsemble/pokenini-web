<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\ElectionVoteController;
use App\Exception\ModifyFailedException;
use App\Service\ElectionVoteService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 */
#[CoversClass(ElectionVoteController::class)]
final class ElectionVoteControllerTest extends TestCase
{
    public function testVote(): void
    {
        $request = new Request([], ['winners_slugs' => ['pichu'], 'losers_slugs' => ['pikachu']]);

        $electionVoteService = $this->createMock(ElectionVoteService::class);
        $electionVoteService
            ->expects($this->once())
            ->method('vote')
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->never())
            ->method('warning')
        ;

        $controller = new ElectionVoteController();

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->willReturn('/fr/election/demo')
        ;

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->exactly(1))
            ->method('get')
            ->willReturn($router)
        ;
        $controller->setContainer($container);

        /** @var RedirectResponse $response */
        $response = $controller->vote(
            $request,
            $electionVoteService,
            $logger,
            'demo',
            ''
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/fr/election/demo', $response->getTargetUrl());
    }

    public function testVoteEmpty(): void
    {
        $request = new Request();

        $electionVoteService = $this->createMock(ElectionVoteService::class);
        $electionVoteService
            ->expects($this->never())
            ->method('vote')
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->never())
            ->method('warning')
        ;

        $controller = new ElectionVoteController();

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessageIsOrContains('Data cannot be empty');
        $controller->vote(
            $request,
            $electionVoteService,
            $logger,
            'demo',
            ''
        );
    }

    public function testVoteWithModifyFailed(): void
    {
        $request = new Request([], ['winners_slugs' => ['pichu'], 'losers_slugs' => ['pikachu']]);

        $electionVoteService = $this->createMock(ElectionVoteService::class);
        $electionVoteService
            ->expects($this->once())
            ->method('vote')
            ->willThrowException(new ModifyFailedException('Oupsy'))
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                'Election vote failed',
                [
                    'exception' => 'Oupsy',
                ],
            )
        ;

        $controller = new ElectionVoteController();

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->willReturn('/fr/election/demo')
        ;

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->exactly(1))
            ->method('get')
            ->willReturn($router)
        ;
        $controller->setContainer($container);

        /** @var RedirectResponse $response */
        $response = $controller->vote(
            $request,
            $electionVoteService,
            $logger,
            'demo',
            ''
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/fr/election/demo', $response->getTargetUrl());
    }

    public function testVoteNonvalid(): void
    {
        $request = new Request([], ['winners_slugs' => ['pichu']]);

        $electionVoteService = $this->createMock(ElectionVoteService::class);
        $electionVoteService
            ->expects($this->never())
            ->method('vote')
        ;

        $logger = $this->createStub(LoggerInterface::class);

        $controller = new ElectionVoteController();

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessageIsOrContains('The required option "losers_slugs');
        $controller->vote(
            $request,
            $electionVoteService,
            $logger,
            'demo',
            ''
        );
    }
}
