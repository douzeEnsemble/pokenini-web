<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/pokemon')]
class PokemonController extends AbstractController
{
    #[Route('/')]
    public function index(HttpClientInterface $client): Response
    {
        $response = $client->request(
            'GET',
            'http://pkmn-lagd-api.local/pokemon'
        );

        $pokemons = json_decode($response->getContent());

        return $this->render('Pokemon/index.html.twig', [
            'list' => $pokemons,
        ]);
    }
}
