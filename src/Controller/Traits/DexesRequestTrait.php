<?php

declare(strict_types=1);

namespace App\Controller\Traits;

use Symfony\Contracts\HttpClient\HttpClientInterface;

trait DexesRequestTrait
{
    private readonly HttpClientInterface $client;
    private readonly string $appApiUrl;

    /**
     * @return string[][]
     */
    protected function getDexes(): array
    {
        $response = $this->client->request(
            'GET',
            "{$this->appApiUrl}/dexes",
            [
                'headers' => [
                    'accept' => 'application/json',
                ],
            ]
        );

        /** @var string[][] */
        return json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }
}
