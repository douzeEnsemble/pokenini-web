<?php

declare(strict_types=1);

namespace App\DTO;

class ActionLog
{
    /**
     * @param int[] $details
     */
    private function __construct(
        public readonly \DateTime $createdAt,
        public readonly ?\DateTime $doneAt,
        public readonly ?int $executionTime,
        public readonly array $details,
        public readonly ?string $errorTrace,
    ) {}

    /**
     * @param array<int|int[]|string> $data
     */
    public static function createFromArray(array $data): self
    {
        /** @var string */
        $createdAt = $data['created_at'];

        /** @var ?string */
        $doneAtStr = $data['done_at'] ?? null;
        $doneAt = null !== $doneAtStr ? new \DateTime($doneAtStr) : null;

        /** @var ?int */
        $executionTime = (isset($data['execution_time'])) ? (int) $data['execution_time'] : null;

        /** @var int[] */
        $details = $data['details'] ?? [];

        /** @var ?string */
        $errorTrace = $data['error_trace'] ?? null;

        return new self(
            new \DateTime($createdAt),
            $doneAt,
            $executionTime,
            $details,
            $errorTrace,
        );
    }
}
