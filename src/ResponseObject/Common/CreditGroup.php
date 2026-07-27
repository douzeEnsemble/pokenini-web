<?php

declare(strict_types=1);

namespace App\ResponseObject\Common;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class CreditGroup
{
    /**
     * @param CreditImage[] $images
     */
    public function __construct(
        #[SerializedName('credit')]
        private readonly string $credit,
        private readonly array $images,
    ) {}

    public function getName(): string
    {
        return PokemonCredit::extractName($this->credit, $this->getUrl());
    }

    public function getUrl(): ?string
    {
        return PokemonCredit::extractUrl($this->credit);
    }

    /**
     * @return CreditImage[]
     */
    public function getImages(): array
    {
        return $this->images;
    }
}
