<?php

declare(strict_types=1);

namespace App\ResponseObject;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class ActionLog
{
    /**
     * @param null|array<string, int> $details
     */
    public function __construct(
        #[SerializedName('created_at')]
        public readonly \DateTimeImmutable $createdAt,
        #[SerializedName('done_at')]
        public readonly ?\DateTimeImmutable $doneAt,
        #[SerializedName('execution_time')]
        public readonly ?int $executionTime,
        #[SerializedName('details')]
        public readonly ?array $details,
        #[SerializedName('error_trace')]
        public readonly ?string $errorTrace,
    ) {}
}
