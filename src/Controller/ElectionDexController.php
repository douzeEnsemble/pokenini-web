<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\Back\GetElectionDexService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/election')]
class ElectionDexController extends AbstractController
{
    #[Route('/dex', methods: ['GET'])]
    public function index(
        GetElectionDexService $getDexService,
    ): Response {
        $dex = $getDexService->get();

        return $this->render(
            'Election/dex.html.twig',
            [
                'dex' => $dex,
            ]
        );
    }
}
