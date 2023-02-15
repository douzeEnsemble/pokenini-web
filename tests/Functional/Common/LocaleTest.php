<?php

declare(strict_types=1);

namespace App\Tests\Functional\Common;

use App\Tests\Common\Traits\TestSetUp;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LocaleTest extends WebTestCase
{
    use TestSetUp;

    public function testLocaleOk(): void
    {
        $client = static::createClient();

        $client->request('GET', '/fr');

        $this->assertResponseStatusCodeSame(200);
    }

    public function testLocaleNonOk(): void
    {
        $client = static::createClient();

        $client->request('GET', '/it');

        $this->assertResponseStatusCodeSame(404);
    }
}
