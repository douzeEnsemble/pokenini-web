<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\AlbumController;

use App\Security\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UpdateAlbumController extends WebTestCase
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
            [
                'body' => 'yes',
            ]
        );

        $this->assertResponseIsSuccessful();
    }

    public function testUpdateNonConnected(): void
    {
        $client = static::createClient();

        $client->request(
            'PATCH',
            '/fr/album/demo/bulbasaur',
            [
                'body' => 'yes',
            ]
        );

        $this->assertTrue($client->getResponse()->isRedirect());
        $this->assertEquals(307, $client->getResponse()->getStatusCode());

        $crawler = $client->followRedirect();

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals('http://localhost/fr/', $crawler->getBaseHref());
    }
}
