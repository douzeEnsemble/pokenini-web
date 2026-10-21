<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\PokemonImage;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Fetches the image of a "Shiny or not" or "Which one is shiny?" round, so the browser never sees
 * whether the regular or the shiny image is shown.
 */
class ShinyGameImageService
{
    private const string DEFAULT_CONTENT_TYPE = 'application/octet-stream';

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly HttpClientInterface $client,
        private readonly string $gameImageUrl,
    ) {}

    public function get(string $icon, bool $isShiny): ?PokemonImage
    {
        $url = sprintf($this->gameImageUrl, $isShiny ? 'shiny' : 'regular', $icon);

        try {
            $response = $this->client->request('GET', $url);
            $content = $response->getContent();
            $contentType = $response->getHeaders()['content-type'][0] ?? self::DEFAULT_CONTENT_TYPE;
        } catch (ExceptionInterface $exception) {
            $this->logger->warning('Failed to fetch the shiny game image', ['exception' => $exception]);

            return null;
        }

        return new PokemonImage($content, $contentType);
    }
}
