<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\Back\GetAlbumDexListService;
use App\Service\GetLabelsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/album')]
final class AlbumDexListController extends AbstractController
{
    public function __construct(
        private readonly GetLabelsService $getLabelsService,
    ) {}

    #[Route(
        '/dex',
        methods: ['GET']
    )]
    public function index(
        GetAlbumDexListService $service,
        Request $request,
    ): Response {
        $requestedTrainerId = $request->query->getAlnum('t', '');

        $dex = $service->get($requestedTrainerId);

        return $this->render(
            'AlbumDexList/index.html.twig',
            [
                'dex' => $dex,
                'catchStates' => $this->getLabelsService->getCatchStates(),
            ]
        );
    }
}
