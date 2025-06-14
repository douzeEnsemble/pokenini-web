<?php

declare(strict_types=1);

namespace App\Tests\Functional\Common;

use App\Tests\Common\Traits\TestNavTrait;
use PHPUnit\Framework\Attributes\CoversNothing;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversNothing]
class ErrorPageTest extends WebTestCase
{
    use TestNavTrait;

    public function testError404(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr/_error/404');

        $this->assertResponseStatusCodeSame(404);

        $this->assertEquals("Pokénini La page n'a pas été trouvée", $crawler->filter('title')->text());

        $this->assertCountFilter($crawler, 1, '#main-container h1');
        $this->assertCountFilter($crawler, 1, '#main-container p');
        $this->assertCountFilter($crawler, 1, '#main-container a');
    }

    public function testError500(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr/_error/500');

        $this->assertResponseStatusCodeSame(500);

        $this->assertEquals("Pokénini Y'a eu une merde. C'est pas toi, c'est nous", $crawler->filter('title')->text());

        $this->assertCountFilter($crawler, 1, '#main-container h1');
        $this->assertCountFilter($crawler, 1, '#main-container p');
        $this->assertCountFilter($crawler, 1, '#main-container a');
    }

    public function testError512(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr/_error/512');

        $this->assertResponseStatusCodeSame(512);

        $this->assertEquals("Pokénini Y'a eu une erreur", $crawler->filter('title')->text());

        $this->assertCountFilter($crawler, 1, '#main-container h1');
        $this->assertCountFilter($crawler, 1, '#main-container p');
        $this->assertCountFilter($crawler, 1, '#main-container a');
    }
}
