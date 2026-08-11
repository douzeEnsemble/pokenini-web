<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\GetTrainerDexLinksTreeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/trainer')]
final class TrainerLinksController extends AbstractController
{
    public function __construct(
        private readonly GetTrainerDexLinksTreeService $getTrainerDexLinksTreeService,
    ) {}

    #[Route('/links', methods: ['GET'])]
    #[IsGranted('ROLE_TRAINER')]
    public function index(): Response
    {
        return $this->render(
            'Trainer/links.html.twig',
            [
                'linksTree' => $this->getTrainerDexLinksTreeService->getTree(),
            ]
        );
    }
}
