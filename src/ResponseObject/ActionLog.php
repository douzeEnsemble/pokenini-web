<?php

declare(strict_types=1);

namespace App\ResponseObject;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class ActionLog
{
    /**
     * @param array<string, int> $details
     */
    public function __construct(
        #[SerializedName('created_at')]
        public readonly \DateTime $createdAt,
        #[SerializedName('done_at')]
        public readonly ?\DateTime $doneAt,
        #[SerializedName('execution_time')]
        public readonly ?int $executionTime,
        #[SerializedName('details')]
        public readonly array $details,
        #[SerializedName('error_trace')]
        public readonly ?string $errorTrace,
    ) {}
}
