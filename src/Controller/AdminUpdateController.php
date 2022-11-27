<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\ApiService;
use App\Service\CacheInvalidatorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

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
        TranslatorInterface $translator,
        CacheInvalidatorService $cacheInvalidatorService
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $apiService->adminUpdate($name);

            $cacheInvalidatorService->invalidate($name);

            $this->addFlash(
                'success',
                $translator->trans("update.$name.success")
            );
        } catch (\Exception $e) {
            $this->addFlash(
                'danger',
                $translator->trans("update.$name.error") . ' ' . $e->getMessage()
            );
        }

        return $this->redirectToRoute('app_admin_index');
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
        TranslatorInterface $translator,
        CacheInvalidatorService $cacheInvalidatorService
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $apiService->adminCalculate($name);

            $cacheInvalidatorService->invalidate($name);

            $this->addFlash(
                'success',
                $translator->trans("calculate.$name.success")
            );
        } catch (\Exception $e) {
            $this->addFlash(
                'danger',
                $translator->trans("calculate.$name.error") . ' ' . $e->getMessage()
            );
        }

        return $this->redirectToRoute('app_admin_index');
    }
}
