<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GetResourcesVersionService
{
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly string $resourcesVersionUrl,
    ) {}

    public function get(): ?string
    {
        try {
            $content = $this->client->request('GET', $this->resourcesVersionUrl)->getContent();
        } catch (ExceptionInterface) {
            return null;
        }

        return trim($content);
    }
}
