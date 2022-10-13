<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\LastRoute;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/s')]
class SecurityController extends AbstractController
{
    #[Route('/l', methods: ['GET'])]
    public function login(RequestStack $requestStack): Response
    {
        /** @var ?LastRoute $lastRoute */
        $lastRoute = $requestStack->getSession()->get('last_route');

        if (null === $lastRoute) {
            return $this->redirectToRoute('app_home_index');
        }

        return $this->redirectToRoute($lastRoute->route, $lastRoute->routeParams);
    }
}
