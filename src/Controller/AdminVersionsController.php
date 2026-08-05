<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\VersionsOverviewService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/istration')]
final class AdminVersionsController extends AbstractController
{
    public function __construct(
        private readonly VersionsOverviewService $versionsOverviewService,
    ) {}

    #[Route('/versions', methods: ['GET'], name: 'app_admin_versions')]
    public function versions(): Response
    {
        return $this->render(
            'Admin/versions.html.twig',
            [
                'versionsOverview' => $this->versionsOverviewService->get(),
            ]
        );
    }
}
