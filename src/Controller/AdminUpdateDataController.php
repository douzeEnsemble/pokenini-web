<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\Back\GetActionLogsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/istration')]
final class AdminUpdateDataController extends AbstractController
{
    public function __construct(
        private readonly GetActionLogsService $getActionLogsService,
    ) {}

    #[Route('/update_data', methods: ['GET'], name: 'app_admin_update_data')]
    public function updateData(): Response
    {
        return $this->render(
            'Admin/update_data.html.twig',
            [
                'actionLogsData' => $this->getActionLogsService->get(),
            ]
        );
    }
}
