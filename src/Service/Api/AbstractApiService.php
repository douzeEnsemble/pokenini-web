<?php

declare(strict_types=1);

namespace App\Service\Api;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

abstract class AbstractApiService implements ApiServiceInterface
{
    public function __construct(
        protected readonly LoggerInterface $logger,
        protected readonly HttpClientInterface $client,
        protected readonly string $apiUrl,
        protected readonly string $apiCafilePath,
        protected readonly TagAwareCacheInterface $cache,
        protected readonly string $apiLogin,
        protected readonly string $apiPassword
    ) {}

    /**
     * @param mixed[] $options
     */
    protected function request(
        string $method,
        string $endpointUrl,
        array $options = []
    ): ResponseInterface {
        $this->logger->info(
            "Requesting {$method} {$endpointUrl}",
            $options
        );

        $response = $this->client->request(
            $method,
            "{$this->apiUrl}$endpointUrl",
            array_merge(
                [
                    'headers' => [
                        'accept' => 'application/json',
                    ],
                    'auth_basic' => [
                        $this->apiLogin,
                        $this->apiPassword,
                    ],
                    'cafile' => $this->apiCafilePath,
                ],
                $options
            ),
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
        array $options = []
    ): string {
        $response = $this->request(
            $method,
            $endpointUrl,
            $options,
        );

        /** @var string */
        return $response->getContent();
    }
}
