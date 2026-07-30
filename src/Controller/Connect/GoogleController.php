<?php

declare(strict_types=1);

namespace App\Controller\Connect;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/connect/g')]
final class GoogleController extends AbstractConnectController
{
    #[\Override]
    protected function getProviderName(): string
    {
        return 'google';
    }

    #[\Override]
    protected function getScope(): string
    {
        return 'openid';
    }

    #[\Override]
    protected function getExtraOptions(Request $request): array
    {
        // AuthenticationEntryPoint::start() stores '_security_target_path' in the session right
        // before redirecting here, but only when it's reacting to a missing/expired refresh
        // token (silent re-auth). AuthenticatorTrait::onAuthenticationSuccess() reads and
        // removes that key once auth succeeds. So its presence here means this redirect is part
        // of the silent-reauth flow and we must force Google to resend a refresh_token. Its
        // absence means a normal user-initiated login, where forcing 'prompt=consent' would
        // needlessly show the heavy re-consent screen.
        if ($request->getSession()->has('_security_target_path')) {
            return ['prompt' => 'consent'];
        }

        return [];
    }
}
