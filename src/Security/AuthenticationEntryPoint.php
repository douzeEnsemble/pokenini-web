<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

final class AuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    private const PROVIDER_ROUTES = [
        'google' => 'app_connect_google_goto',
        'discord' => 'app_connect_discord_goto',
    ];

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
        $session = $request->getSession();

        /** @var null|string $provider */
        $provider = $session->get('_security_provider');

        if (is_string($provider) && array_key_exists($provider, self::PROVIDER_ROUTES)) {
            $session->set('_security_target_path', $request->getUri());

            return new RedirectResponse(
                $this->router->generate(self::PROVIDER_ROUTES[$provider]),
                Response::HTTP_TEMPORARY_REDIRECT
            );
        }

        return new RedirectResponse(
            $this->router->generate('app_home_index'),
            Response::HTTP_TEMPORARY_REDIRECT
        );
    }
}
