<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\Utils\JsonDecoder;

class GetFormsService extends AbstractBackService
{
    /**
     * @return string[][]
     */
    public function getFormsCategory(): array
    {
        return $this->getFormsByType('category');
    }

    /**
     * @return string[][]
     */
    public function getFormsRegional(): array
    {
        return $this->getFormsByType('regional');
    }

    /**
     * @return string[][]
     */
    public function getFormsSpecial(): array
    {
        return $this->getFormsByType('special');
    }

    /**
     * @return string[][]
     */
    public function getFormsVariant(): array
    {
        return $this->getFormsByType('variant');
    }

    /**
     * @return string[][]
     */
    private function getFormsByType(string $type): array
    {
        $json = $this->requestContent(
            'GET',
            "/labels/forms/{$type}",
        );

        /** @var string[][] */
        return JsonDecoder::decode($json);
    }
}
