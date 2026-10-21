<?php

declare(strict_types=1);

namespace App\Controller;

use App\ResponseObject\ColorGame\ColorGameState;
use App\Service\Back\ColorGameService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;

#[Route('/game/color')]
final class ColorGameController extends AbstractController
{
    private const string CSRF_TOKEN_ID = 'color_game';

    public function __construct(
        private readonly ColorGameService $colorGameService,
    ) {}

    #[Route('', name: 'app_colorgame_dex', methods: ['GET'])]
    public function dex(): Response
    {
        return $this->render(
            'ColorGame/dex.html.twig',
            [
                'dex' => $this->colorGameService->getDexList(),
                'games' => $this->colorGameService->getGames(),
            ]
        );
    }

    #[Route('', name: 'app_colorgame_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $this->assertCsrfTokenIsValid($request);

        try {
            $game = $this->colorGameService->create($request->request->getString('dex_slug'));
        } catch (HttpExceptionInterface) {
            $this->addFlash('warning', 'color_game.flash.create_failed');

            return $this->redirectToRoute('app_colorgame_dex');
        }

        return $this->redirectToRoute('app_colorgame_play', ['gameId' => $game->getId()]);
    }

    #[Route('/{gameId}', name: 'app_colorgame_play', requirements: ['gameId' => Requirement::UUID], methods: ['GET'])]
    public function play(string $gameId): Response
    {
        $state = $this->getState($gameId);

        return $this->render(
            'ColorGame/play.html.twig',
            [
                'game' => $state->getGame(),
                'round' => $state->getRound(),
                'answer' => null,
            ]
        );
    }

    #[Route('/{gameId}', name: 'app_colorgame_answer', requirements: ['gameId' => Requirement::UUID], methods: ['POST'])]
    public function answer(string $gameId, Request $request): Response
    {
        $this->assertCsrfTokenIsValid($request);

        $position = $request->request->getInt('position');
        $round = $this->getState($gameId)->getRound();

        // The round has already been answered (page reloaded, other tab…): show the current state
        if (null === $round || $round->getPosition() !== $position) {
            return $this->redirectToRoute('app_colorgame_play', ['gameId' => $gameId]);
        }

        try {
            $answer = $this->colorGameService->answer($gameId, $position, $request->request->getInt('choice'));
        } catch (HttpExceptionInterface) {
            return $this->redirectToRoute('app_colorgame_play', ['gameId' => $gameId]);
        }

        return $this->render(
            'ColorGame/play.html.twig',
            [
                'game' => $answer->getGame(),
                'round' => $round,
                'answer' => $answer,
            ]
        );
    }

    private function getState(string $gameId): ColorGameState
    {
        try {
            return $this->colorGameService->get($gameId);
        } catch (HttpExceptionInterface $e) {
            throw $this->createNotFoundException("Game {$gameId} not found", $e);
        }
    }

    private function assertCsrfTokenIsValid(Request $request): void
    {
        if (!$this->isCsrfTokenValid(self::CSRF_TOKEN_ID, $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
    }
}
