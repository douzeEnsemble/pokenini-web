<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\ActionPipelineService;
use App\Service\Back\GetActionLogsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/istration')]
final class AdminUpdateAvailabilitiesController extends AbstractController
{
    public function __construct(
        private readonly ActionPipelineService $pipelineService,
        private readonly GetActionLogsService $getActionLogsService,
    ) {}

    #[Route('/update_availabilities', methods: ['GET'], name: 'app_admin_update_availabilities')]
    public function updateAvailabilities(): Response
    {
        return $this->render(
            'Admin/update_availabilities.html.twig',
            [
                'steps' => $this->pipelineService->stepsOf('update_availabilities'),
                'actionLogsData' => $this->getActionLogsService->get(),
            ]
        );
    }
}
