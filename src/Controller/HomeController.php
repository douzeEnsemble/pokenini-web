<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\ApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class HomeController extends AbstractController
{
    public function __construct(
        private readonly ApiService $apiService
    ) {
    }

    #[Route('/')]
    public function index(): Response
    {
        $dexes = $this->apiService->getDexes();

        return $this->render(
            'Home/index.html.twig',
            [
                'dexes' => $dexes,
            ]
        );
    }
}
