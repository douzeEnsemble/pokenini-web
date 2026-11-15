<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/istration')]
final class AdminController extends AbstractController
{
    #[Route('', methods: ['GET'], name: 'app_admin_index')]
    public function index(): RedirectResponse
    {
        return $this->redirectToRoute('app_admin_update_data');
    }
}
