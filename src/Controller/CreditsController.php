<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\GetCreditsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CreditsController extends AbstractController
{
    #[Route('/credits')]
    public function index(GetCreditsService $service): Response
    {
        return $this->render(
            'Credits/index.html.twig',
            [
                'credits' => $service->get(),
            ]
        );
    }
}
