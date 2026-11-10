<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\Back\GetElectionDexListService;
use App\Service\LocaleService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/election')]
final class ElectionDexListController extends AbstractController
{
    #[Route('/dex', methods: ['GET'])]
    public function index(
        GetElectionDexListService $getDexService,
        LocaleService $localeService,
    ): Response {
        $dex = $getDexService->get($localeService->current());

        return $this->render(
            'Election/dex.html.twig',
            [
                'dex' => $dex,
            ]
        );
    }
}
