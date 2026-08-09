<?php

declare(strict_types=1);

namespace App\Controller;

use App\AlbumFilters\FromRequest;
use App\Exception\NoLoggedUserException;
use App\ResponseObject\Album\Dex;
use App\Security\User;
use App\Security\UserTokenServiceInterface;
use App\Service\Back\GetTrainerDexListService;
use App\Service\GetLabelsService;
use App\Service\GetTrainerPokedexService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/album')]
final class AlbumIndexController extends AbstractController
{
    public function __construct(
        private readonly GetTrainerPokedexService $getTrainerPokedexService,
        private readonly GetLabelsService $getLabelsService,
        private readonly GetTrainerDexListService $getTrainerDexListService,
        private readonly UserTokenServiceInterface $userTokenService,
    ) {}

    #[Route(
        '/{dexSlug}',
        requirements: [
            'dexSlug' => '[A-Za-z0-9]+(?:-[A-Za-z0-9]+)*',
        ],
        methods: ['GET']
    )]
    public function index(
        string $dexSlug,
        Request $request,
    ): Response {
        $requestedTrainerId = $request->query->getAlnum('t', '');

        $filterBag = FromRequest::get($request);
        $apiFilters = $filterBag->toApiParams();

        $album = $this->getTrainerPokedexService->getPokedexData($dexSlug, $apiFilters, $requestedTrainerId);
        if (null === $album || null === $album->getPokedex()->getDex()) {
            throw $this->createNotFoundException();
        }

        $pokedex = $album->getPokedex();

        /**
         * @var Dex $dex
         */
        $dex = $pokedex->getDex();

        if (!$this->accessDexIsGranted($dex, $requestedTrainerId)) {
            throw $this->createNotFoundException();
        }

        $labels = $this->getLabelsService->getAllLabels();
        $pokemons = $pokedex->getPokemons();

        try {
            $loggedTrainerId = $this->userTokenService->getLoggedUserId();
        } catch (NoLoggedUserException) {
            $loggedTrainerId = null;
        }

        $allowedToEdit = $this->editDexIsGranted($dex, $requestedTrainerId);

        $trainerDexList = $allowedToEdit ? $this->getTrainerDexListService->get() : [];

        return $this->render('Album/index.html.twig', [
            'currentDexSlug' => $dexSlug,
            'dex' => $dex,
            'report' => $pokedex->getReport(),
            'filteredReport' => $pokedex->getFilteredReport(),
            'list' => $pokemons,
            'catchStates' => $labels->getCatchStates(),
            'types' => $labels->getTypes(),
            'forms' => $labels->getForms(),
            'gameBundles' => $labels->getGameBundles(),
            'collections' => $labels->getCollections(),
            'mode' => 'read',
            'filters' => $filterBag->toRouteParams(),
            'trainerId' => !empty($requestedTrainerId) ? $requestedTrainerId : $loggedTrainerId,
            'loggedTrainerId' => $loggedTrainerId,
            'requestedTrainerId' => $requestedTrainerId,
            'allowedToEdit' => $allowedToEdit,
            'trainerDexList' => $trainerDexList,
        ]);
    }

    private function accessDexIsGranted(Dex $dex, string $requestedTrainerId): bool
    {
        if ($dex->isPrivate()
            && '' !== $requestedTrainerId
            && !$this->userTokenService->compare($requestedTrainerId)
        ) {
            return false;
        }

        /** @var ?User $user */
        $user = $this->getUser();

        if (!$dex->isReleased() && !$user?->isAnAdmin()) {
            return false;
        }

        return true;
    }

    private function editDexIsGranted(Dex $dex, string $requestedTrainerId): bool
    {
        if ('' !== $requestedTrainerId
            && !$this->userTokenService->compare($requestedTrainerId)
        ) {
            return false;
        }

        /** @var ?User $user */
        $user = $this->getUser();

        if ($dex->isPremium() && !$user?->isACollector()) {
            return false;
        }

        return true;
    }
}
