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
    private const SHORT_MODE_READ = 'r';
    private const SHORT_MODE_WRITE = 'w';

    private const MODES_SHORT_LONG = [
        self::SHORT_MODE_READ => 'read',
        self::SHORT_MODE_WRITE => 'write',
    ];

    public function __construct(
        private readonly ApiService $apiService
    ) {
    }

    #[Route(
        '/{mode}/{dexSlug}/{filter?}',
        name: 'app_album_index',
        requirements: [
            'mode' => '[rw]',
            'dexSlug' => '[A-Za-z0-9]+(?:-[A-Za-z0-9]+)*',
            'filter' => '[A-Za-z0-9]+(?:-[A-Za-z0-9]+)*'
        ],
        methods: ['GET']
    )]
    public function index(
        Request $request,
        string $mode,
        string $dexSlug,
        ?string $filter = null,
    ): Response {
        if (self::SHORT_MODE_WRITE === $request->query->get('mode')) {
            $this->denyAccessUnlessGranted('ROLE_ADMIN');
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
            'mode' => self::MODES_SHORT_LONG[$mode],
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
