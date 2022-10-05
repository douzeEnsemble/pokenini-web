<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/istrateur')]
class AdminController extends AbstractController
{
    #[Route('/', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('Admin/index.html.twig');
    }
}
