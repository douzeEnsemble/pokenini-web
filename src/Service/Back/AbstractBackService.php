<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\Exception\NoLoggedUserException;
use App\Security\UserTokenServiceInterface;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

abstract class AbstractBackService
{
    public function __construct(
        protected readonly LoggerInterface $logger,
        protected readonly HttpClientInterface $client,
        protected readonly string $backUrl,
        protected readonly string $backCafilePath,
        protected readonly UserTokenServiceInterface $userTokenService,
        protected readonly SerializerInterface $serializer,
    ) {}

    /**
     * @param mixed[] $options
     */
    protected function request(
        string $method,
        string $endpointUrl,
        array $options = [],
        ?AccessToken $accessToken = null,
        ?string $providerName = null,
    ): ResponseInterface {
        $optionsHeaders = [
            'accept' => 'application/json',
        ];

        try {
            $user = $this->userTokenService->getLoggedUser();
            $token = $user->getAccessToken()->getToken();
            $provider = $user->getProviderName();
        } catch (NoLoggedUserException) {
            $token = $accessToken?->getToken() ?? null;
            $provider = $providerName ?? null;
        }

        if (null !== $token) {
            $optionsHeaders['Authorization'] = 'Bearer '.$token;
        }

        if (null !== $provider) {
            $optionsHeaders['X-Provider'] = strtolower($provider);
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

        $responseContent = $response->getContent();

        $this->logger->info(
            "Response status code: {$response->getStatusCode()}",
            [
                'response' => substr($responseContent, 0, 256),
            ]
        );

        $this->logger->debug(
            'Response content',
            [
                'full' => $responseContent,
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
        ?string $providerName = null,
    ): string {
        $response = $this->request(
            $method,
            $endpointUrl,
            $options,
            $accessToken,
            $providerName,
        );

        /** @var string */
        return $response->getContent();
    }
}
