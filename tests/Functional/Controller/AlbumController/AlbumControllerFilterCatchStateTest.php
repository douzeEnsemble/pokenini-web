<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\AlbumController;

use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AlbumControllerFilterCatchStateTest extends WebTestCase
{
    use TestNavTrait;

    public function testFilterCatchStateNo(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr/album/r/demo/no?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertCountFilter($crawler, 1718, '.album-case');

        $this->assertCountFilter($crawler, 0, 'h2.box');
        $this->assertCountFilter($crawler, 1, '#bulbasaur');
        $this->assertCountFilter($crawler, 0, '#venusaur-f');
        $this->assertCountFilter($crawler, 0, '#charmander');

        $this->assertCountFilter($crawler, 0, '.toast');

        $this->assertCountFilter($crawler, 7, 'table a');
        $this->assertEquals(
            '/fr/album/r/demo/no?t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $crawler->filter('table a')->first()->attr('href')
        );
        $this->assertEquals(
            '/fr/album/r/demo?t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $crawler->filter('table a')->last()->attr('href')
        );
    }

    public function testFilterCatchStateYes(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr/album/r/demo/yes?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertCountFilter($crawler, 9, '.album-case');

        $this->assertCountFilter($crawler, 0, 'h2.box');
        $this->assertCountFilter($crawler, 0, '#bulbasaur');
        $this->assertCountFilter($crawler, 0, '#venusaur-f');
        $this->assertCountFilter($crawler, 1, '#charmander');

        $this->assertCountFilter($crawler, 0, '.toast');

        $this->assertCountFilter($crawler, 7, 'table a');
        $this->assertEquals(
            '/fr/album/r/demo/no?t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $crawler->filter('table a')->first()->attr('href')
        );
        $this->assertEquals(
            '/fr/album/r/demo?t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $crawler->filter('table a')->last()->attr('href')
        );
    }

    public function testEditFilterCatchStateYes(): void
    {
        $client = static::createClient();

        $user = new User('789465465489');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/w/demo/yes');

        $this->assertCountFilter($crawler, 2, '.album-case');

        $this->assertCountFilter($crawler, 0, 'h2.box');
        $this->assertCountFilter($crawler, 0, '#bulbasaur');
        $this->assertCountFilter($crawler, 0, '#venusaur-f');
        $this->assertCountFilter($crawler, 1, '#charmander');

        $this->assertCountFilter($crawler, 4, '.toast');
        $this->assertCountFilter($crawler, 2, '.toast.text-bg-success');
        $this->assertCountFilter($crawler, 2, '.toast.text-bg-danger');

        $this->assertCountFilter($crawler, 7, 'table a');
        $this->assertEquals(
            '/fr/album/r/demo/no',
            $crawler->filter('table a')->first()->attr('href')
        );
        $this->assertEquals(
            '/fr/album/r/demo',
            $crawler->filter('table a')->last()->attr('href')
        );
    }

    public function testFilterCatchStateUnknown(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr/album/r/demo/unknown?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertCountFilter($crawler, 0, '.album-case');

        $this->assertCountFilter($crawler, 0, 'h2.box');

        $this->assertCountFilter($crawler, 7, 'table a');
        $this->assertEquals(
            '/fr/album/r/demo/no?t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $crawler->filter('table a')->first()->attr('href')
        );
        $this->assertEquals(
            '/fr/album/r/demo?t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $crawler->filter('table a')->last()->attr('href')
        );
    }
}
