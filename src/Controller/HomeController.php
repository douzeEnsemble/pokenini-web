<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\HomePage;
use App\Service\Back\GetAlbumDexListService;
use App\Service\Back\TrainerPreferenceService;
use App\Service\GetLabelsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

final class HomeController extends AbstractController
{
    public function __construct(
        private readonly GetAlbumDexListService $albumDexListService,
        private readonly GetLabelsService $getLabelsService,
        private readonly string $demoUserId,
    ) {}

    /**
     * Opens Pokénini on the page the trainer chose, the hub otherwise.
     */
    #[Route('')]
    public function index(TrainerPreferenceService $trainerPreferenceService): Response
    {
        $homePage = $this->isGranted('ROLE_TRAINER')
            ? $this->getHomePage($trainerPreferenceService)
            : new HomePage(HomePage::HUB);

        if ($homePage->isHub()) {
            return $this->renderHub();
        }

        return $this->redirectToRoute($homePage->getRoute());
    }

    #[Route('/hub')]
    public function hub(): Response
    {
        return $this->renderHub();
    }

    private function getHomePage(TrainerPreferenceService $trainerPreferenceService): HomePage
    {
        try {
            return $trainerPreferenceService->getHomePage();
        } catch (ExceptionInterface) {
            // The hub still leads everywhere when the choice can't be read
            return new HomePage(HomePage::HUB);
        }
    }

    private function renderHub(): Response
    {
        if ($this->isGranted('ROLE_TRAINER')) {
            return $this->render('Home/index.html.twig');
        }

        return $this->render(
            'Home/index.html.twig',
            [
                'dex' => $this->albumDexListService->get($this->demoUserId),
                'demoUserId' => $this->demoUserId,
                'catchStates' => $this->getLabelsService->getCatchStates(),
            ]
        );
    }
}
