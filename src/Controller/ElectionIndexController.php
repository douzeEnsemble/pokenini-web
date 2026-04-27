<?php

declare(strict_types=1);

namespace App\Controller;

use App\AlbumFilters\FromRequest;
use App\AlbumFilters\Mapping;
use App\Service\ElectionIndexService;
use App\Service\GetLabelsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/election')]
final class ElectionIndexController extends AbstractController
{
    #[Route(
        '/{dexSlug}/{electionSlug}',
        requirements: [
            'dexSlug' => '[A-Za-z0-9]+(?:-[A-Za-z0-9]+)*',
            'electionSlug' => '[A-Za-z0-9]+(?:-[A-Za-z0-9]+)*',
        ],
        defaults: ['electionSlug' => ''],
        methods: ['GET']
    )]
    public function index(
        GetLabelsService $getLabelsService,
        ElectionIndexService $electionIndexService,
        Request $request,
        string $dexSlug,
        string $electionSlug = '',
    ): Response {
        $filters = FromRequest::get($request);
        $apiFilters = Mapping::get($filters);

        $data = $electionIndexService->get($dexSlug, $electionSlug, $apiFilters);

        if (null === $data) {
            throw $this->createNotFoundException('Election not found');
        }

        return $this->render(
            'Election/index.html.twig',
            [
                'listType' => $data->listType,
                'pokemons' => $data->pokemons,
                'pokedex' => $data->pokedex,
                'types' => $getLabelsService->getTypes(),
                'categoryForms' => $getLabelsService->getFormsCategory(),
                'regionalForms' => $getLabelsService->getFormsRegional(),
                'specialForms' => $getLabelsService->getFormsSpecial(),
                'variantForms' => $getLabelsService->getFormsVariant(),
                'gameBundles' => $getLabelsService->getGameBundles(),
                'collections' => $getLabelsService->getCollections(),
                'electionTop' => $data->electionTop,
                'metrics' => $data->metrics,
                'detachedCount' => $data->detachedCount,
                'isTheLastOne' => $data->isTheLastOne,
                'isTheLastPage' => $data->isTheLastPage,
            ]
        );
    }
}
