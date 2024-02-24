<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\ToJsonResponseException;
use App\Security\UserTokenService;
use App\Service\Api\ModifyAlbumService;
use App\Service\CacheInvalidator\AlbumsCacheInvalidatorService;
use App\Validator\CatchStates;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[Route('/album')]
class AlbumUpsertController extends AbstractController
{
    use ValidatorJsonResponseTrait;

    public function __construct(
        private readonly UserTokenService $userTokenService,
        private readonly ValidatorInterface $validator,
        private readonly ModifyAlbumService $modifyAlbumService,
        private readonly AlbumsCacheInvalidatorService $albumsCacheInvalidatorService,
    ) {
    }

    #[Route('/{dexSlug}/{pokemonSlug}', methods: ['PATCH', 'PUT'])]
    public function upsert(
        string $dexSlug,
        string $pokemonSlug,
        Request $request,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_TRAINER');

        try {
            $content = $this->getContentFromRequest($request);

            $trainerId = $this->userTokenService->getLoggedUserToken();

            $this->validate($content, new CatchStates());
        } catch (ToJsonResponseException $e) {
            return new JsonResponse(
                [
                    'error' => $e->getMessage()
                ],
                $e->getCode()
            );
        }

        try {
            $this->modifyAlbumService->modify(
                $request->getMethod(),
                $dexSlug,
                $pokemonSlug,
                $content,
                $trainerId
            );

            $this->albumsCacheInvalidatorService->invalidate();
        } catch (HttpExceptionInterface | TransportExceptionInterface $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }

        return new Response();
    }

    private function getContentFromRequest(Request $request): string
    {
        $content = $request->getContent();

        if (! is_string($content) || empty($content)) {
            throw new ToJsonResponseException(
                'Content must be a non-empty string',
                400
            );
        }

        return $content;
    }
}
