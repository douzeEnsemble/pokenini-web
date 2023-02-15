<?php

declare(strict_types=1);

namespace App\Tests\Functional\Common;

use App\Tests\Common\Traits\TestSetUp;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class IndexRedirectionTest extends WebTestCase
{
    use TestSetUp;

    public function testRedirection(): void
    {
        $client = static::createClient();

        $client->request('GET', '/');

        $this->assertResponseStatusCodeSame(301);
        $crawler = $client->followRedirect();

        $this->assertEquals('http://localhost/fr', $crawler->getUri());
    }
}
