<?php

declare(strict_types=1);

namespace App\Controller;

use App\AlbumFilters\FromRequest;
use App\DTO\ElectionVote;
use App\Exception\ModifyFailedException;
use App\Service\ElectionVoteService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/election')]
final class ElectionVoteController extends AbstractController
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
        LoggerInterface $logger,
        string $dexSlug,
        string $electionSlug = '',
    ): Response {
        /** @var array{
         *  winners_slugs?: array<int, string>,
         *  losers_slugs?: array<int, string>,
         * } $requestedData
         */
        $requestedData = $request->request->all();

        if (empty($requestedData)) {
            throw new BadRequestHttpException('Data cannot be empty');
        }

        $data = array_merge(
            $requestedData,
            [
                'dex_slug' => $dexSlug,
                'election_slug' => $electionSlug,
            ],
        );

        try {
            $electionVote = ElectionVote::createFromArray($data);
        } catch (\InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        try {
            $electionVoteService->vote($electionVote);
        } catch (ModifyFailedException $e) {
            $logger->warning('Election vote failed', ['exception' => $e->getMessage()]);
        }

        $filterBag = FromRequest::get($request);

        return $this->redirectToRoute(
            'app_electionindex_index',
            array_merge(
                [
                    'dexSlug' => $electionVote->dexSlug,
                    'electionSlug' => $electionVote->electionSlug,
                ],
                $filterBag->toRouteParams(),
            ),
        );
    }
}
