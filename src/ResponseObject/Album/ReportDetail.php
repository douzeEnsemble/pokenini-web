<?php

declare(strict_types=1);

namespace App\ResponseObject\Album;

use App\ResponseObject\Label\CatchState;
use Symfony\Component\Serializer\Attribute\SerializedName;

final class ReportDetail
{
    public function __construct(
        #[SerializedName('catch_state')]
        private readonly CatchState $catchState,
        #[SerializedName('count')]
        private readonly int $count,
    ) {}

    public function getSlug(): string
    {
        return $this->catchState->getSlug();
    }

    public function getName(): string
    {
        return $this->catchState->getName();
    }

    public function getFrenchName(): string
    {
        return $this->catchState->getFrenchName();
    }

    public function getColor(): string
    {
        return $this->catchState->getColor();
    }

    public function getCount(): int
    {
        return $this->count;
    }
}
