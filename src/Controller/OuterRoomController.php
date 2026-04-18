<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/outerroom')]
final class OuterRoomController extends AbstractController
{
    #[Route('')]
    public function index(): Response
    {
        if ($this->isGranted('ROLE_TRAINER')) {
            return $this->redirectToRoute('app_home_index');
        }

        return $this->render(
            'OuterRoom/index.html.twig'
        );
    }
}
