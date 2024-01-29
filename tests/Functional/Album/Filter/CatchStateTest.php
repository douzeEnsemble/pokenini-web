<?php

declare(strict_types=1);

namespace App\Tests\Functional\Album\Filter;

use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CatchStateTest extends WebTestCase
{
    use TestNavTrait;

    public function testFilterCatchStateNo(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr/album/demo?cs=no&t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertCountFilter($crawler, 12, '.album-case');

        $this->assertCountFilter($crawler, 0, 'h2.box');
        $this->assertCountFilter($crawler, 1, '#bulbasaur');
        $this->assertCountFilter($crawler, 0, '#venusaur-f');
        $this->assertCountFilter($crawler, 0, '#charmander');

        $this->assertCountFilter($crawler, 0, '.toast');

        $this->assertCountFilter($crawler, 7, 'table a');
        $this->assertEquals(
            '/fr/album/demo?t=7b52009b64fd0a2a49e6d8a939753077792b0554&cs=no',
            $crawler->filter('table a')->first()->attr('href')
        );
        $this->assertEquals(
            '/fr/album/demo?t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $crawler->filter('table a')->last()->attr('href')
        );
    }

    public function testFilterCatchStateYes(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr/album/demo?cs=yes&t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertCountFilter($crawler, 5, '.album-case');

        $this->assertCountFilter($crawler, 0, 'h2.box');
        $this->assertCountFilter($crawler, 0, '#bulbasaur');
        $this->assertCountFilter($crawler, 0, '#venusaur-f');
        $this->assertCountFilter($crawler, 1, '#charmander');

        $this->assertCountFilter($crawler, 0, '.toast');

        $this->assertCountFilter($crawler, 7, 'table a');
        $this->assertEquals(
            '/fr/album/demo?t=7b52009b64fd0a2a49e6d8a939753077792b0554&cs=no',
            $crawler->filter('table a')->first()->attr('href')
        );
        $this->assertEquals(
            '/fr/album/demo?t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $crawler->filter('table a')->last()->attr('href')
        );
    }

    public function testOwnerFilterCatchStateYes(): void
    {
        $client = static::createClient();

        $user = new User('789465465489');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/demo?cs=yes');

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
            '/fr/album/demo?cs=no',
            $crawler->filter('table a')->first()->attr('href')
        );
        $this->assertEquals(
            '/fr/album/demo',
            $crawler->filter('table a')->last()->attr('href')
        );
    }

    public function testFilterCatchStateUnknown(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr/album/demo?cs=unknown&t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertCountFilter($crawler, 0, '.album-case');

        $this->assertCountFilter($crawler, 0, 'h2.box');

        $this->assertCountFilter($crawler, 7, 'table a');
        $this->assertEquals(
            '/fr/album/demo?t=7b52009b64fd0a2a49e6d8a939753077792b0554&cs=no',
            $crawler->filter('table a')->first()->attr('href')
        );
        $this->assertEquals(
            '/fr/album/demo?t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $crawler->filter('table a')->last()->attr('href')
        );
    }
}
