<?php

namespace App\Tests\Functionnal\Controller\AlbumController;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UpdateAlbumController extends WebTestCase
{
    public function testUpdatePrivate(): void
    {
        $client = static::createClient();

        $client->request(
            'PATCH',
            '/album/demo/bulbasaur?token=cb19dc668f0c426c8f3e319f9ea36ecc',
            [
                'body' => 'yes',
            ]
        );

        $this->assertResponseIsSuccessful();
    }

    public function testUpdatePublic(): void
    {
        $client = static::createClient();

        $client->request(
            'PATCH',
            '/album/demo/bulbasaur',
            [
                'body' => 'yes',
            ]
        );


        $this->assertEquals(403, $client->getResponse()->getStatusCode());
    }

    public function testUpdateWrongToken(): void
    {
        $client = static::createClient();

        $client->request(
            'PATCH',
            '/album/demo/bulbasaur?token=kadkjazpdazpdi',
            [
                'body' => 'yes',
            ]
        );

        $this->assertEquals(403, $client->getResponse()->getStatusCode());
    }
}
