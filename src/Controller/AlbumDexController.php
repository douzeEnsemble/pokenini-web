<?php

declare(strict_types=1);

namespace App\Controller;

use App\Security\UserTokenService;
use App\Service\Api\GetDexService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/album')]
class AlbumDexController extends AbstractController
{
    public function __construct() {}

    #[Route(
        '/dex',
        methods: ['GET']
    )]
    public function index(
        GetDexService $getDexService,
        UserTokenService $userTokenService,
    ): Response {
        $connectedUserId = $userTokenService->getLoggedUserToken();

        $dex = $this->isGranted('ROLE_ADMIN')
            ? $getDexService->getWithUnreleasedAndPremium($connectedUserId)
            : $getDexService->getWithPremium($connectedUserId);

        return $this->render(
            'AlbumDex/index.html.twig',
            [
                'dex' => $dex,
            ]
        );
    }
}
