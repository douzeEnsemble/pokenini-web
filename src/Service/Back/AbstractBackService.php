<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\Exception\NoLoggedUserException;
use App\Security\UserTokenService;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

abstract class AbstractBackService implements BackServiceInterface
{
    public function __construct(
        protected readonly LoggerInterface $logger,
        protected readonly HttpClientInterface $client,
        protected readonly string $backUrl,
        protected readonly string $backCafilePath,
        protected readonly UserTokenService $userTokenService,
    ) {}

    /**
     * @param mixed[] $options
     */
    protected function request(
        string $method,
        string $endpointUrl,
        array $options = [],
        ?AccessToken $accessToken = null,
    ): ResponseInterface {
        $optionsHeaders = [
            'accept' => 'application/json',
        ];

        try {
            $token = $this->userTokenService->getLoggedUserToken();
        } catch (NoLoggedUserException) {
            $token = $accessToken?->getToken() ?? null;
        }

        if (null !== $token) {
            $optionsHeaders['Authorization'] = 'Bearer '.$token;
        }

        $finalOptions = array_merge(
            [
                'headers' => $optionsHeaders,
                'cafile' => $this->backCafilePath,
            ],
            $options
        );

        $this->logger->info(
            "Requesting {$method} {$endpointUrl}",
            $finalOptions
        );

        $response = $this->client->request(
            $method,
            "{$this->backUrl}$endpointUrl",
            $finalOptions,
        );

        $this->logger->info(
            "Response status code: {$response->getStatusCode()}",
            [
                'response' => $response->getContent(),
            ]
        );

        return $response;
    }

    /**
     * @param mixed[] $options
     */
    protected function requestContent(
        string $method,
        string $endpointUrl,
        array $options = [],
        ?AccessToken $accessToken = null,
    ): string {
        $response = $this->request(
            $method,
            $endpointUrl,
            $options,
            $accessToken,
        );

        /** @var string */
        return $response->getContent();
    }
}
