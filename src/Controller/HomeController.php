<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\NoLoggedUserException;
use App\Security\UserTokenService;
use App\Service\ApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class HomeController extends AbstractController
{
    #[Route('/')]
    public function index(ApiService $apiService, UserTokenService $userTokenService): Response
    {
        try {
            $userId = $userTokenService->getLoggedUserToken();

            $dexes = $apiService->getDexes($userId);
        } catch (NoLoggedUserException $e) {
            $dexes = [];
        }

        return $this->render(
            'Home/index.html.twig',
            [
                'dexes' => $dexes,
            ]
        );
    }
}
