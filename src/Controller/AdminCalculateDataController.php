<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\Back\GetActionLogsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/istration')]
final class AdminCalculateDataController extends AbstractController
{
    public function __construct(
        private readonly GetActionLogsService $getActionLogsService,
    ) {}

    #[Route('/calculate_data', methods: ['GET'], name: 'app_admin_calculate_data')]
    public function calculateData(): Response
    {
        return $this->render(
            'Admin/calculate_data.html.twig',
            [
                'actionLogsData' => $this->getActionLogsService->get(),
            ]
        );
    }
}
