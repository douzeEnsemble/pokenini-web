<?php

declare(strict_types=1);

namespace App\ResponseObject\Label;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class Forms
{
    /**
     * @param array<int, CategoryForm> $category
     * @param array<int, RegionalForm> $regional
     * @param array<int, SpecialForm>  $special
     * @param array<int, VariantForm>  $variant
     */
    public function __construct(
        #[SerializedName('category')]
        private readonly array $category,
        #[SerializedName('regional')]
        private readonly array $regional,
        #[SerializedName('special')]
        private readonly array $special,
        #[SerializedName('variant')]
        private readonly array $variant,
    ) {}

    /**
     * @return array<int, CategoryForm>
     */
    public function getCategory(): array
    {
        return $this->category;
    }

    /**
     * @return array<int, RegionalForm>
     */
    public function getRegional(): array
    {
        return $this->regional;
    }

    /**
     * @return array<int, SpecialForm>
     */
    public function getSpecial(): array
    {
        return $this->special;
    }

    /**
     * @return array<int, VariantForm>
     */
    public function getVariant(): array
    {
        return $this->variant;
    }
}
