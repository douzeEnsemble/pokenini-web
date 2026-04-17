<?php

declare(strict_types=1);

namespace App\Security;

use League\OAuth2\Client\Token\AccessToken;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

trait AuthenticatorTrait
{
    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?RedirectResponse
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
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        return new Response($message, Response::HTTP_FORBIDDEN);
    }

    private function loadFromAccessToken(AccessToken $accessToken, string $providerName): User
    {
        $userInfo = $this->getUserInfoService->get($accessToken, $providerName);

        $user = new User($userInfo->getId(), $providerName, $accessToken);

        $roles = $userInfo->getRoles();

        if (in_array('ROLE_TRAINER', $roles)) {
            $user->addTrainerRole();
        }

        if (in_array('ROLE_COLLECTOR', $roles)) {
            $user->addCollectorRole();
        }

        if (in_array('ROLE_ADMIN', $roles)) {
            $user->addAdminRole();
        }

        return $user;
    }
}
