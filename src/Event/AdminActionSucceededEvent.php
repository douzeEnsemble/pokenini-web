<?php

declare(strict_types=1);

namespace App\Event;

final class AdminActionSucceededEvent
{
    public function __construct(
        public readonly string $name,
    ) {}
}
