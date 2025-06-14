<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

trait AuthenticatorTrait
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
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
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        return new Response($message, Response::HTTP_FORBIDDEN);
    }

    private function loadUserFromLists(string $identifier, string $providerName): User
    {
        $user = new User($identifier, $providerName);

        $listAdmins = explode(',', $this->listAdmin);
        $listAdmins = array_map(fn ($value) => trim($value), $listAdmins);
        $listTrainers = explode(',', $this->listTrainer);
        $listTrainers = array_map(fn ($value) => trim($value), $listTrainers);
        $listCollectors = explode(',', $this->listCollector);
        $listCollectors = array_map(fn ($value) => trim($value), $listCollectors);

        if (in_array($user->getUserIdentifier(), $listAdmins)) {
            $user->addAdminRole();
        }
        if (in_array($user->getUserIdentifier(), $listCollectors)) {
            $user->addCollectorRole();
        }
        if (!$this->isInvitationRequired || in_array($user->getUserIdentifier(), $listTrainers)) {
            $user->addTrainerRole();
        }

        return $user;
    }
}
