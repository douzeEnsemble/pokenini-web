<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/trainer')]
class TrainerController extends AbstractController
{
    #[Route('/')]
    public function index(): Response
    {
        return $this->render(
            'Trainer/index.html.twig'
        );
    }
}
