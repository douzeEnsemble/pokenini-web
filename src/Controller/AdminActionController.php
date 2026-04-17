<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\AdminAction;
use App\Service\Back\AdminActionService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/istration/action')]
class AdminActionController extends AbstractController
{
    public const string SESSION_ACTION_DATA = 'admin.action.response.content';

    public function __construct(
        private readonly AdminActionService $adminActionService,
        private readonly RequestStack $requestStack,
        private readonly LoggerInterface $logger,
    ) {}

    #[Route(
        '/update/{name}',
        methods: ['GET'],
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
    ): RedirectResponse {
        return $this->execute($name, 'update');
    }

    #[Route(
        '/calculate/{name}',
        methods: ['GET'],
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
    ): RedirectResponse {
        return $this->execute($name, 'calculate');
    }

    #[Route(
        '/invalidate/{name}',
        methods: ['GET'],
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
    ): RedirectResponse {
        return $this->execute($name, 'invalidate');
    }

    private function execute(
        string $name,
        string $action,
    ): RedirectResponse {
        try {
            $adminAction = $this->adminActionService->execute($action, $name);
        } catch (\Exception $e) {
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

        $this->requestStack->getSession()->set(self::SESSION_ACTION_DATA, $adminAction);

        return $this->redirectToRoute(
            'app_admin_index',
            [
                '_fragment' => "{$action}_{$name}",
            ]
        );
    }
}
