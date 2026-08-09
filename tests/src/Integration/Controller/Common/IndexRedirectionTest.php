<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Common;

use App\Controller\HomeController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(HomeController::class)]
final class IndexRedirectionTest extends WebTestCase
{
    #[Test]
    public function redirection(): void
    {
        $client = self::createClient();

        $client->request('GET', '/');

        $this->assertResponseStatusCodeSame(301);
        $crawler = $client->followRedirect();

        $this->assertEquals('http://localhost/fr', $crawler->getUri());
    }
}
