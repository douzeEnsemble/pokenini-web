<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class AuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    public function __construct(
        private readonly RouterInterface $router,
    ) {}

    /**
     * Called when authentication is needed, but it's not sent.
     * This redirects to the 'login'.
     *
     * {@inheritDoc}
     */
    #[\Override]
    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new RedirectResponse(
            $this->router->generate('app_home_index'),
            Response::HTTP_TEMPORARY_REDIRECT
        );
    }
}
