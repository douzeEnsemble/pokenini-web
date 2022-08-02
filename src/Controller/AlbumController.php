<?php

declare(strict_types=1);

namespace App\Controller;

use App\Helper\PokeniniTokenHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/album')]
class AlbumController extends AbstractController
{
    public function __construct(private readonly HttpClientInterface $client)
    {
    }

    #[Route('/{dexSlug}', methods: ['GET'])]
    public function index(string $dexSlug, Request $request): Response
    {
        $mode = 'read';
        if ($request->query->get('token') === PokeniniTokenHelper::getFromDexSlug($dexSlug)) {
            $mode = 'write';
        }

        $pokedex = $this->getPokedex($dexSlug);

        $catchStates = $this->getCatchStates();

        return $this->render('Album/index.html.twig', [
            'dex' => $pokedex['dex'],
            'list' => $pokedex['pokemons'],
            'catchStates' => $catchStates,
            'mode' => $mode,
        ]);
    }

    #[Route('/{dexSlug}/{pokemonSlug}', methods: ['PATCH', 'PUT'])]
    public function upsert(string $dexSlug, string $pokemonSlug, Request $request): Response
    {
        if ($request->query->get('token') !== PokeniniTokenHelper::getFromDexSlug($dexSlug)) {
            return new Response('', Response::HTTP_FORBIDDEN);
        }

        $this->client->request(
            $request->getMethod(),
            "http://pkmn-lagd-api.local/album/$dexSlug/$pokemonSlug",
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
            "http://pkmn-lagd-api.local/album/$dexSlug"
        );

        /** @var string[][]|string[][][] */
        return json_decode($response->getContent(), true);
    }

    /**
     * @return string[][]
     */
    private function getCatchStates(): array
    {
        $response = $this->client->request(
            'GET',
            'http://pkmn-lagd-api.local/catch_states'
        );

        /** @var string[][] */
        return json_decode($response->getContent(), true);
    }
}
