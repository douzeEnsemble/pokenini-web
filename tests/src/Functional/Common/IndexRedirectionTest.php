<?php

declare(strict_types=1);

namespace App\Tests\Functional\Common;

use App\Controller\HomeController;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(HomeController::class)]
class IndexRedirectionTest extends WebTestCase
{
    public function testRedirection(): void
    {
        $client = static::createClient();

        $client->request('GET', '/');

        $this->assertResponseStatusCodeSame(301);
        $crawler = $client->followRedirect();

        $this->assertEquals('http://localhost/fr', $crawler->getUri());
    }
}
