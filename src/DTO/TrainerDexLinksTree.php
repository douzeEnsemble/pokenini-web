<?php

declare(strict_types=1);

namespace App\DTO;

final class TrainerDexLinksTree
{
    /**
     * @param TrainerDexLinkEdge[] $edges
     */
    public function __construct(
        private readonly array $edges,
    ) {}

    /**
     * @return TrainerDexLinkEdge[]
     */
    public function getEdges(): array
    {
        return $this->edges;
    }

    public function isEmpty(): bool
    {
        return [] === $this->edges;
    }
}
