<?php

declare(strict_types=1);

namespace App\Controller;

use App\AlbumFilters\FromRequest;
use App\AlbumFilters\Mapping;
use App\Security\User;
use App\Service\Api\GetLabelsService;
use App\Service\GetTrainerPokedexService;
use App\Service\TrainerIdsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/album')]
class AlbumIndexController extends AbstractController
{
    public function __construct(
        private readonly TrainerIdsService $trainerIdsService,
        private readonly GetTrainerPokedexService $getTrainerPokedexService,
        private readonly GetLabelsService $getLabelsService,
    ) {}

    #[Route(
        '/{dexSlug}',
        requirements: [
            'dexSlug' => '[A-Za-z0-9]+(?:-[A-Za-z0-9]+)*',
        ],
        methods: ['GET']
    )]
    public function index(
        Request $request,
        string $dexSlug,
    ): Response {
        $this->trainerIdsService->init();

        $trainerId = $this->trainerIdsService->getTrainerId();

        if (!$trainerId) {
            throw $this->createNotFoundException();
        }

        $filters = FromRequest::get($request);
        $apiFilters = Mapping::get($filters);

        $pokedex = $this->getTrainerPokedexService->getPokedexDataByTrainerId($dexSlug, $apiFilters, $trainerId);
        if (null === $pokedex || !isset($pokedex['dex']) || empty($pokedex['dex'])) {
            throw $this->createNotFoundException();
        }

        $dex = $pokedex['dex'];
        if (!$this->accessDexIsGranted($dex)) {
            throw $this->createNotFoundException();
        }

        $catchStates = $this->getLabelsService->getCatchStates();
        $types = $this->getLabelsService->getTypes();
        $categoryForms = $this->getLabelsService->getFormsCategory();
        $regionalForms = $this->getLabelsService->getFormsRegional();
        $specialForms = $this->getLabelsService->getFormsSpecial();
        $variantForms = $this->getLabelsService->getFormsVariant();
        $variantForms = $this->getLabelsService->getFormsVariant();
        $gameBundles = $this->getLabelsService->getGameBundles();
        $collections = $this->getLabelsService->getCollections();

        $pokemons = $pokedex['pokemons'];

        $loggedTrainerId = $this->trainerIdsService->getLoggedTrainerId();

        return $this->render('Album/index.html.twig', [
            'currentDexSlug' => $dexSlug,
            'dex' => $dex,
            'report' => $pokedex['report'],
            'filteredReport' => $pokedex['filteredReport'],
            'list' => $pokemons,
            'catchStates' => $catchStates,
            'types' => $types,
            'categoryForms' => $categoryForms,
            'regionalForms' => $regionalForms,
            'specialForms' => $specialForms,
            'variantForms' => $variantForms,
            'gameBundles' => $gameBundles,
            'collections' => $collections,
            'mode' => 'read',
            'filters' => $filters,
            'trainerId' => $trainerId,
            'loggedTrainerId' => $loggedTrainerId,
            'requestedTrainerId' => $this->trainerIdsService->getRequestedTrainerId(),
            'allowedToEdit' => $this->editDexIsGranted($dex),
        ]);
    }

    /**
     * @param string[]|string[][] $dex
     */
    private function accessDexIsGranted(array $dex): bool
    {
        if ($dex['is_private']
            && $this->trainerIdsService->getTrainerId() != $this->trainerIdsService->getLoggedTrainerId()
        ) {
            return false;
        }

        /** @var User $user */
        $user = $this->getUser();

        if (!$dex['is_released'] && !$user->isAnAdmin()) {
            return false;
        }

        return true;
    }

    /**
     * @param string[]|string[][] $dex
     */
    private function editDexIsGranted(array $dex): bool
    {
        $trainerId = $this->trainerIdsService->getTrainerId();
        $loggedTrainerId = $this->trainerIdsService->getLoggedTrainerId();

        if ($trainerId !== $loggedTrainerId) {
            return false;
        }

        /** @var User $user */
        $user = $this->getUser();

        if ($dex['is_premium'] && !$user->isACollector()) {
            return false;
        }

        return true;
    }
}
