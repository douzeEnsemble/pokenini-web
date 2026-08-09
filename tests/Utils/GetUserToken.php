<?php

declare(strict_types=1);

namespace App\Tests\Utils;

use App\Security\User;
use League\OAuth2\Client\Token\AccessToken;

final class GetUserToken
{
    public static function getFakeUserToken(
        string $identifier = '789465465489',
        string $providerName = 'TestProvider',
    ): User {
        // Fake tokens derived from the identifier — not real OAuth credentials.
        // The session token must stay derived from the identifier (not a fixed
        // value): AbstractBackService::request() sends it as bearer, and the Moco
        // fixtures under tests/resources/moco/Back/ select per-user responses by
        // matching this exact bearer value.
        return new User(
            $identifier,
            $providerName,
            new AccessToken(
                [
                    'access_token' => sha1($identifier),
                    'expires_in' => 999999,
                    'refresh_token' => md5($identifier),
                ]
            ),
            sha1($identifier),
        );
    }
}
