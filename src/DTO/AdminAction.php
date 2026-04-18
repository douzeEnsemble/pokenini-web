<?php

declare(strict_types=1);

namespace App\DTO;

final class AdminAction
{
    public function __construct(
        public readonly string $action,
        public readonly string $item,
        public readonly string $state,
        public readonly string $content = '',
        public readonly string $error = '',
    ) {}
}
