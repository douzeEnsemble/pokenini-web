<?php

namespace App\Service;

use App\Exception\ModifyFailedException;
use App\Service\Back\ModifyAlbumService;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class ModifyTrainerAlbumService
{
    public function __construct(
        private readonly ModifyAlbumService $modifyAlbumService,
        private readonly RequestStack $requestStack,
    ) {}

    public function modifyAlbum(
        string $dexSlug,
        string $pokemonSlug,
        string $content,
    ): void {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            throw new ModifyFailedException();
        }

        try {
            $this->modifyAlbumService->modify(
                $request->getMethod(),
                $dexSlug,
                $pokemonSlug,
                $content,
            );
        } catch (HttpExceptionInterface|TransportExceptionInterface $e) {
            throw new ModifyFailedException();
        }
    }
}
