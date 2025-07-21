<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\AdminAction;
use App\Service\Back\GetActionLogsService;
use App\Service\Back\GetReportsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/istration')]
class AdminController extends AbstractController
{
    public function __construct(
        private readonly GetReportsService $getReportsService,
        private readonly GetActionLogsService $getActionLogsService,
    ) {}

    #[Route('', methods: ['GET'])]
    public function index(RequestStack $requestStack): Response
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

        $reportsData = $this->getReportsService->get();
        $actionLogsData = $this->getActionLogsService->get();

        return $this->render(
            'Admin/index.html.twig',
            [
                'reportsData' => $reportsData,
                'actionLogsData' => $actionLogsData,
            ]
        );
    }
}
