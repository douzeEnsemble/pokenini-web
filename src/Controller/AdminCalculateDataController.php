<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\AdminAction;
use App\Service\Back\GetActionLogsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/istration')]
final class AdminCalculateDataController extends AbstractController
{
    public function __construct(
        private readonly GetActionLogsService $getActionLogsService,
    ) {}

    #[Route('/calculate_data', methods: ['GET'], name: 'app_admin_calculate_data')]
    public function calculateData(RequestStack $requestStack): Response
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
            'Admin/calculate_data.html.twig',
            [
                'actionLogsData' => $this->getActionLogsService->get(),
            ]
        );
    }
}
