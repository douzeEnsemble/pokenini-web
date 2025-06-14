<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\Api\GetElectionDexService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/election')]
class ElectionDexController extends AbstractController
{
    #[Route('/dex', methods: ['GET'])]
    public function index(
        GetElectionDexService $getDexService,
    ): Response {
        switch (true) {
            case $this->isGranted('ROLE_ADMIN'):
                $dex = $getDexService->getWithUnreleasedAndPremium();

                break;

            case $this->isGranted('ROLE_COLLECTOR'):
                $dex = $getDexService->getWithPremium();

                break;

            default:
                $dex = $getDexService->get();

                break;
        }

        return $this->render(
            'Election/dex.html.twig',
            [
                'dex' => $dex,
            ]
        );
    }
}
