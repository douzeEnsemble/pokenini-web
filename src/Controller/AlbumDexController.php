<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\Back\GetDexService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/album')]
class AlbumDexController extends AbstractController
{
    public function __construct() {}

    #[Route(
        '/dex',
        methods: ['GET']
    )]
    public function index(
        GetDexService $getDexService,
        Request $request,
    ): Response {
        $requestedTrainerId = $request->query->getAlnum('t', '');

        $dex = $getDexService->get($requestedTrainerId);

        return $this->render(
            'AlbumDex/index.html.twig',
            [
                'dex' => $dex,
            ]
        );
    }
}
