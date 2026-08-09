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
final class LocaleTest extends WebTestCase
{
    #[Test]
    public function localeOk(): void
    {
        $client = self::createClient();

        $client->request('GET', '/fr');

        $this->assertResponseStatusCodeSame(200);
    }

    #[Test]
    public function localeNonOk(): void
    {
        $client = self::createClient();

        $client->request('GET', '/it');

        $this->assertResponseStatusCodeSame(404);
    }
}
