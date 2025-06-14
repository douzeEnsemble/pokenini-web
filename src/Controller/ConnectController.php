<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/connect')]
class ConnectController extends AbstractController
{
    #[Route('/logout', methods: ['GET'])]
    public function logout(): void
    {
        throw new \Exception('Don\'t forget to activate logout in security.yaml');
    }

    #[Route('', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('Connect/index.html.twig');
    }
}
