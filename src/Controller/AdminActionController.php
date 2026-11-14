<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\AdminAction;
use App\Event\AdminActionSucceededEvent;
use App\Service\Back\AdminActionService;
use App\Service\Back\GetActionCatalogService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[Route('/istration/action')]
final class AdminActionController extends AbstractController
{
    public const string ERROR_ALREADY_RUNNING = 'admin.action.already_running';

    /**
     * @var list<string>
     */
    private const array UPDATE_AVAILABILITIES_ITEMS = [
        'games_availabilities',
        'games_shinies_availabilities',
        'collections_availabilities',
    ];

    public function __construct(
        private readonly AdminActionService $adminActionService,
        private readonly LoggerInterface $logger,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly GetActionCatalogService $actionCatalog,
    ) {}

    #[Route(
        '/update/{name}',
        methods: ['POST'],
        requirements: ['name' => '[a-z_]+'],
    )]
    public function update(
        string $name,
        Request $request,
    ): RedirectResponse {
        $this->assertKnownAction('update', $name);

        if (!$this->isCsrfTokenValid('admin_update', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        return $this->execute($name, 'update', 'POST');
    }

    #[Route(
        '/calculate/{name}',
        methods: ['POST'],
        requirements: ['name' => '[a-z_]+'],
    )]
    public function calculate(
        string $name,
        Request $request,
    ): RedirectResponse {
        $this->assertKnownAction('calculate', $name);

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
                'catch_states',
                'types',
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

    #[Route(
        '/trigger/{name}',
        methods: ['POST'],
        condition: "params['name']
            in [
                'update_images',
                'update_banners',
            ]"
    )]
    public function trigger(
        string $name,
        Request $request,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('admin_trigger', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        return $this->execute($name, 'trigger', 'POST');
    }

    private function assertKnownAction(string $action, string $name): void
    {
        if (!$this->actionCatalog->has($action, $name)) {
            throw $this->createNotFoundException("Unknown action: {$action} {$name}");
        }
    }

    private function execute(
        string $name,
        string $action,
        string $method,
    ): RedirectResponse {
        try {
            $adminAction = $this->adminActionService->execute($action, $name, $method);
        } catch (HttpExceptionInterface|TransportExceptionInterface $e) {
            if ($e instanceof HttpExceptionInterface && Response::HTTP_CONFLICT === $e->getResponse()->getStatusCode()) {
                // The same action is already running: not an incident, only worth a clear message.
                $error = self::ERROR_ALREADY_RUNNING;
            } else {
                $error = $e->getMessage();

                $this->logger->critical(
                    $error,
                    [
                        'name' => $name,
                        'action' => $action,
                    ]
                );
            }

            $adminAction = new AdminAction(
                $action,
                $name,
                'ko',
                '',
                $error,
            );
        }

        if ('ok' === $adminAction->state) {
            $this->eventDispatcher->dispatch(new AdminActionSucceededEvent($name));
        }

        if ('' !== $adminAction->error) {
            $this->addFlash('error', $adminAction->error);
        }

        $this->addFlash('action', $adminAction->action);
        $this->addFlash('item', $adminAction->item);
        $this->addFlash('state', $adminAction->state);

        return $this->redirectToRoute(
            $this->resolveRedirectRoute($action, $name),
            [
                '_fragment' => "{$action}_{$name}",
            ]
        );
    }

    /**
     * Each action item now lives on its own dedicated admin page (see AdminUpdateDataController
     * and friends) instead of a shared tab-based "actions" page, so the redirect after a POST must
     * be resolved per action/name pair: 'reports' keeps its own special case (invalidating the
     * reports cache sends the trainer back to the reports page), 'update' items are split between
     * the update_data and update_availabilities pages, and the other action verbs each map to a
     * single dedicated page.
     */
    private function resolveRedirectRoute(string $action, string $name): string
    {
        if ('reports' === $name) {
            return 'app_admin_reports';
        }

        return match ($action) {
            'update' => \in_array($name, self::UPDATE_AVAILABILITIES_ITEMS, true)
                ? 'app_admin_update_availabilities'
                : 'app_admin_update_data',
            'calculate' => 'app_admin_calculate_data',
            'trigger' => 'app_admin_trigger_pipeline',
            default => 'app_admin_invalidate_data',
        };
    }
}
