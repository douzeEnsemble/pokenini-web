<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Traits\DexesRequestTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/album')]
class AlbumController extends AbstractController
{
    use DexesRequestTrait;

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly string $appApiUrl
    ) {
    }

    #[Route('/{dexSlug}', methods: ['GET'])]
    public function index(
        string $dexSlug,
        Request $request,
    ): Response {
        $mode = 'read';
        if ('edit' === $request->query->get('mode')) {
            $this->denyAccessUnlessGranted('ROLE_ADMIN');

            $mode = 'write';
        }

        $pokedex = $this->getPokedex($dexSlug);
        $catchStates = $this->getCatchStates();
        $dexes = $this->getDexes();

        return $this->render('Album/index.html.twig', [
            'currentDexSlug' => $dexSlug,
            'dex' => $pokedex['dex'],
            'report' => $pokedex['report'],
            'list' => $pokedex['pokemons'],
            'catchStates' => $catchStates,
            'dexes' => $dexes,
            'mode' => $mode,
        ]);
    }

    #[Route('/{dexSlug}/{pokemonSlug}', methods: ['PATCH', 'PUT'])]
    public function upsert(string $dexSlug, string $pokemonSlug, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $this->client->request(
            $request->getMethod(),
            "{$this->appApiUrl}/album/$dexSlug/$pokemonSlug",
            [
                'body' => $request->getContent(),
            ]
        );

        return new Response();
    }

    /**
     * @return string[][]|string[][][]
     */
    private function getPokedex(string $dexSlug): array
    {
        $response = $this->client->request(
            'GET',
            "{$this->appApiUrl}/album/$dexSlug"
        );

        /** @var string[][]|string[][][] */
        return json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @return string[][]
     */
    private function getCatchStates(): array
    {
        $response = $this->client->request(
            'GET',
            "{$this->appApiUrl}/catch_states",
            [
                'headers' => [
                    'accept' => 'application/json',
                ],
            ]
        );

        /** @var string[][] */
        return json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }
}
