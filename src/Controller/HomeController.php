<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Traits\DexesRequestTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class HomeController extends AbstractController
{
    use DexesRequestTrait;

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly string $appApiUrl
    ) {
    }

    #[Route('/')]
    public function index(Request $request): Response
    {
        $dexes = $this->getDexes();

        return $this->render(
            'Home/index.html.twig',
            [
                'dexes' => $dexes,
                'lang' => $request->query->get('lang', 'fr'),
            ]
        );
    }
}
