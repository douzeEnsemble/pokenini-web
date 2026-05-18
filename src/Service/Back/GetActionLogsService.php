<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\DTO\ActionLogData;
use App\ResponseObject\ActionLog;
use App\Utils\JsonDecoder;

class GetActionLogsService extends AbstractBackService
{
    /**
     * @return array<string, ActionLogData>
     */
    public function get(): array
    {
        $json = $this->requestContent(
            'GET',
            '/istration/action-logs'
        );

        /** @var array<string, array{current: array{created_at: string, done_at: null|string, execution_time: null|int, details: array<string, int>, error_trace: null|string}, last: null|array{created_at: string, done_at: null|string, execution_time: null|int, details: array<string, int>, error_trace: null|string}}> */
        $actionLogsData = JsonDecoder::decode($json);

        $list = [];
        foreach ($actionLogsData as $item => $data) {
            $currentData = $data['current'];
            $lastData = $data['last'];

            $list[$item] = new ActionLogData(
                $item,
                $this->serializer->deserialize((string) json_encode($currentData), ActionLog::class, 'json'),
                $lastData ? $this->serializer->deserialize((string) json_encode($lastData), ActionLog::class, 'json') : null,
            );
        }

        return $list;
    }
}
