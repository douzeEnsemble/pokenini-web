<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\ApiService;
use App\Service\CacheInvalidatorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/istration')]
class AdminUpdateController extends AbstractController
{
    #[Route(
        '/update/{name}',
        methods: ['GET'],
        condition: "params['name']
            in ['labels', 'games_and_dexes', 'pokemons', 'game_availability', 'regional_dex_number']"
    )]
    public function update(
        string $name,
        ApiService $apiService,
        CacheInvalidatorService $cacheInvalidatorService
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $state = 'ok';

        try {
            $apiService->adminUpdate($name);

            $cacheInvalidatorService->invalidate($name);
        } catch (\Exception $e) {
            $state = 'ko';

            $this->addFlash(
                'update_error',
                $e->getMessage()
            );

            error_log($e->getMessage());
        }

        return $this->redirectToRoute(
            'app_admin_index',
            [
                'item' => $name,
                'state' => $state,
            ]
        );
    }

    #[Route(
        '/calculate/{name}',
        methods: ['GET'],
        condition: "params['name']
            in ['game_bundle_availability', 'dex_availability']"
    )]
    public function calculate(
        string $name,
        ApiService $apiService,
        CacheInvalidatorService $cacheInvalidatorService
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $state = 'ok';

        try {
            $apiService->adminCalculate($name);

            $cacheInvalidatorService->invalidate($name);
        } catch (\Exception $e) {
            $state = 'ko';
            $this->addFlash(
                'calculate_error',
                $e->getMessage()
            );

            error_log($e->getMessage());
        }

        return $this->redirectToRoute(
            'app_admin_index',
            [
                'item' => $name,
                'state' => $state,
            ]
        );
    }
}
