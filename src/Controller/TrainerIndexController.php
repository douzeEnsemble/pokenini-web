<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\DexFilters;
use App\DTO\DexFiltersRequest;
use App\ResponseObject\Album\DexListItem;
use App\Service\Back\GetTrainerDexListService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/trainer')]
final class TrainerIndexController extends AbstractController
{
    public function __construct(
        private readonly GetTrainerDexListService $getDexService,
    ) {}

    #[Route('')]
    #[IsGranted('ROLE_TRAINER')]
    public function index(Request $request): Response
    {
        $trainerDex = $this->getDexService->get();

        $filters = DexFiltersRequest::dexFiltersFromRequest($request);

        $filteredTrainerDex = $this->filtersTrainerDex($trainerDex, $filters);

        return $this->render(
            'Trainer/index.html.twig',
            [
                'trainerDex' => $filteredTrainerDex,
                'filters' => $filters,
            ]
        );
    }

    /**
     * @param DexListItem[] $trainerDex
     *
     * @return DexListItem[]
     */
    private function filtersTrainerDex(array $trainerDex, DexFilters $filters): array
    {
        $dex = $trainerDex;

        if (null !== $filters->privacy->value) {
            $dex = array_filter(
                $dex,
                function (DexListItem $item) use ($filters) {
                    return $filters->privacy->value === $item->getFlags()->isPrivate();
                }
            );
        }

        if (null !== $filters->homepaged->value) {
            $dex = array_filter(
                $dex,
                function (DexListItem $item) use ($filters) {
                    return $filters->homepaged->value === $item->getFlags()->isOnHome();
                }
            );
        }

        if (null !== $filters->shiny->value) {
            $dex = array_filter(
                $dex,
                function (DexListItem $item) use ($filters) {
                    return $filters->shiny->value === $item->getFlags()->isShiny();
                }
            );
        }

        if (null !== $filters->released->value) {
            $dex = array_filter(
                $dex,
                function (DexListItem $item) use ($filters) {
                    return $filters->released->value === $item->getFlags()->isReleased();
                }
            );
        }

        if (null !== $filters->premium->value) {
            $dex = array_filter(
                $dex,
                function (DexListItem $item) use ($filters) {
                    return $filters->premium->value === $item->getFlags()->isPremium();
                }
            );
        }

        return $dex;
    }
}
