<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\AlbumController;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UpdateAlbumController extends WebTestCase
{
    public function testUpdateConnected(): void
    {
        $client = static::createClient();

        $client->request(
            'PATCH',
            '/album/demo/bulbasaur',
            [
                'body' => 'yes',
            ],
            [],
            [
                'PHP_AUTH_USER' => 'renaud',
                'PHP_AUTH_PW'   => 'douze',
            ]
        );

        $this->assertResponseIsSuccessful();
    }

    public function testUpdateNonConnected(): void
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
}
