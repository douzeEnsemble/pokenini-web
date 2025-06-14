<?php

declare(strict_types=1);

namespace App\Controller;

use App\AlbumFilters\FromRequest;
use App\DTO\ElectionVote;
use App\Service\ElectionVoteService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/election')]
class ElectionVoteController extends AbstractController
{
    #[Route(
        '/{dexSlug}/{electionSlug}',
        requirements: [
            'dexSlug' => '[A-Za-z0-9]+(?:-[A-Za-z0-9]+)*',
            'electionSlug' => '[A-Za-z0-9]+(?:-[A-Za-z0-9]+)*',
        ],
        methods: ['POST']
    )]
    public function vote(
        Request $request,
        ElectionVoteService $electionVoteService,
        string $dexSlug,
        string $electionSlug = '',
    ): Response {
        $data = $request->request->all();

        if (empty($data)) {
            throw new BadRequestHttpException('Data cannot be empty');
        }

        /** @var string[]|string[][] $data */
        $data = array_merge(
            [
                'dex_slug' => $dexSlug,
                'election_slug' => $electionSlug,
            ],
            $data
        );

        try {
            $electionVote = new ElectionVote($data);
        } catch (\InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        $electionVoteService->vote($electionVote);

        $filters = FromRequest::get($request);

        return $this->redirectToRoute(
            'app_web_electionindex_index',
            array_merge(
                [
                    'dexSlug' => $electionVote->dexSlug,
                    'electionSlug' => $electionVote->electionSlug,
                ],
                $filters,
            ),
        );
    }
}
