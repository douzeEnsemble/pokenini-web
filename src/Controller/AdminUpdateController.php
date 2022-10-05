<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpClient\Exception\ServerException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/istrateur/update')]
class AdminUpdateController extends AbstractController
{
    #[Route(
        '/{name}',
        methods: ['GET'],
        condition: "params['name'] in ['labels', 'games_and_dexes', 'game_bundle_availability', 'dex_availability']"
    )]
    public function update(
        string $name,
        HttpClientInterface $client,
        string $appApiUrl,
    ): Response {
        try {
            $client->request(
                'POST',
                "{$appApiUrl}/istrateur/update/$name"
            );

            $this->addFlash(
                'success',
                'La MAJ a bien fonctionné'
            );
        } catch (TransportExceptionInterface | ServerException | \Exception $e) {
            $this->addFlash(
                'danger',
                "La MAJ a échoué. ({$e->getMessage()})"
            );
        }

        return $this->render('Admin/index.html.twig');
    }
}
