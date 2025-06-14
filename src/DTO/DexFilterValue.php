<?php

declare(strict_types=1);

namespace App\DTO;

final class DexFilterValue
{
    public function __construct(public ?bool $value) {}
}
