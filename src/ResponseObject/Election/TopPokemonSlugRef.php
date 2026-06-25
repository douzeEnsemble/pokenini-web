<?php

declare(strict_types=1);

namespace App\ResponseObject\Election;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class TopPokemonSlugRef
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
