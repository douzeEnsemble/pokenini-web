<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\DTO\ActionLog;
use App\DTO\ActionLogData;
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

        /** @var array<string, int[][]|int[][][]|null[][]|null[][][]|string[][]> */
        $actionLogsData = JsonDecoder::decode($json);

        $list = [];
        foreach ($actionLogsData as $item => $data) {
            /** @var int[]|int[][]|string[] */
            $currentData = $data['current'];

            /** @var int[]|int[][]|string[] */
            $lastData = $data['last'];

            $list[$item] = new ActionLogData(
                $item,
                ActionLog::createFromArray($currentData),
                $lastData ? ActionLog::createFromArray($lastData) : null,
            );
        }

        return $list;
    }
}
