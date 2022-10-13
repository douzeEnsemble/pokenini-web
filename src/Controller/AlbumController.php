<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Traits\DexesRequestTrait;
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
        private readonly ApiService $apiService
    ) {
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
        $mode = 'read';
        if ('edit' === $request->query->get('mode')) {
            $this->denyAccessUnlessGranted('ROLE_ADMIN');

            $mode = 'edit';
        }

        $pokedex = $this->apiService->getPokedex($dexSlug);
        $catchStates = $this->apiService->getCatchStates();
        $dexes = $this->apiService->getDexes();

        return $this->render('Album/index.html.twig', [
            'currentDexSlug' => $dexSlug,
            'dex' => $pokedex['dex'],
            'report' => $pokedex['report'],
            'list' => $pokedex['pokemons'],
            'catchStates' => $catchStates,
            'dexes' => $dexes,
            'mode' => $mode,
            'filter' => $filter,
        ]);
    }

    #[Route('/{dexSlug}/{pokemonSlug}', methods: ['PATCH', 'PUT'])]
    public function upsert(
        string $dexSlug,
        string $pokemonSlug,
        ApiService $apiService,
        Request $request
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $this->apiService->modifyAlbum(
                $request->getMethod(),
                $dexSlug,
                $pokemonSlug,
                (string)$request->getContent()
            );

            $apiService->invalidateCacheAlbums();
        } catch (HttpExceptionInterface | TransportExceptionInterface $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }

        return new Response();
    }
}
