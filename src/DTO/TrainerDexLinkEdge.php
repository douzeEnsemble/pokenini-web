<?php

declare(strict_types=1);

namespace App\DTO;

use App\ResponseObject\Album\DexListItem;

final class TrainerDexLinkEdge
{
    public function __construct(
        private readonly string $id,
        private readonly string $mode,
        private readonly DexListItem $from,
        private readonly DexListItem $to,
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * 'to' for a one-directional sync from `from` to `to`, 'both' for a bidirectional sync.
     */
    public function getMode(): string
    {
        return $this->mode;
    }

    public function getFrom(): DexListItem
    {
        return $this->from;
    }

    public function getTo(): DexListItem
    {
        return $this->to;
    }
}
