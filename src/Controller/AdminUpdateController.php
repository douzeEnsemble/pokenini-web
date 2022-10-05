<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpClient\Exception\ServerException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/istrateur/update')]
class AdminUpdateController extends AbstractController
{
    #[Route(
        '/{name}',
        methods: ['GET'],
        condition: "params['name'] in ['labels', 'games_and_dexes', 'pokemons', 'game_bundle_availability', 'dex_availability']"
    )]
    public function update(
        string $name,
        HttpClientInterface $client,
        string $appApiUrl,
        TranslatorInterface $translator
    ): Response {
        try {
            $client->request(
                'POST',
                "{$appApiUrl}/istrateur/update/$name"
            );

            $this->addFlash(
                'success',
                $translator->trans("update.$name.success")
            );
        } catch (TransportExceptionInterface | ServerException | \Exception $e) {
            $this->addFlash(
                'danger',
                $translator->trans("update.$name.error") . ' ' . $e->getMessage()
            );
        }

        return $this->redirectToRoute('app_admin_index');
    }
}
