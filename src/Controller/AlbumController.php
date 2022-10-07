<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Traits\DexesRequestTrait;
use App\Service\ApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/album')]
class AlbumController extends AbstractController
{
    public function __construct(
        private readonly ApiService $apiService
    ) {
    }

    #[Route('/{dexSlug}', methods: ['GET'])]
    public function index(
        string $dexSlug,
        Request $request,
    ): Response {
        $mode = 'read';
        if ('edit' === $request->query->get('mode')) {
            $this->denyAccessUnlessGranted('ROLE_ADMIN');

            $mode = 'write';
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

        $this->apiService->modifyAlbum(
            $request->getMethod(),
            $dexSlug,
            $pokemonSlug,
            (string) $request->getContent()
        );

        $apiService->invalidateCacheAlbums();

        return new Response();
    }
}
