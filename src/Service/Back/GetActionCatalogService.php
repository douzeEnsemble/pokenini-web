<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\Utils\JsonDecoder;

class GetActionCatalogService extends AbstractBackService
{
    /**
     * @return list<array{type: string, name: string, depends_on: list<string>}>
     */
    public function get(): array
    {
        $json = $this->requestContent('GET', '/istration/actions');

        /** @var list<array{type: string, name: string, depends_on: list<string>}> */
        return JsonDecoder::decode($json);
    }

    public function has(string $type, string $name): bool
    {
        foreach ($this->get() as $action) {
            if ($action['type'] === $type && $action['name'] === $name) {
                return true;
            }
        }

        return false;
    }
}
