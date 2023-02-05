<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\NoLoggedUserException;
use App\Security\UserTokenService;
use App\Service\ApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[Route('/album')]
class AlbumController extends AbstractController
{
    public function __construct(
        private readonly ApiService $apiService,
        private readonly UserTokenService $userTokenService
    ) {
    }

    #[Route('/{dexSlug}/{pokemonSlug}', methods: ['PATCH', 'PUT'])]
    public function upsert(
        string $dexSlug,
        string $pokemonSlug,
        Request $request
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_TRAINER');

        try {
            $trainerId = $this->userTokenService->getLoggedUserToken();
            $this->apiService->modifyAlbum(
                $request->getMethod(),
                $dexSlug,
                $pokemonSlug,
                (string) $request->getContent(),
                $trainerId
            );

            $this->apiService->invalidateCacheAlbums();
        } catch (HttpExceptionInterface | TransportExceptionInterface $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }

        return new Response();
    }

    /**
     * @param string[][] $pokemons
     * @return string[][]
     */
    private function pokemonsFilter(array $pokemons, ?string $filter): array
    {
        if (empty($filter)) {
            return $pokemons;
        }

        $list = [];
        foreach ($pokemons as $pokemon) {
            if ($filter === ($pokemon['catch_state_slug'] ?? 'no')) {
                $list[] = $pokemon;
            }
        }

        return $list;
    }

    #[Route(
        '/{dexSlug}/{filter?}',
        name: 'app_album_index',
        requirements: [
            'dexSlug' => '[A-Za-z0-9]+(?:-[A-Za-z0-9]+)*',
            'filter' => '[A-Za-z0-9]+(?:-[A-Za-z0-9]+)*'
        ],
        methods: ['GET']
    )]
    public function index(
        Request $request,
        string $dexSlug,
        ?string $filter = null,
    ): Response {
        $loggedTrainerId = null;
        $queryTrainerId = $request->query->getAlnum('t');

        try {
            $loggedTrainerId = $this->userTokenService->getLoggedUserToken();

            $trainerId = !empty($queryTrainerId) ? $queryTrainerId : $loggedTrainerId;
        } catch (NoLoggedUserException $e) {
            $trainerId = $queryTrainerId;
        }

        if (empty($trainerId)) {
            throw $this->createNotFoundException();
        }

        try {
            $pokedex = $this->apiService->getPokedex($dexSlug, $trainerId);
        } catch (HttpExceptionInterface | TransportExceptionInterface $e) {
            throw $this->createNotFoundException();
        }

        $dex = $pokedex['dex'];

        if ($dex['is_private'] && $trainerId != $loggedTrainerId) {
            throw $this->createNotFoundException();
        }

        $catchStates = $this->apiService->getCatchStates();

        $pokemons = $this->pokemonsFilter($pokedex['pokemons'], $filter);

        return $this->render('Album/index.html.twig', [
            'currentDexSlug' => $dexSlug,
            'dex' => $dex,
            'report' => $pokedex['report'],
            'list' => $pokemons,
            'catchStates' => $catchStates,
            'mode' => 'read',
            'filter' => $filter,
            'trainerId' => $trainerId,
            'loggedTrainerId' => $loggedTrainerId,
            'queryTrainerId' => $queryTrainerId,
            'allowedToEdit' => $trainerId === $loggedTrainerId,
        ]);
    }
}
