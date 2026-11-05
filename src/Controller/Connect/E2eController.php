<?php

declare(strict_types=1);

namespace App\Controller\Connect;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Entry point of the E2E tests sign-in, handled by App\Security\E2eAuthenticator.
 */
#[Route('/connect/e2e')]
final class E2eController extends AbstractController
{
    // @codeCoverageIgnoreStart
    #[Route('', name: 'app_connect_e2e_check', methods: ['POST'])]
    public function check(): void
    {
        // nothing, all done by the authenticator
    }
    // @codeCoverageIgnoreEnd
}
