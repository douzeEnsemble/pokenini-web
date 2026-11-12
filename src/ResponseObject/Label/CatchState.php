<?php

declare(strict_types=1);

namespace App\ResponseObject\Label;

use App\ResponseObject\Common\NamesMap;
use Symfony\Component\Serializer\Attribute\SerializedName;

final class CatchState
{
    public function __construct(
        #[SerializedName('name')]
        private readonly string $name,
        #[SerializedName('french_name')]
        private readonly ?string $frenchName,
        #[SerializedName('slug')]
        private readonly string $slug,
        #[SerializedName('color')]
        private readonly string $color,
        #[SerializedName('text_color')]
        private readonly ?string $textColor = null,
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

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    /**
     * Color of the text written on the catch state color, when the API provides it.
     */
    public function getTextColor(): ?string
    {
        // "??" on purpose: the labels cache holds objects serialized before this property
        // existed, and unserialize() leaves it uninitialized, where a plain read would throw.
        return $this->textColor ?? null;
    }
}
