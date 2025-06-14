<?php

namespace App\Controller\Connect;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

abstract class AbstractConnectController extends AbstractController implements ConnectControllerInterface
{
    #[Route('', methods: ['GET'])]
    #[\Override]
    public function goto(ClientRegistry $clientRegistry): Response
    {
        return $clientRegistry
            ->getClient($this->getProviderName())
            ->redirect([$this->getscope()], [])
        ;
    }

    // @codeCoverageIgnoreStart
    #[Route('/c', methods: ['GET'])]
    #[\Override]
    public function check(): void
    {
        // noting, all done by the authenticator
    }
    // @codeCoverageIgnoreEnd

    abstract protected function getProviderName(): string;

    abstract protected function getscope(): string;
}
