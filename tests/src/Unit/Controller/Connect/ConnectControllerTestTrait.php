<?php

namespace App\Tests\Unit\Controller\Connect;

use App\Controller\Connect\ConnectControllerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use Symfony\Component\HttpFoundation\Response;

trait ConnectControllerTestTrait
{
    public function assertGoto(
        ConnectControllerInterface $controller,
        string $scope,
        string $clientName,
    ): void {
        $client = $this->createMock(OAuth2ClientInterface::class);
        $client
            ->expects($this->once())
            ->method('redirect')
            ->with([$scope], [])
            ->willReturn(new Response())
        ;

        $clientRegistry = $this->createMock(ClientRegistry::class);
        $clientRegistry
            ->expects($this->once())
            ->method('getClient')
            ->with($clientName)
            ->willReturn($client)
        ;

        $controller->goto($clientRegistry);
    }
}
