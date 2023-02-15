<?php

declare(strict_types=1);

namespace App\Tests\Functional\Trainer;

use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Common\Traits\TestSetUp;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ActionTest extends WebTestCase
{
    use TestNavTrait;
    use TestSetUp;

    public function testActionConnected(): void
    {
        $client = static::createClient();

        $user = new User('789465465489');
        $user->addTrainerRole();
        $client->loginUser($user);

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

        $user = new User('789465465489');
        $user->addTrainerRole();
        $client->loginUser($user);

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

        $user = new User('789465465489');
        $user->addTrainerRole();
        $client->loginUser($user);

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

        $user = new User('789465465489');
        $user->addTrainerRole();
        $client->loginUser($user);

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
        $this->assertStringContainsString('HTTP\/1.1 400 Bad Request returned', $content);
        $this->assertStringContainsString(
            '\/dex\/6c33064427a5b419ca8eb3f7d11a0807f66cab22\/redgreenblueyellow',
            $content
        );
    }

    public function testActionFail(): void
    {
        $client = static::createClient();

        $user = new User('789465465489');
        $user->addTrainerRole();
        $client->loginUser($user);

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
        $this->assertStringContainsString('HTTP\/1.1 500 Internal Server Error', $content);
        $this->assertStringContainsString(
            '\/dex\/6c33064427a5b419ca8eb3f7d11a0807f66cab22\/redgreenblueyellow',
            $content
        );
    }
}
