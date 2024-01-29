<?php

declare(strict_types=1);

namespace App\Tests\Functional\Album\Action;

use App\Security\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UpdateTest extends WebTestCase
{

    public function testUpdateConnected(): void
    {
        $client = static::createClient();

        $user = new User('789465465489');
        $user->addTrainerRole();
        $client->loginUser($user);

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

    public function testUpdateNonConnected(): void
    {
        $client = static::createClient();

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

    public function testUpdateFailed(): void
    {
        $client = static::createClient();

        $user = new User('789465465489');
        $user->addTrainerRole();
        $client->loginUser($user);

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
        $this->assertStringContainsString('HTTP\/1.1 500 Internal Server Error', $content);
        $this->assertStringContainsString('\/demo\/blastoise', $content);
    }
}
