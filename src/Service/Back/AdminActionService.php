<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\DTO\AdminAction;

class AdminActionService extends AbstractBackService
{
    public function execute(string $action, string $item, string $method): AdminAction
    {
        $content = $this->requestContent(
            $method,
            "/istration/action/{$action}/{$item}"
        );

        /** @var string[] $data */
        $data = json_decode($content, true);

        return new AdminAction(
            $action,
            $item,
            $data['state'] ?? 'ko',
            $data['content'] ?? '',
            $data['error'] ?? ''
        );
    }
}
