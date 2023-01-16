<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\AlbumController\AlbumTemplate;

use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AlbumTemplateList5Test extends WebTestCase
{
    use TestNavTrait;

    public function testDexList5Template(): void
    {
        $client = static::createClient();

        $user = new User('12');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/r/demolist5?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertCountFilter($crawler, 1738, '.album-case.col');
        $this->assertCountFilter($crawler, 5, 'div.row.album-line', 0, '.album-case.col');
        $this->assertCountFilter($crawler, 5, 'div.row.album-line', 12, '.album-case.col');
        $this->assertCountFilter($crawler, 348, 'div.row.album-line');
        $this->assertCountFilter($crawler, 0, '.box');
    }

    public function testFilterDexList5Template(): void
    {
        $client = static::createClient();

        $user = new User('12');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/r/demolist5/no?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertCountFilter($crawler, 1732, '.album-case.col');
        $this->assertCountFilter($crawler, 5, 'div.row.album-line', 0, '.album-case.col');
        $this->assertCountFilter($crawler, 5, 'div.row.album-line', 12, '.album-case.col');
        $this->assertCountFilter($crawler, 347, 'div.row.album-line');
        $this->assertCountFilter($crawler, 0, '.box');
    }
}
