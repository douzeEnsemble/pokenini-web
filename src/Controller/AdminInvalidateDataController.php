<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\Back\GetActionLogsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/istration')]
final class AdminInvalidateDataController extends AbstractController
{
    public function __construct(
        private readonly GetActionLogsService $getActionLogsService,
    ) {}

    #[Route('/invalidate_data', methods: ['GET'], name: 'app_admin_invalidate_data')]
    public function invalidateData(): Response
    {
        return $this->render(
            'Admin/invalidate_data.html.twig',
            [
                'actionLogsData' => $this->getActionLogsService->get(),
            ]
        );
    }
}
