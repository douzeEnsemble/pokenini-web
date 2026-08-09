<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Trainer;

use App\Controller\TrainerUpsertController;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(TrainerUpsertController::class)]
#[Group('api-mocked-testing')]
final class ActionTest extends WebTestCase
{
    use TestNavTrait;

    #[Test]
    public function actionConnected(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
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

    #[Test]
    public function actionOnlyIsPrivate(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
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

    #[Test]
    public function actionOnlyIsOnHome(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
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

    #[Test]
    public function actionOnPremiumAsCollector(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
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

    #[Test]
    public function actionOnPremiumAsNonCollector(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
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

    #[Test]
    public function actionNotConnected(): void
    {
        $client = self::createClient();

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

    #[Test]
    public function actionBadRequest(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
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

    #[Test]
    public function actionFail(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
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
