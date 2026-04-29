<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Connect;

use App\Controller\Connect\ConnectControllerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use Symfony\Component\HttpFoundation\Response;

trait ConnectControllerTestTrait
{
    /**
     * @param array<string, string> $options
     */
    public function assertGoto(
        ConnectControllerInterface $controller,
        string $scope,
        string $clientName,
        array $options = [],
    ): void {
        $client = $this->createMock(OAuth2ClientInterface::class);
        $client
            ->expects($this->once())
            ->method('redirect')
            ->with(
                [
                    $scope,
                ],
                $options
            )
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
