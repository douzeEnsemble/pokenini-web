<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\ApiService;
use App\Service\CacheInvalidatorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/istration/action')]
class AdminActionController extends AbstractController
{
    public function __construct(
        private readonly CacheInvalidatorService $cacheInvalidatorService,
        private readonly ApiService $apiService,
    ) {
    }

    #[Route(
        '/update/{name}',
        methods: ['GET'],
        condition: "params['name']
            in [
                'labels',
                'games_and_dexes',
                'pokemons',
                'game_availability',
                'regional_dex_number',
            ]"
    )]
    public function update(
        string $name,
    ): Response {
        return $this->execute($name, 'update');
    }

    #[Route(
        '/calculate/{name}',
        methods: ['GET'],
        condition: "params['name']
            in [
                'game_bundle_availability',
                'dex_availability',
            ]"
    )]
    public function calculate(
        string $name,
    ): Response {
        return $this->execute($name, 'calculate');
    }

    #[Route(
        '/invalidate/{name}',
        methods: ['GET'],
        condition: "params['name']
            in [
                'catch_states',
                'dexes',
                'albums',
            ]"
    )]
    public function invalidate(
        string $name,
    ): Response {
        return $this->execute($name, 'invalidate');
    }

    private function execute(
        string $name,
        string $action,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $state = 'ok';

        try {
            $this->doAction($name, $action);
        } catch (\Exception $e) {
            $state = 'ko';

            $this->addFlash(
                "{$action}_error",
                $e->getMessage()
            );

            error_log($e->getMessage());
        }

        $this->addFlash('action', $action);
        $this->addFlash('item', $name);
        $this->addFlash('state', $state);

        return $this->redirectToRoute('app_admin_index');
    }

    private function doAction(
        string $name,
        string $action,
    ): void {
        switch ($action) {
            case 'update':
                $this->apiService->adminUpdate($name);
                break;
            case 'calculate':
                $this->apiService->adminCalculate($name);
                break;
            default:
                // nothing
                break;
        }

        $this->cacheInvalidatorService->invalidate($name);
    }
}
