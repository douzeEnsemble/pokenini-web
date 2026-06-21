<?php

declare(strict_types=1);

namespace App\ResponseObject\Label;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class Generation
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
