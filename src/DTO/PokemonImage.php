<?php

declare(strict_types=1);

namespace App\DTO;

final class PokemonImage
{
    public function __construct(
        private readonly string $content,
        private readonly string $contentType,
    ) {}

    /**
     * Raw bytes of an image fetched from the resources server, never rendered as HTML.
     *
     * @psalm-taint-escape html
     */
    public function getContent(): string
    {
        return $this->content;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }
}
