<?php

declare(strict_types=1);

namespace App\ResponseObject\ColorGame;

use App\ResponseObject\Common\NamesMap;
use Symfony\Component\Serializer\Attribute\SerializedName;

final class ColorGameChoice
{
    /**
     * @param string[]      $codes
     * @param string[]      $names
     * @param null|string[] $frenchNames null when the API answered in a single language
     */
    public function __construct(
        #[SerializedName('index')]
        private readonly int $index,
        #[SerializedName('codes')]
        private readonly array $codes,
        #[SerializedName('names')]
        private readonly array $names,
        #[SerializedName('french_names')]
        private readonly ?array $frenchNames,
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
     * @return null|string[]
     */
    public function getFrenchNames(): ?array
    {
        return $this->frenchNames;
    }

    /**
     * @return array<string, string[]> the names of the colors indexed by locale
     */
    public function getNamesByLocale(): array
    {
        if (null === $this->frenchNames) {
            return [NamesMap::RESOLVED => $this->names];
        }

        return ['en' => $this->names, 'fr' => $this->frenchNames];
    }
}
