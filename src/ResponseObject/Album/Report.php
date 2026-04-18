<?php

declare(strict_types=1);

namespace App\ResponseObject\Album;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class Report
{
    /**
     * @param ReportDetail[] $detail
     */
    public function __construct(
        #[SerializedName('total')]
        private readonly ?int $total,
        #[SerializedName('total_caught')]
        private readonly ?int $totalCaught,
        #[SerializedName('total_uncaught')]
        private readonly ?int $totalUncaught,
        #[SerializedName('detail')]
        private readonly ?array $detail,
    ) {}

    public function getTotal(): ?int
    {
        return $this->total;
    }

    public function getTotalCaught(): ?int
    {
        return $this->totalCaught;
    }

    public function getTotalUncaught(): ?int
    {
        return $this->totalUncaught;
    }

    /**
     * @return ReportDetail[]
     */
    public function getDetail(): array
    {
        return $this->detail ?? [];
    }
}
