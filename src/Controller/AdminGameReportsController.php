<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\Back\GetGameReportsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/istration')]
final class AdminGameReportsController extends AbstractController
{
    public function __construct(
        private readonly GetGameReportsService $getGameReportsService,
    ) {}

    #[Route('/reports/games', methods: ['GET'], name: 'app_admin_game_reports')]
    public function gameReports(): Response
    {
        return $this->render(
            'Admin/game_reports.html.twig',
            [
                'gameReportsData' => $this->getGameReportsService->get(),
            ]
        );
    }
}
