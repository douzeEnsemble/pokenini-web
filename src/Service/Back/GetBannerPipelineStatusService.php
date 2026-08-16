<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\ResponseObject\BannerPipelineStatus;

class GetBannerPipelineStatusService extends AbstractBackService
{
    public function get(bool $refresh): ?BannerPipelineStatus
    {
        $endpointUrl = '/istration/action/trigger/update_banners/status';

        if ($refresh) {
            $endpointUrl .= '?refresh=1';
        }

        $content = $this->requestContent('GET', $endpointUrl);

        if ('{}' === trim($content)) {
            return null;
        }

        /** @var BannerPipelineStatus */
        return $this->serializer->deserialize($content, BannerPipelineStatus::class, 'json');
    }
}
