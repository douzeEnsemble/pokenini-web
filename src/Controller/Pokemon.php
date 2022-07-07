<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/pokemon')]
class Pokemon extends AbstractController
{
    #[Route('/')]
    public function index(): Response
    {
        return $this->render('Pokemon/index.html.twig', []);
    }
}
