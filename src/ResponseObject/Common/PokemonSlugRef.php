<?php

declare(strict_types=1);

namespace App\ResponseObject\Common;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class PokemonSlugRef
{
    public function __construct(
        #[SerializedName('slug')]
        private readonly string $slug,
    ) {}

    public function getSlug(): string
    {
        return $this->slug;
    }
}
