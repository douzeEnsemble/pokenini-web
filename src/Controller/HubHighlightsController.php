<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\HubHighlightsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

/**
 * The parts of the hub loaded once the page is shown, so that the hub opens at once.
 *
 * Each one answers an HTML fragment, or nothing when there is nothing to show or it can't be read:
 * the hub then keeps the tile as it is.
 */
#[Route('/hub/highlights')]
final class HubHighlightsController extends AbstractController
{
    public function __construct(
        private readonly HubHighlightsService $hubHighlightsService,
    ) {}

    #[Route('/album', methods: ['GET'])]
    public function album(): Response
    {
        try {
            $albums = $this->hubHighlightsService->getAlbums();
        } catch (ExceptionInterface) {
            return new Response(status: Response::HTTP_NO_CONTENT);
        }

        return $this->render('Home/_highlights_album.html.twig', ['albums' => $albums]);
    }

    #[Route('/election', methods: ['GET'])]
    public function election(): Response
    {
        try {
            $elections = $this->hubHighlightsService->getElections();
        } catch (ExceptionInterface) {
            return new Response(status: Response::HTTP_NO_CONTENT);
        }

        if ([] === $elections) {
            return new Response(status: Response::HTTP_NO_CONTENT);
        }

        return $this->render('Home/_highlights_election.html.twig', ['elections' => $elections]);
    }

    #[Route('/game', methods: ['GET'])]
    public function game(): Response
    {
        try {
            $games = $this->hubHighlightsService->getGames();
        } catch (ExceptionInterface) {
            return new Response(status: Response::HTTP_NO_CONTENT);
        }

        return $this->render('Home/_highlights_game.html.twig', ['games' => $games]);
    }
}
