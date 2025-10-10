<?php

namespace App\Service\Back;

use App\DTO\UserInfo;
use League\OAuth2\Client\Token\AccessToken;

class GetUserInfoService extends AbstractBackService implements BackServiceInterface
{
    public function get(AccessToken $accessToken, string $providerName): UserInfo
    {
        $content = $this->requestContent(
            'GET',
            '/user-info',
            [],
            $accessToken,
            $providerName,
        );

        return $this->serializer->deserialize($content, UserInfo::class, 'json');
    }
}
