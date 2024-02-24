<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Service\Trait\CacheRegisterTrait;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

abstract class AbstractApiService implements ApiServiceInterface
{
    use CacheRegisterTrait;

    public function __construct(
        protected readonly HttpClientInterface $client,
        protected readonly string $appApiUrl,
        protected readonly CacheInterface $cache,
        protected readonly string $apiLogin,
        protected readonly string $apiPassword
    ) {
    }

    /**
     * @param mixed[] $options
     */
    protected function request(
        string $method,
        string $endpointUrl,
        array $options = []
    ): ResponseInterface {
        return $this->client->request(
            $method,
            "{$this->appApiUrl}$endpointUrl",
            array_merge(
                [
                    'headers' => [
                        'accept' => 'application/json',
                    ],
                    'auth_basic' => [
                        $this->apiLogin,
                        $this->apiPassword,
                    ],
                ],
                $options
            ),
        );
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
