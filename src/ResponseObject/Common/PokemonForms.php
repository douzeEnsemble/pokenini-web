<?php

declare(strict_types=1);

namespace App\ResponseObject\Common;

use App\ResponseObject\Label\CategoryForm;
use App\ResponseObject\Label\RegionalForm;
use App\ResponseObject\Label\SpecialForm;
use App\ResponseObject\Label\VariantForm;
use Symfony\Component\Serializer\Attribute\SerializedName;

final class PokemonForms
{
    public function __construct(
        #[SerializedName('category')]
        private readonly ?CategoryForm $category,
        #[SerializedName('regional')]
        private readonly ?RegionalForm $regional,
        #[SerializedName('special')]
        private readonly ?SpecialForm $special,
        #[SerializedName('variant')]
        private readonly ?VariantForm $variant,
    ) {}

    public function getCategory(): ?CategoryForm
    {
        return $this->category;
    }

    public function getRegional(): ?RegionalForm
    {
        return $this->regional;
    }

    public function getSpecial(): ?SpecialForm
    {
        return $this->special;
    }

    public function getVariant(): ?VariantForm
    {
        return $this->variant;
    }
}
