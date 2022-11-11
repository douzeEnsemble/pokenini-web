<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/outerroom')]
class OuterRoomController extends AbstractController
{
    #[Route('/')]
    public function index(): Response
    {
        return $this->render(
            'OuterRoom/index.html.twig'
        );
    }
}
