<?php

namespace App\Tests\Functional\EventListener;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorage;

class RequestListenerTest extends WebTestCase
{
    public function testSetLang(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/');
        $this->assertEquals('Accueil', $crawler->filter('.nav-item')->first()->text());

        $crawler = $client->request('GET', '/?lang=en');
        $this->assertEquals('Home', $crawler->filter('.nav-item')->first()->text());

        $crawler = $client->request('GET', '/');
        $this->assertEquals('Home', $crawler->filter('.nav-item')->first()->text());
    }
}
