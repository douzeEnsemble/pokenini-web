<?php

declare(strict_types=1);

namespace App\ResponseObject\Common;

use App\ResponseObject\Label\Type;
use Symfony\Component\Serializer\Attribute\SerializedName;

final class PokemonTypes
{
    public function __construct(
        #[SerializedName('primary')]
        private readonly ?Type $primary,
        #[SerializedName('secondary')]
        private readonly ?Type $secondary,
    ) {}

    public function getPrimary(): ?Type
    {
        return $this->primary;
    }

    public function getSecondary(): ?Type
    {
        return $this->secondary;
    }
}
