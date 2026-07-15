<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\AdminAction;
use App\Service\Back\GetActionLogsService;
use App\Service\Back\GetImagePipelineStatusService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/istration')]
final class AdminController extends AbstractController
{
    public function __construct(
        private readonly GetActionLogsService $getActionLogsService,
        private readonly GetImagePipelineStatusService $getImagePipelineStatusService,
    ) {}

    #[Route('', methods: ['GET'], name: 'app_admin_index')]
    public function index(): RedirectResponse
    {
        return $this->redirectToRoute('app_admin_actions');
    }

    #[Route('/actions', methods: ['GET'], name: 'app_admin_actions')]
    public function actions(RequestStack $requestStack, Request $request): Response
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

        $actionLogsData = $this->getActionLogsService->get();
        $imagePipelineStatus = $this->getImagePipelineStatusService->get($request->query->has('refresh'));

        return $this->render(
            'Admin/actions.html.twig',
            [
                'actionLogsData' => $actionLogsData,
                'imagePipelineStatus' => $imagePipelineStatus,
            ]
        );
    }
}
