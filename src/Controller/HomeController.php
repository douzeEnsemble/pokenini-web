<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\NoLoggedUserException;
use App\Security\UserTokenServiceInterface;
use App\Service\Back\GetAlbumDexListService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('')]
    public function index(
        UserTokenServiceInterface $userTokenService,
        GetAlbumDexListService $service,
        string $demoUserId,
    ): Response {
        try {
            $userTokenService->getLoggedUserId();

            return new RedirectResponse($this->generateUrl('app_albumdexlist_index'));
        } catch (NoLoggedUserException) {
            // nothing to do
        }

        $dex = $service->get($demoUserId);

        return $this->render(
            'Home/index.html.twig',
            [
                'dex' => $dex,
                'demoUserId' => $demoUserId,
            ]
        );
    }
}
