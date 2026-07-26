<?php

declare(strict_types=1);

namespace App\ResponseObject\Common;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class PokemonCredit
{
    public function __construct(
        #[SerializedName('credit')]
        private readonly string $credit,
    ) {}

    public function getName(): string
    {
        return self::extractName($this->credit, $this->getUrl());
    }

    public function getUrl(): ?string
    {
        return self::extractUrl($this->credit);
    }

    private static function extractUrl(string $credit): ?string
    {
        if (1 === preg_match('/(https?:\/\/\S+)/', $credit, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private static function extractName(string $credit, ?string $url): string
    {
        if (null === $url) {
            return $credit;
        }

        $name = trim(str_replace($url, '', $credit), " \t\n\r\0\x0B-");

        return '' !== $name ? $name : $credit;
    }
}
