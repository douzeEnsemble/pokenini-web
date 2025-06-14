<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\EmptyContentException;
use App\Exception\InvalidJsonException;
use App\Exception\ModifyFailedException;
use App\Service\GetTrainerPokedexService;
use App\Service\ModifyTrainerAlbumService;
use App\Service\RequestedContentService;
use App\Validator\CatchStates;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/album')]
class AlbumUpsertController extends AbstractController
{
    public function __construct(
        private readonly RequestedContentService $requestedContentService,
        private readonly GetTrainerPokedexService $getTrainerPokedexService,
        private readonly ModifyTrainerAlbumService $modifyTrainerAlbumService,
    ) {}

    #[Route('/{dexSlug}/{pokemonSlug}', methods: ['PATCH', 'PUT'])]
    #[IsGranted('ROLE_TRAINER')]
    public function upsert(
        string $dexSlug,
        string $pokemonSlug,
    ): Response {
        try {
            $content = $this->requestedContentService->getContent(new CatchStates());
        } catch (EmptyContentException|InvalidJsonException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }

        $pokedex = $this->getTrainerPokedexService->getPokedexData($dexSlug, []);
        if (null === $pokedex || empty($pokedex['dex'])) {
            return new JsonResponse([], 404);
        }

        $dex = $pokedex['dex'];

        if ($dex['is_premium'] && !$this->isGranted('ROLE_COLLECTOR')) {
            return new JsonResponse([], 404);
        }

        try {
            $this->modifyTrainerAlbumService->modifyAlbum(
                $dexSlug,
                $pokemonSlug,
                $content,
            );
        } catch (ModifyFailedException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }

        return new Response();
    }
}
