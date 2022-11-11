<?php

declare(strict_types=1);

namespace App\Security;

use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class GoogleAuthenticator extends OAuth2Authenticator implements AuthenticationEntrypointInterface
{
    public function __construct(
        private readonly ClientRegistry $clientRegistry,
        private readonly RouterInterface $router,
        private readonly string $listAdmin,
        private readonly string $listTrainer,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'app_connect_googlecheck';
    }

    /**
     * @inheritDoc
     */
    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client) {
                /** @var GoogleUser $authUser */
                $authUser = $client->fetchUserFromToken($accessToken);

                $user = new User(strval($authUser->getId()));

                if (in_array($user->getUserIdentifier(), explode(',', $this->listAdmin))) {
                    $user->addAdminRole();
                }
                if (in_array($user->getUserIdentifier(), explode(',', $this->listTrainer))) {
                    $user->addTrainerRole();
                }

                return $user;
            })
        );
    }

    /**
     * @inheritDoc
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        /** @var User $user */
        $user = $token->getUser();

        $targetUrl = $this->router->generate('app_outerroom_index');
        if ($user->isATrainer()) {
            $targetUrl = $this->router->generate('app_home_index');
        }

        return new RedirectResponse($targetUrl);
    }

    /**
     * @inheritDoc
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        return new Response($message, Response::HTTP_FORBIDDEN);
    }

   /**
     * Called when authentication is needed, but it's not sent.
     * This redirects to the 'login'.
     *
     * @inheritDoc
     */
    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new RedirectResponse(
            $this->router->generate('app_home_index'),
            Response::HTTP_TEMPORARY_REDIRECT
        );
    }
}
