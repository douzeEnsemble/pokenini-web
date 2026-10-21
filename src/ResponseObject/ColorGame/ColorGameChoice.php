<?php

declare(strict_types=1);

namespace App\ResponseObject\ColorGame;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class ColorGameChoice
{
    /**
     * @param string[] $codes
     * @param string[] $names
     * @param string[] $frenchNames
     */
    public function __construct(
        #[SerializedName('index')]
        private readonly int $index,
        #[SerializedName('codes')]
        private readonly array $codes,
        #[SerializedName('names')]
        private readonly array $names,
        #[SerializedName('french_names')]
        private readonly array $frenchNames,
    ) {}

    public function getIndex(): int
    {
        return $this->index;
    }

    /**
     * @return string[]
     */
    public function getCodes(): array
    {
        return $this->codes;
    }

    /**
     * @return string[]
     */
    public function getNames(): array
    {
        return $this->names;
    }

    /**
     * @return string[]
     */
    public function getFrenchNames(): array
    {
        return $this->frenchNames;
    }
}
