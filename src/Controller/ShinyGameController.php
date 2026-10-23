<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\GameAnsweredRound;
use App\DTO\GameRoundCount;
use App\ResponseObject\ShinyGame\ShinyGameAnswer;
use App\ResponseObject\ShinyGame\ShinyGameState;
use App\Service\Back\ShinyGameService;
use App\Service\ShinyGameImageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;

#[Route('/game/shiny')]
final class ShinyGameController extends AbstractController
{
    private const string CSRF_TOKEN_ID = 'shiny_game';
    private const string ANSWER_SESSION_KEY = 'shiny_game/answered_round';

    public function __construct(
        private readonly ShinyGameService $shinyGameService,
        private readonly ShinyGameImageService $shinyGameImageService,
    ) {}

    #[Route('', name: 'app_shinygame_dex', methods: ['GET'])]
    public function dex(): Response
    {
        return $this->render(
            'ShinyGame/dex.html.twig',
            [
                'dex' => $this->shinyGameService->getDexList(),
                'games' => $this->shinyGameService->getGames(),
            ]
        );
    }

    #[Route('', name: 'app_shinygame_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $this->assertCsrfTokenIsValid($request);

        try {
            $game = $this->shinyGameService->create(
                $request->request->getString('dex_slug'),
                (new GameRoundCount($request->request->getString('round_count')))->value,
            );
        } catch (HttpExceptionInterface) {
            $this->addFlash('warning', 'shiny_game.flash.create_failed');

            return $this->redirectToRoute('app_shinygame_dex');
        }

        return $this->redirectToRoute('app_shinygame_play', ['gameId' => $game->getId()]);
    }

    #[Route('/{gameId}', name: 'app_shinygame_play', requirements: ['gameId' => Requirement::UUID], methods: ['GET'])]
    public function play(string $gameId): Response
    {
        $state = $this->getState($gameId);

        return $this->render(
            'ShinyGame/play.html.twig',
            [
                'game' => $state->getGame(),
                'round' => $state->getRound(),
                'answer' => null,
            ]
        );
    }

    #[Route('/{gameId}', name: 'app_shinygame_answer', requirements: ['gameId' => Requirement::UUID], methods: ['POST'])]
    public function answer(string $gameId, Request $request): Response
    {
        $this->assertCsrfTokenIsValid($request);

        $position = $request->request->getInt('position');
        $round = $this->getState($gameId)->getRound();

        // The round has already been answered (page reloaded, other tab…): show the current state
        if (null === $round || $round->getPosition() !== $position) {
            return $this->redirectToRoute('app_shinygame_play', ['gameId' => $gameId]);
        }

        try {
            $answer = $this->shinyGameService->answer($gameId, $position, $request->request->getBoolean('is_shiny'));
        } catch (HttpExceptionInterface) {
            return $this->redirectToRoute('app_shinygame_play', ['gameId' => $gameId]);
        }

        // Post/Redirect/Get: reloading the answer page must not send the answer again
        $request->getSession()->set(self::ANSWER_SESSION_KEY, new GameAnsweredRound($gameId, $position, $round, $answer));

        return $this->redirectToRoute('app_shinygame_answered', ['gameId' => $gameId, 'position' => $position]);
    }

    #[Route(
        '/{gameId}/rounds/{position}/answer',
        name: 'app_shinygame_answered',
        requirements: ['gameId' => Requirement::UUID, 'position' => Requirement::POSITIVE_INT],
        methods: ['GET'],
    )]
    public function answered(string $gameId, int $position, Request $request): Response
    {
        $answered = $request->getSession()->get(self::ANSWER_SESSION_KEY);

        // Only the last answer is kept: any other round shows the current state of the game
        if (!$answered instanceof GameAnsweredRound || !$answered->isFor($gameId, $position)) {
            return $this->redirectToRoute('app_shinygame_play', ['gameId' => $gameId]);
        }

        /** @var ShinyGameAnswer $answer */
        $answer = $answered->answer;

        return $this->render(
            'ShinyGame/play.html.twig',
            [
                'game' => $answer->getGame(),
                'round' => $answered->round,
                'answer' => $answer,
            ]
        );
    }

    /**
     * The image of the round to play, served without telling if it is the regular or the shiny one.
     */
    #[Route(
        '/{gameId}/rounds/{position}/image',
        name: 'app_shinygame_image',
        requirements: ['gameId' => Requirement::UUID, 'position' => Requirement::POSITIVE_INT],
        methods: ['GET'],
    )]
    public function image(string $gameId, int $position): Response
    {
        $state = $this->getState($gameId);
        $round = $state->getRound();

        if (null === $round || $round->getPosition() !== $position || 'in_progress' !== $state->getGame()->getStatus()) {
            throw $this->createNotFoundException("No round {$position} to play in game {$gameId}");
        }

        $image = $this->shinyGameImageService->get($round->getPokemon()->getIcon(), $round->isShiny());

        if (null === $image) {
            throw $this->createNotFoundException("No image for round {$position} of game {$gameId}");
        }

        return new Response(
            $image->getContent(),
            Response::HTTP_OK,
            [
                'Content-Type' => $image->getContentType(),
                'Cache-Control' => 'private, no-store',
            ],
        );
    }

    private function getState(string $gameId): ShinyGameState
    {
        try {
            return $this->shinyGameService->get($gameId);
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
