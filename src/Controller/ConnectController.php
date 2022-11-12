<?php

declare(strict_types=1);

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/connect')]
class ConnectController extends AbstractController
{
    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): void
    {
        // controller can be blank: it will never be called!
        throw new \Exception('Don\'t forget to activate logout in security.yaml');
    }

    #[Route('/', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('Connect/index.html.twig');
    }

    #[Route('/g', methods: ['GET'])]
    public function google(ClientRegistry $clientRegistry): Response
    {
        return $clientRegistry
            ->getClient('google')
            ->redirect(['openid'], [])
        ;
    }

    #[Route('/g/c', methods: ['GET'])]
    public function googleCheck(): void
    {
        // noting, all done by the authenticator
    }
}
