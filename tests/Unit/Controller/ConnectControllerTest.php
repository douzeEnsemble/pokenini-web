<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\ConnectController;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class ConnectControllerTest extends TestCase
{
    public function testLogout(): void
    {
        $controller = new ConnectController();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Don't forget to activate logout in security.yaml");

        $controller->logout();
    }

    public function testGoogle(): void
    {
        $controller = new ConnectController();

        $client = $this->createMock(OAuth2ClientInterface::class);
        $client
            ->expects($this->once())
            ->method('redirect')
            ->with(['openid'], [])
            ->willReturn(new Response())
        ;

        $clientRegistry = $this->createMock(ClientRegistry::class);
        $clientRegistry
            ->expects($this->once())
            ->method('getClient')
            ->with('google')
            ->willReturn($client)
        ;

        $controller->google($clientRegistry);
    }
}
