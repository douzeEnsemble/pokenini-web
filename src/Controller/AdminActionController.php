<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\AdminAction;
use App\Service\ApiService;
use App\Service\CacheInvalidatorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/istration/action')]
class AdminActionController extends AbstractController
{
    public const SESSION_ACTION_DATA = 'admin.action.response.content';

    public function __construct(
        private readonly CacheInvalidatorService $cacheInvalidatorService,
        private readonly ApiService $apiService,
        private readonly RequestStack $requestStack,
    ) {
    }

    #[Route(
        '/update/{name}',
        methods: ['GET'],
        condition: "params['name']
            in [
                'labels',
                'games_and_dex',
                'pokemons',
                'games_availabilities',
                'regional_dex_numbers',
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
                'game_bundles_availabilities',
                'dex_availabilities',
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
                'dex',
                'albums',
                'reports',
            ]"
    )]
    public function invalidate(
        string $name,
    ): Response {
        return $this->execute($name, 'invalidate');
    }

    private function execute(
        string $name,
        string $action
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $state = 'ok';
        $content = '';
        $error = '';

        try {
            $content = $this->doAction($name, $action);
        } catch (\Exception $e) {
            $state = 'ko';

            $error = $e->getMessage();

            error_log($e->getMessage());
        }

        $adminAction = new AdminAction(
            $action,
            $name,
            $state,
            $content,
            $error
        );
        $this->requestStack->getSession()->set(self::SESSION_ACTION_DATA, $adminAction);

        return $this->redirectToRoute('app_admin_index');
    }

    private function doAction(
        string $name,
        string $action,
    ): string {
        $responseContent = '';

        switch ($action) {
            case 'update':
                $responseContent = $this->apiService->adminUpdate($name);
                break;
            case 'calculate':
                $responseContent = $this->apiService->adminCalculate($name);
                break;
            default:
                // nothing
                break;
        }

        $this->cacheInvalidatorService->invalidate($name);

        return $responseContent;
    }
}
