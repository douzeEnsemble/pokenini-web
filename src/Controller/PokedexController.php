<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/pokedex')]
class PokedexController extends AbstractController
{
    public function __construct(private readonly HttpClientInterface $client)
    {
    }

    #[Route('/{dexSlug}')]
    public function index(string $dexSlug): Response
    {
        $pokedex = $this->getPokedex($dexSlug);

        $catchStates = $this->getCatchStates();

        return $this->render('Pokedex/index.html.twig', [
            'dex' => $pokedex['dex'],
            'list' => $pokedex['pokemons'],
            'catchStates' => $catchStates,
        ]);
    }

    /**
     * @return string[][]|string[][][]
     */
    private function getPokedex(string $dexSlug): array
    {
        $response = $this->client->request(
            'GET',
            "http://pkmn-lagd-api.local/pokedex?dex_slug=$dexSlug"
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
