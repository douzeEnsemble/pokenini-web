<?php

declare(strict_types=1);

namespace App\ResponseObject\Album;

use App\ResponseObject\Common\NamesMap;
use Symfony\Component\Serializer\Attribute\SerializedName;

final class DexRegion
{
    public function __construct(
        #[SerializedName('name')]
        private readonly string $name,
        #[SerializedName('french_name')]
        private readonly ?string $frenchName,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getFrenchName(): ?string
    {
        return $this->frenchName;
    }

    /**
     * @return array<string, string> the name indexed by locale
     */
    public function getNames(): array
    {
        return NamesMap::from($this->name, $this->frenchName);
    }
}
