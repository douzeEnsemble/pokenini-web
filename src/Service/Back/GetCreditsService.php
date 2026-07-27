<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\ResponseObject\Common\CreditGroup;

class GetCreditsService extends AbstractBackService
{
    /**
     * @return CreditGroup[]
     */
    public function get(): array
    {
        $json = $this->requestContent(
            'GET',
            '/credits'
        );

        /** @var CreditGroup[] */
        return $this->serializer->deserialize($json, CreditGroup::class.'[]', 'json');
    }
}
