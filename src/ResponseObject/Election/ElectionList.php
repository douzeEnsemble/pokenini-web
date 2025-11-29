<?php

declare(strict_types=1);

namespace App\ResponseObject\Election;

use App\ResponseObject\Common\Pokemon;
use Symfony\Component\Serializer\Attribute\SerializedName;

class ElectionList
{
    /**
     * @param Pokemon[] $items
     */
    public function __construct(
        #[SerializedName('type')]
        private readonly string $type,
        #[SerializedName('items')]
        private readonly array $items,
    ) {}

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return Pokemon[]
     */
    public function getItems(): array
    {
        return $this->items;
    }
}
