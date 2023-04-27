<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\NoLoggedUserException;
use App\Exception\ToJsonResponseException;
use App\Security\User;
use App\Security\UserTokenService;
use App\Service\ApiService;
use App\Validator\CatchStates;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[Route('/album')]
class AlbumController extends AbstractController
{
    public function __construct(
        private readonly ApiService $apiService,
        private readonly UserTokenService $userTokenService,
        private readonly ValidatorInterface $validator,
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
            $content = $this->getContentFromRequest($request);

            $trainerId = $this->userTokenService->getLoggedUserToken();

            $this->validateCatchState($content);
        } catch (ToJsonResponseException $e) {
            return new JsonResponse(
                [
                    'error' => $e->getMessage()
                ],
                $e->getCode()
            );
        }

        try {
            $this->apiService->modifyAlbum(
                $request->getMethod(),
                $dexSlug,
                $pokemonSlug,
                $content,
                $trainerId
            );

            $this->apiService->invalidateCacheAlbums();
        } catch (HttpExceptionInterface | TransportExceptionInterface $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }

        return new Response();
    }

    #[Route(
        '/{dexSlug}',
        name: 'app_album_index',
        requirements: [
            'dexSlug' => '[A-Za-z0-9]+(?:-[A-Za-z0-9]+)*',
        ],
        methods: ['GET']
    )]
    public function index(
        Request $request,
        string $dexSlug,
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

        /** @var User $user */
        $user = $this->getUser();

        if (!$dex['is_released'] && !$user->isAnAdmin()) {
            throw $this->createNotFoundException();
        }

        $catchStates = $this->apiService->getCatchStates();

        $filters = $this->getFilters($request);
        $pokemons = $this->pokemonsFilter($pokedex['pokemons'], $filters);

        return $this->render('Album/index.html.twig', [
            'currentDexSlug' => $dexSlug,
            'dex' => $dex,
            'report' => $pokedex['report'],
            'list' => $pokemons,
            'catchStates' => $catchStates,
            'mode' => 'read',
            'filters' => $filters,
            'trainerId' => $trainerId,
            'loggedTrainerId' => $loggedTrainerId,
            'queryTrainerId' => $queryTrainerId,
            'allowedToEdit' => $trainerId === $loggedTrainerId,
        ]);
    }

    /**
     * @param string[][] $pokemons
     * @param string[] $filters
     *
     * @return string[][]
     */
    private function pokemonsFilter(array $pokemons, array $filters): array
    {
        if (empty($filters)) {
            return $pokemons;
        }

        $list = $pokemons;
        if (!empty($filters['cs'])) {
            foreach ($list as $index => $pokemon) {
                if ($filters['cs'] !== ($pokemon['catch_state_slug'] ?? 'no')) {
                    unset($list[$index]);
                }
            }
        }

        if (!empty($filters['f'])) {
            foreach ($list as $index => $pokemon) {
                if ($filters['f'] !== ($pokemon['family_lead_slug'] ?? $pokemon['pokemon_slug'])) {
                    unset($list[$index]);
                }
            }
        }

        return $list;
    }

    /**
     * @return string[]
     */
    private function getFilters(Request $request): array
    {
        $filter = [];

        if ($request->query->has('cs')) {
            $filter['cs'] = $request->query->getAlpha('cs');
        }

        if ($request->query->has('f')) {
            $filter['f'] = (string) $request->query->get('f');
        }

        return $filter;
    }

    private function getContentFromRequest(Request $request): string
    {
        $content = $request->getContent();

        if (! is_string($content) || empty($content)) {
            throw new ToJsonResponseException(
                'Content must be a non-empty string',
                400
            );
        }

        return $content;
    }

    private function validateCatchState(string $catchState): void
    {
        $errors = $this->validator->validate(
            $catchState,
            new CatchStates()
        );

        if (!$errors->count()) {
            return;
        }

        throw new ToJsonResponseException(
            (string) $errors[0]?->getMessage(),
            400
        );
    }
}
