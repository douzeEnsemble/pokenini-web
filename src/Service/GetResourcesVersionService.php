<?php

declare(strict_types=1);

namespace App\Service;

use App\ResponseObject\BrickVersion;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GetResourcesVersionService
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly HttpClientInterface $client,
        private readonly string $resourcesVersionUrl,
    ) {}

    public function get(): BrickVersion
    {
        try {
            $response = $this->client->request('GET', $this->resourcesVersionUrl);
            $content = $response->getContent();
            $lastModified = $response->getHeaders()['last-modified'][0] ?? null;
        } catch (ExceptionInterface $exception) {
            $this->logger->warning('Failed to fetch resources version', ['exception' => $exception]);

            return new BrickVersion(null, null);
        }

        return new BrickVersion(
            trim($content),
            null !== $lastModified ? new \DateTimeImmutable($lastModified) : null,
        );
    }
}
