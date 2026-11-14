<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\Back\GetGameReportsService;
use App\Service\Back\GetReportsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/istration')]
final class AdminReportsController extends AbstractController
{
    public function __construct(
        private readonly GetReportsService $getReportsService,
        private readonly GetGameReportsService $getGameReportsService,
    ) {}

    #[Route('/reports', methods: ['GET'], name: 'app_admin_reports')]
    public function reports(): Response
    {
        return $this->render(
            'Admin/reports.html.twig',
            [
                'reportsData' => $this->getReportsService->get(),
                'gameReportsData' => $this->getGameReportsService->get(),
            ]
        );
    }
}
