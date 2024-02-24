<?php

declare(strict_types=1);

namespace App\Controller;

use App\Security\User;
use App\Security\UserTokenService;
use App\Service\Api\GetDexService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/trainer')]
class TrainerIndexController extends AbstractController
{
    use ValidatorJsonResponseTrait;

    public function __construct(
        private readonly UserTokenService $userTokenService,
        private readonly ValidatorInterface $validator,
        private readonly GetDexService $getDexService,
    ) {
    }

    #[Route('')]
    public function index(): Response
    {
        /** @var ?User $user */
        $user = $this->getUser();

        if (null === $user) {
            return new Response('', Response::HTTP_UNAUTHORIZED);
        }

        $userToken = $this->userTokenService->getLoggedUserToken();

        $trainerDex = $user->isAnAdmin()
            ? $this->getDexService->getWithUnreleased($userToken)
            : $this->getDexService->get($userToken);

        return $this->render(
            'Trainer/index.html.twig',
            [
                'trainerDex' => $trainerDex,
            ]
        );
    }
}
