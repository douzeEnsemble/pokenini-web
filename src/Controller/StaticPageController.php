<?php

declare(strict_types=1);

namespace App\Controller;

use App\Security\UserTokenService;
use App\Service\ApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StaticPageController extends AbstractController
{
    #[Route('/policy')]
    public function policy(): Response
    {
        return $this->render('Static/Policy/index.html.twig');
    }

    #[Route('/legals')]
    public function legals(): Response
    {
        return $this->render('Static/Legals/index.html.twig');
    }

    #[Route('/cookies')]
    public function cookies(): Response
    {
        return $this->render('Static/Cookies/index.html.twig');
    }
}
