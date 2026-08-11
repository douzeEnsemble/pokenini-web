<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/trainer')]
final class TrainerPersonnalDataController extends AbstractController
{
    #[Route('/personnal_data', methods: ['GET'])]
    #[IsGranted('ROLE_TRAINER')]
    public function index(): Response
    {
        return $this->render('Trainer/personnal_data.html.twig');
    }
}
