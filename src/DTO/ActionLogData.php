<?php

declare(strict_types=1);

namespace App\DTO;

use App\ResponseObject\ActionLog;

final class ActionLogData
{
    public function __construct(
        public readonly string $actionType,
        public readonly ?ActionLog $current = null,
        public readonly ?ActionLog $last = null,
    ) {}

    public function getActionType(): string
    {
        return $this->actionType;
    }

    public function getCurrent(): ?ActionLog
    {
        return $this->current;
    }

    public function getLast(): ?ActionLog
    {
        return $this->last;
    }
}
