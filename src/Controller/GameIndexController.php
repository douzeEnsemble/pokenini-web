<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/game')]
final class GameIndexController extends AbstractController
{
    #[Route('', name: 'app_game_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('Game/index.html.twig');
    }
}
