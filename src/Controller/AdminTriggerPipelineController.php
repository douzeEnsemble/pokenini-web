<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\AdminAction;
use App\Service\Back\GetActionLogsService;
use App\Service\Back\GetBannerPipelineStatusService;
use App\Service\Back\GetImagePipelineStatusService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/istration')]
final class AdminTriggerPipelineController extends AbstractController
{
    public function __construct(
        private readonly GetActionLogsService $getActionLogsService,
        private readonly GetImagePipelineStatusService $getImagePipelineStatusService,
        private readonly GetBannerPipelineStatusService $getBannerPipelineStatusService,
    ) {}

    #[Route('/trigger_pipeline', methods: ['GET'], name: 'app_admin_trigger_pipeline')]
    public function triggerPipeline(RequestStack $requestStack, Request $request): Response
    {
        $session = $requestStack->getSession();

        /** @var ?AdminAction $adminAction */
        $adminAction = $session->get(AdminActionController::SESSION_ACTION_DATA);
        $session->remove(AdminActionController::SESSION_ACTION_DATA);

        if (null !== $adminAction) {
            if ('' !== $adminAction->error) {
                $this->addFlash('error', $adminAction->error);
            }

            $this->addFlash('action', $adminAction->action);
            $this->addFlash('item', $adminAction->item);
            $this->addFlash('state', $adminAction->state);
        }

        return $this->render(
            'Admin/trigger_pipeline.html.twig',
            [
                'actionLogsData' => $this->getActionLogsService->get(),
                'imagePipelineStatus' => $this->getImagePipelineStatusService->get($request->query->has('refresh_images')),
                'bannerPipelineStatus' => $this->getBannerPipelineStatusService->get($request->query->has('refresh_banners')),
            ]
        );
    }
}
