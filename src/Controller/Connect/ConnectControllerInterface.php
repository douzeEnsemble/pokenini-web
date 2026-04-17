<?php

declare(strict_types=1);

namespace App\Controller\Connect;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Component\HttpFoundation\Response;

interface ConnectControllerInterface
{
    public function goto(ClientRegistry $clientRegistry): Response;

    public function check(): void;
}
