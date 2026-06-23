<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\DTO\ActionLogData;
use App\ResponseObject\ActionLog;
use App\Utils\JsonDecoder;

class GetActionLogsService extends AbstractBackService
{
    /**
     * @return array<int, ActionLogData>
     */
    public function get(): array
    {
        $json = $this->requestContent(
            'GET',
            '/istration/action-logs'
        );

        /** @var array<int, array{action_type: string, current: null|array<string, mixed>, last: null|array<string, mixed>}> $rows */
        $rows = JsonDecoder::decode($json);

        $list = [];
        foreach ($rows as $row) {
            $list[] = new ActionLogData(
                $row['action_type'],
                null === $row['current'] ? null : $this->serializer->deserialize((string) json_encode($row['current']), ActionLog::class, 'json'),
                null === $row['last'] ? null : $this->serializer->deserialize((string) json_encode($row['last']), ActionLog::class, 'json'),
            );
        }

        return $list;
    }
}
