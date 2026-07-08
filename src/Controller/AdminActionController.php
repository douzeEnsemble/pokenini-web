<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\AdminAction;
use App\Event\AdminActionSucceededEvent;
use App\Service\Back\AdminActionService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[Route('/istration/action')]
final class AdminActionController extends AbstractController
{
    public const string SESSION_ACTION_DATA = 'admin.action.response.content';

    public function __construct(
        private readonly AdminActionService $adminActionService,
        private readonly RequestStack $requestStack,
        private readonly LoggerInterface $logger,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    #[Route(
        '/update/{name}',
        methods: ['POST'],
        condition: "params['name']
            in [
                'labels',
                'games_collections_and_dex',
                'pokemons',
                'games_availabilities',
                'games_shinies_availabilities',
                'regional_dex_numbers',
                'collections_availabilities',
            ]"
    )]
    public function update(
        string $name,
        Request $request,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('admin_update', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        return $this->execute($name, 'update', 'POST');
    }

    #[Route(
        '/calculate/{name}',
        methods: ['POST'],
        condition: "params['name']
            in [
                'game_bundles_availabilities',
                'game_bundles_shinies_availabilities',
                'collections_availabilities',
                'dex_availabilities',
                'pokemon_availabilities',
            ]"
    )]
    public function calculate(
        string $name,
        Request $request,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('admin_calculate', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        return $this->execute($name, 'calculate', 'POST');
    }

    #[Route(
        '/invalidate/{name}',
        methods: ['POST'],
        condition: "params['name']
            in [
                'labels',
                'dex',
                'albums',
                'reports',
                'actions',
            ]"
    )]
    public function invalidate(
        string $name,
        Request $request,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('admin_invalidate', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        return $this->execute($name, 'invalidate', 'DELETE');
    }

    private function execute(
        string $name,
        string $action,
        string $method,
    ): RedirectResponse {
        try {
            $adminAction = $this->adminActionService->execute($action, $name, $method);
        } catch (HttpExceptionInterface|TransportExceptionInterface $e) {
            $this->logger->critical(
                $e->getMessage(),
                [
                    'name' => $name,
                    'action' => $action,
                ]
            );

            $adminAction = new AdminAction(
                $action,
                $name,
                'ko',
                '',
                $e->getMessage(),
            );
        }

        if ('ok' === $adminAction->state) {
            $this->eventDispatcher->dispatch(new AdminActionSucceededEvent($name));
        }

        $this->requestStack->getSession()->set(self::SESSION_ACTION_DATA, $adminAction);

        return $this->redirectToRoute(
            'reports' === $name ? 'app_admin_reports' : 'app_admin_actions',
            [
                '_fragment' => "{$action}_{$name}",
            ]
        );
    }
}
