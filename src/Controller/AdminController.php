<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\AdminAction;
use App\Service\ApiService;
use App\Utils\JsonDecoder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/istration')]
class AdminController extends AbstractController
{
    public function __construct(
        private readonly ApiService $apiService
    ) {
    }

    #[Route('', methods: ['GET'])]
    public function index(RequestStack $requestStack): Response
    {
        $session = $requestStack->getSession();

        /** @var AdminAction $adminAction */
        $adminAction = $session->get(AdminActionController::SESSION_ACTION_DATA);
        $session->remove(AdminActionController::SESSION_ACTION_DATA);

        $responseData = [];

        if (null !== $adminAction) {
            if ('' !== $adminAction->error) {
                $this->addFlash(
                    "{$adminAction->action}_error",
                    $adminAction->error
                );
            }

            $this->addFlash('action', $adminAction->action);
            $this->addFlash('item', $adminAction->item);
            $this->addFlash('state', $adminAction->state);

            if ('' !== $adminAction->content) {
                $responseData = JsonDecoder::decode($adminAction->content);
            }
        }

        $reportsData = $this->apiService->getReports();

        return $this->render(
            'Admin/index.html.twig',
            [
                'responseData' => $responseData,
                'reportsData' => $reportsData,
            ]
        );
    }
}
