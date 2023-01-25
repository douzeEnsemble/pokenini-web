<?php

declare(strict_types=1);

namespace App\Tests\Functional\Album\Template;

use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class List7Test extends WebTestCase
{
    use TestNavTrait;

    public function testDexList7Template(): void
    {
        $client = static::createClient();

        $user = new User('12');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/demolist7?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertCountFilter($crawler, 1738, '.album-case.col');
        $this->assertCountFilter($crawler, 7, 'div.row.album-line', 0, '.album-case.col');
        $this->assertCountFilter($crawler, 7, 'div.row.album-line', 12, '.album-case.col');
        $this->assertCountFilter($crawler, 249, 'div.row.album-line');
        $this->assertCountFilter($crawler, 0, '.box');
    }

    public function testFilterDexList7Template(): void
    {
        $client = static::createClient();

        $user = new User('12');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/demolist7/no?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertCountFilter($crawler, 1732, '.album-case.col');
        $this->assertCountFilter($crawler, 7, 'div.row.album-line', 0, '.album-case.col');
        $this->assertCountFilter($crawler, 7, 'div.row.album-line', 12, '.album-case.col');
        $this->assertCountFilter($crawler, 248, 'div.row.album-line');
        $this->assertCountFilter($crawler, 0, '.box');
    }
}
