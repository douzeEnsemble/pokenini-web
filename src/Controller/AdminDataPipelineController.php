<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\ActionPipelineService;
use App\Service\Back\GetActionLogsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/istration')]
final class AdminDataPipelineController extends AbstractController
{
    public function __construct(
        private readonly ActionPipelineService $pipelineService,
        private readonly GetActionLogsService $getActionLogsService,
    ) {}

    #[Route('/data_pipeline', methods: ['GET'], name: 'app_admin_data_pipeline')]
    public function dataPipeline(): Response
    {
        return $this->render(
            'Admin/data_pipeline.html.twig',
            [
                'steps' => $this->pipelineService->steps(),
                'actionLogsData' => $this->getActionLogsService->get(),
            ]
        );
    }
}
