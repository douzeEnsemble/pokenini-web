<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Album\Action;

use App\Controller\AlbumUpsertController;
use App\Service\ModifyTrainerDexService;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(AlbumUpsertController::class)]
#[CoversClass(ModifyTrainerDexService::class)]
#[Group('api-mocked-testing')]
final class UpdateTest extends WebTestCase
{
    #[Test]
    public function updateConnected(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $client->request(
            'PATCH',
            '/fr/album/demo/bulbasaur',
            [],
            [],
            [],
            'yes'
        );

        $this->assertResponseIsSuccessful();
    }

    #[Test]
    public function updateNonConnected(): void
    {
        $client = self::createClient();

        $client->request(
            'PATCH',
            '/fr/album/demo/bulbasaur',
            [],
            [],
            [],
            'yes'
        );

        $this->assertTrue($client->getResponse()->isRedirect());
        $this->assertEquals(307, $client->getResponse()->getStatusCode());

        $crawler = $client->followRedirect();

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals('http://localhost/fr', $crawler->getBaseHref());
    }

    #[Test]
    public function updatePremiumCollector(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $user->addCollectorRole();
        $client->loginUser($user, 'web');

        $client->request(
            'PATCH',
            '/fr/album/homepokemongo/bulbasaur',
            [],
            [],
            [],
            'yes'
        );

        $this->assertResponseIsSuccessful();
    }

    #[Test]
    public function updatePremiumNonCollector(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $client->request(
            'PATCH',
            '/fr/album/homepokemongo/bulbasaur',
            [],
            [],
            [],
            'yes'
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    #[Test]
    public function updateFailed(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $client->request(
            'PATCH',
            '/fr/album/demo/blastoise',
            [],
            [],
            [],
            'tobreed'
        );

        $this->assertEquals(500, $client->getResponse()->getStatusCode());

        $content = (string) $client->getResponse()->getContent();
        $this->assertSame('{"error":"Fail to modify resources"}', $content);
    }
}
