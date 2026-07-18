<?php

declare(strict_types=1);

namespace App\ResponseObject\Common;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class PokemonCredit
{
    public function __construct(
        #[SerializedName('name')]
        private readonly string $name,
        #[SerializedName('url')]
        private readonly string $url,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getUrl(): string
    {
        return $this->url;
    }
}
