<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\HomePage;
use App\Service\Back\TrainerPreferenceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

#[Route('/trainer/home_page')]
#[IsGranted('ROLE_TRAINER')]
final class TrainerHomePageController extends AbstractController
{
    private const string CSRF_TOKEN_ID = 'trainer_home_page';

    public function __construct(
        private readonly TrainerPreferenceService $trainerPreferenceService,
    ) {}

    #[Route('', name: 'app_trainerhomepage_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render(
            'Trainer/home_page.html.twig',
            [
                'homePage' => $this->trainerPreferenceService->getHomePage(),
                'homePages' => array_keys(HomePage::ROUTES),
                'csrfTokenId' => self::CSRF_TOKEN_ID,
            ]
        );
    }

    #[Route('', name: 'app_trainerhomepage_save', methods: ['POST'])]
    public function save(Request $request): Response
    {
        if (!$this->isCsrfTokenValid(self::CSRF_TOKEN_ID, $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $value = $request->request->getString('home_page');

        if (!HomePage::isValid($value)) {
            $this->addFlash('home_page', 'error');

            return $this->redirectToRoute('app_trainerhomepage_index');
        }

        try {
            $this->trainerPreferenceService->modifyHomePage(new HomePage($value));
        } catch (ExceptionInterface) {
            $this->addFlash('home_page', 'error');

            return $this->redirectToRoute('app_trainerhomepage_index');
        }

        $this->addFlash('home_page', 'saved');

        return $this->redirectToRoute('app_trainerhomepage_index');
    }
}
