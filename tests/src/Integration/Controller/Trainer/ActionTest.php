<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Trainer;

use App\Controller\AlbumDexController;
use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(AlbumDexController::class)]
#[Group('api-mocked-testing')]
class ActionTest extends WebTestCase
{
    use TestNavTrait;

    public function testActionConnected(): void
    {
        $client = static::createClient();

        $user = new User('789465465489', 'TestProvider', new AccessToken(['access_token' => sha1('789465465489')]));
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $client->request(
            'PUT',
            '/fr/trainer/dex/goldsilvercrystal',
            [],
            [],
            [],
            '{"is_private": true, "is_on_home": true}'
        );

        $this->assertResponseStatusCodeSame(200);
    }

    public function testActionOnlyIsPrivate(): void
    {
        $client = static::createClient();

        $user = new User('789465465489', 'TestProvider', new AccessToken(['access_token' => sha1('789465465489')]));
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $client->request(
            'PUT',
            '/fr/trainer/dex/goldsilvercrystal',
            [],
            [],
            [],
            '{"is_private": true}'
        );

        $this->assertResponseStatusCodeSame(200);
    }

    public function testActionOnlyIsOnHome(): void
    {
        $client = static::createClient();

        $user = new User('789465465489', 'TestProvider', new AccessToken(['access_token' => sha1('789465465489')]));
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $client->request(
            'PUT',
            '/fr/trainer/dex/goldsilvercrystal',
            [],
            [],
            [],
            '{"is_on_home": true}'
        );

        $this->assertResponseStatusCodeSame(200);
    }

    public function testActionOnPremiumAsCollector(): void
    {
        $client = static::createClient();

        $user = new User('789465465489', 'TestProvider', new AccessToken(['access_token' => sha1('789465465489')]));
        $user->addTrainerRole();
        $user->addCollectorRole();
        $client->loginUser($user, 'web');

        $client->request(
            'PUT',
            '/fr/trainer/dex/homepokemongo',
            [],
            [],
            [],
            '{"is_private": true, "is_on_home": true}'
        );

        $this->assertResponseStatusCodeSame(200);
    }

    public function testActionOnPremiumAsNonCollector(): void
    {
        $client = static::createClient();

        $user = new User('789465465489', 'TestProvider', new AccessToken(['access_token' => sha1('789465465489')]));
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $client->request(
            'PUT',
            '/fr/trainer/dex/homepokemongo',
            [],
            [],
            [],
            '{"is_private": true, "is_on_home": true}'
        );

        $this->assertResponseStatusCodeSame(404);
    }

    public function testActionNotConnected(): void
    {
        $client = static::createClient();

        $client->request(
            'PUT',
            '/fr/trainer/dex/goldsilvercrystal',
            [],
            [],
            [],
            '{"is_private": true, "is_on_home": true}'
        );

        $this->assertResponseStatusCodeSame(307);
    }

    public function testActionBadRequest(): void
    {
        $client = static::createClient();

        $user = new User('789465465489', 'TestProvider', new AccessToken(['access_token' => sha1('789465465489')]));
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $client->request('PUT', '/fr/trainer/dex/redgreenblueyellow');

        $client->request(
            'PUT',
            '/fr/trainer/dex/redgreenblueyellow',
            [],
            [],
            [],
            '{"isprivate": true, "isonhome": true}'
        );

        $this->assertResponseStatusCodeSame(500);

        $content = (string) $client->getResponse()->getContent();
        $this->assertSame('{"error":"Fail to modify resources"}', $content);
    }

    public function testActionFail(): void
    {
        $client = static::createClient();

        $user = new User('789465465489', 'TestProvider', new AccessToken(['access_token' => sha1('789465465489')]));
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $client->request('PUT', '/fr/trainer/dex/redgreenblueyellow');

        $client->request(
            'PUT',
            '/fr/trainer/dex/redgreenblueyellow',
            [],
            [],
            [],
            '{"is_private": true, "is_on_home": true}'
        );

        $this->assertResponseStatusCodeSame(500);

        $content = (string) $client->getResponse()->getContent();
        $this->assertSame('{"error":"Fail to modify resources"}', $content);
    }
}
