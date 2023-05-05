<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\ToJsonResponseException;
use App\Security\User;
use App\Security\UserTokenService;
use App\Service\ApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Json;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[Route('/trainer')]
class TrainerController extends AbstractController
{
    use ValidatorJsonResponseTrait;

    public function __construct(
        private readonly ApiService $apiService,
        private readonly UserTokenService $userTokenService,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('')]
    public function index(): Response
    {
        /** @var ?User $user */
        $user = $this->getUser();

        if (null === $user) {
            return new Response('', Response::HTTP_UNAUTHORIZED);
        }

        $userToken = $this->userTokenService->getLoggedUserToken();

        $trainerDex = $user->isAnAdmin()
            ? $this->apiService->getDexWithUnreleased($userToken)
            : $this->apiService->getDex($userToken);

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
        Request $request,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_TRAINER');

        try {
            $content = $this->getContentFromRequest($request);

            $trainerId = $this->userTokenService->getLoggedUserToken();

            $this->validate($content, new Json());
        } catch (ToJsonResponseException $e) {
            return new JsonResponse(
                [
                    'error' => $e->getMessage()
                ],
                $e->getCode()
            );
        }

        try {
            $this->apiService->modifyDex(
                $dexSlug,
                $content,
                $trainerId
            );

            $this->apiService->invalidateCacheAlbum($dexSlug, $trainerId);
            $this->apiService->invalidateCacheDexByTrainerId($trainerId);
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
