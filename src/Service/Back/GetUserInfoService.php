<?php

namespace App\Service\Back;

use App\DTO\UserInfo;
use League\OAuth2\Client\Token\AccessToken;

class GetUserInfoService extends AbstractBackService implements BackServiceInterface
{
    public function get(?AccessToken $accessToken = null): UserInfo
    {
        $content = $this->requestContent(
            'GET',
            '/user-info',
            [],
            $accessToken,
        );

        /** @var array<string|string[]> */
        $data = json_decode($content, true);

        return UserInfo::createFromArray($data);
    }
}
