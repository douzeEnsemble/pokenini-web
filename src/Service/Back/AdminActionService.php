<?php

namespace App\Service\Back;

use App\DTO\AdminAction;

class AdminActionService extends AbstractBackService implements BackServiceInterface
{
    public function execute(string $action, string $item): AdminAction
    {
        $content = $this->requestContent(
            'POST',
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
