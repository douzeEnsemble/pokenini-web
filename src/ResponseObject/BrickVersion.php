<?php

declare(strict_types=1);

namespace App\ResponseObject;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class BrickVersion
{
    public function __construct(
        public readonly ?string $version,
        #[SerializedName('updated_at')]
        public readonly ?\DateTimeImmutable $updatedAt,
    ) {}
}
