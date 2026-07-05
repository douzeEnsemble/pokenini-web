<?php

declare(strict_types=1);

namespace App\Controller\Connect;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/connect/f')]
final class FakeController extends AbstractController
{
    // @codeCoverageIgnoreStart
    #[Route(
        '/c',
        methods: ['GET'],
        condition: "env('APP_ENV') in ['dev', 'test']"
    )]
    public function check(): void
    {
        // noting, all done by the authenticator
    }
    // @codeCoverageIgnoreEnd
}
