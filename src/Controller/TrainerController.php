<?php

declare(strict_types=1);

namespace App\Controller;

use App\Security\UserTokenService;
use App\Service\ApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[Route('/trainer')]
class TrainerController extends AbstractController
{
    public function __construct(
        private readonly ApiService $apiService,
        private readonly UserTokenService $userTokenService
    ) {
    }

    #[Route('/')]
    public function index(): Response
    {
        $trainerDex = $this->apiService->getDex($this->userTokenService->getLoggedUserToken());

        return $this->render(
            'Trainer/index.html.twig',
            [
                'trainerDex' => $trainerDex,
            ]
        );
    }

    #[Route('/dex/{dexSlug}', methods: ['PUT'])]
    public function upsert(
        string $dexSlug,
        Request $request
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_TRAINER');

        try {
            $trainerId = $this->userTokenService->getLoggedUserToken();
            $this->apiService->modifyDex(
                $dexSlug,
                (string) $request->getContent(),
                $trainerId
            );

            $this->apiService->invalidateCacheAlbum($dexSlug, $trainerId);
            $this->apiService->invalidateCacheDexByTrainerId($trainerId);
        } catch (HttpExceptionInterface | TransportExceptionInterface $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }

        return new Response();
    }
}
