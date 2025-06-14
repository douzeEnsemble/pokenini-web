<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\ElectionVoteController;
use App\Service\ElectionVoteService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @internal
 */
#[CoversClass(ElectionVoteController::class)]
class ElectionVoteControllerTest extends TestCase
{
    public function testVote(): void
    {
        $request = new Request([], ['winners_slugs' => ['pichu'], 'losers_slugs' => ['pikachu']]);

        $electionVoteService = $this->createMock(ElectionVoteService::class);
        $electionVoteService
            ->expects($this->once())
            ->method('vote')
        ;

        $controller = new ElectionVoteController();

        $router = $this->createMock(Router::class);
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

        $controller = new ElectionVoteController();

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Data cannot be empty');
        $controller->vote(
            $request,
            $electionVoteService,
            'demo',
            ''
        );
    }

    public function testVoteNonvalid(): void
    {
        $request = new Request([], ['winners_slugs' => ['pichu']]);

        $electionVoteService = $this->createMock(ElectionVoteService::class);

        $controller = new ElectionVoteController();

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('The required option "losers_slugs');
        $controller->vote(
            $request,
            $electionVoteService,
            'demo',
            ''
        );
    }
}
