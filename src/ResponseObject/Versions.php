<?php

declare(strict_types=1);

namespace App\ResponseObject;

final class Versions
{
    public function __construct(
        public readonly BrickVersion $back,
        public readonly BrickVersion $api,
    ) {}
}
