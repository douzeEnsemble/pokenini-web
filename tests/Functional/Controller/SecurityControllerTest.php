<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SecurityControllerTest extends WebTestCase
{
    public function testLoginWithoutAuth(): void
    {
        $client = static::createClient();

        $client->request('GET', '/s/l');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testLoginWithoutPreviousPageButWithAuth(): void
    {
        $client = static::createClient();

        $client->request('GET', '/s/l', [], [], [
            'PHP_AUTH_USER' => 'renaud',
            'PHP_AUTH_PW'   => 'douze',
        ]);

        $this->assertResponseStatusCodeSame(302);

        $crawler = $client->followRedirect();

        $this->assertEquals('http://localhost/fr/', $crawler->getUri());
    }

    public function testLoginWithPreviousPageAndAuth(): void
    {
        $client = static::createClient();

        $client->request('GET', '/fr/album/demo');

        $client->request('GET', '/s/l', [], [], [
            'PHP_AUTH_USER' => 'renaud',
            'PHP_AUTH_PW'   => 'douze',
        ]);

        $this->assertResponseStatusCodeSame(302);

        $crawler = $client->followRedirect();

        $this->assertEquals('http://localhost/fr/album/demo', $crawler->getUri());
    }

    public function testLoginWithPreviousPageIsLoginAndAuth(): void
    {
        $client = static::createClient();

        $client->request('GET', '/s/l', [], [], [
            'PHP_AUTH_USER' => 'renaud',
            'PHP_AUTH_PW'   => 'douze',
        ]);

        $client->request('GET', '/s/l', [], [], [
            'PHP_AUTH_USER' => 'renaud',
            'PHP_AUTH_PW'   => 'douze',
        ]);

        $this->assertResponseStatusCodeSame(302);

        $crawler = $client->followRedirect();

        $this->assertEquals('http://localhost/fr/', $crawler->getUri());
    }
}
