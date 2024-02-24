<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\NoLoggedUserException;
use App\Security\UserTokenService;
use App\Service\Api\GetDexService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class HomeController extends AbstractController
{
    #[Route('')]
    public function index(
        GetDexService $getDexService,
        UserTokenService $userTokenService,
        string $demoUserId,
    ): Response {
        $connectedUserId = null;

        try {
            $userId = $connectedUserId = $userTokenService->getLoggedUserToken();
        } catch (NoLoggedUserException $e) {
            $userId = $demoUserId;
        }

        $dex = $getDexService->get($userId);

        return $this->render(
            'Home/index.html.twig',
            [
                'dex' => $dex,
                'userId' => $userId,
                'connectedUserId' => $connectedUserId,
            ]
        );
    }
}
