<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\Back\TrainerDexLinkService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;

#[Route('/album_link')]
final class TrainerDexLinkController extends AbstractController
{
    public function __construct(
        private readonly TrainerDexLinkService $service,
        private readonly SerializerInterface $serializer,
    ) {}

    #[Route('/{dexSlug}', methods: ['GET'])]
    #[IsGranted('ROLE_TRAINER')]
    public function list(string $dexSlug): Response
    {
        try {
            $links = $this->service->list($dexSlug);
        } catch (HttpExceptionInterface $e) {
            return new JsonResponse([], $e->getResponse()->getStatusCode());
        }

        $json = $this->serializer->serialize($links, 'json');

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/{dexSlug}', methods: ['POST'])]
    #[IsGranted('ROLE_TRAINER')]
    public function create(string $dexSlug, Request $request): Response
    {
        $content = $request->getContent();

        if (!$content) {
            return new JsonResponse([], Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->service->create($dexSlug, $content);
        } catch (HttpExceptionInterface $e) {
            return new JsonResponse([], $e->getResponse()->getStatusCode());
        }

        return new Response('', Response::HTTP_CREATED);
    }

    #[Route('/{linkId}', methods: ['DELETE'])]
    #[IsGranted('ROLE_TRAINER')]
    public function delete(string $linkId): Response
    {
        try {
            $this->service->delete($linkId);
        } catch (HttpExceptionInterface $e) {
            return new JsonResponse([], $e->getResponse()->getStatusCode());
        }

        return new Response();
    }
}
