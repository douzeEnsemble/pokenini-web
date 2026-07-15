<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\ResponseObject\ImagePipelineStatus;

class GetImagePipelineStatusService extends AbstractBackService
{
    public function get(bool $refresh): ?ImagePipelineStatus
    {
        $endpointUrl = '/istration/action/trigger/update_images/status';

        if ($refresh) {
            $endpointUrl .= '?refresh=1';
        }

        $content = $this->requestContent('GET', $endpointUrl);

        if ('{}' === trim($content)) {
            return null;
        }

        /** @var ImagePipelineStatus */
        return $this->serializer->deserialize($content, ImagePipelineStatus::class, 'json');
    }
}
