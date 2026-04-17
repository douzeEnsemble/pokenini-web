<?php

declare(strict_types=1);

namespace App\Controller;

use App\AlbumFilters\FromRequest;
use App\AlbumFilters\Mapping;
use App\Service\ElectionMetricsService;
use App\Service\ElectionTopService;
use App\Service\GetLabelsService;
use App\Service\GetPokemonsListService;
use App\Service\GetTrainerPokedexService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/election')]
class ElectionIndexController extends AbstractController
{
    #[Route(
        '/{dexSlug}/{electionSlug}',
        requirements: [
            'dexSlug' => '[A-Za-z0-9]+(?:-[A-Za-z0-9]+)*',
            'electionSlug' => '[A-Za-z0-9]+(?:-[A-Za-z0-9]+)*',
        ],
        methods: ['GET']
    )]
    public function index(
        GetPokemonsListService $getPokemonsListService,
        GetLabelsService $getLabelsService,
        ElectionTopService $electionTopService,
        ElectionMetricsService $metricsService,
        GetTrainerPokedexService $getTrainerPokedexService,
        Request $request,
        string $dexSlug,
        string $electionSlug = '',
    ): Response {
        $filters = FromRequest::get($request);
        $apiFilters = Mapping::get($filters);

        $electionTop = $electionTopService->getTop($dexSlug, $electionSlug);

        $list = $getPokemonsListService->get($dexSlug, $electionSlug, $apiFilters);
        $metrics = $metricsService->getMetrics($dexSlug, $electionSlug);
        $pokedex = $getTrainerPokedexService->getPokedexData($dexSlug, $apiFilters);

        $detachedCount = 0;
        foreach ($electionTop->getItems() as $pokemon) {
            if ($pokemon->isSignificance()) {
                ++$detachedCount;
            }
        }

        $isTheLastPage = 0 === $metrics->underMaxViewCount && $metrics->maxViewCount === count($list->getItems());
        $isTheLastOne = $isTheLastPage && 1 === $metrics->maxViewCount;

        $types = $getLabelsService->getTypes();
        $categoryForms = $getLabelsService->getFormsCategory();
        $regionalForms = $getLabelsService->getFormsRegional();
        $specialForms = $getLabelsService->getFormsSpecial();
        $variantForms = $getLabelsService->getFormsVariant();
        $variantForms = $getLabelsService->getFormsVariant();
        $gameBundles = $getLabelsService->getGameBundles();
        $collections = $getLabelsService->getCollections();

        return $this->render(
            'Election/index.html.twig',
            [
                'listType' => $list->getType(),
                'pokemons' => $list->getItems(),
                'pokedex' => $pokedex,
                'types' => $types,
                'categoryForms' => $categoryForms,
                'regionalForms' => $regionalForms,
                'specialForms' => $specialForms,
                'variantForms' => $variantForms,
                'gameBundles' => $gameBundles,
                'collections' => $collections,
                'electionTop' => $electionTop,
                'metrics' => $metrics,
                'detachedCount' => $detachedCount,
                'isTheLastOne' => $isTheLastOne,
                'isTheLastPage' => $isTheLastPage,
            ]
        );
    }
}
