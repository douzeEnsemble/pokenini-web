<?php

declare(strict_types=1);

namespace App\Tests\Functional\Album\Filter;

use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Common\Traits\TestSetUp;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FamilyTest extends WebTestCase
{
    use TestNavTrait;
    use TestSetUp;

    public function testFilterFamilyBulbasaur(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr/album/demo?f=bulbasaur&t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertCountFilter($crawler, 6, '.album-case');

        $this->assertCountFilter($crawler, 0, 'h2.box');
        $this->assertCountFilter($crawler, 1, '#bulbasaur');
        $this->assertCountFilter($crawler, 1, '#venusaur-f');
        $this->assertCountFilter($crawler, 0, '#charmander');
        $this->assertCountFilter($crawler, 0, '#wartortle');

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

    public function testFilterFamilySquirtle(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr/album/demo?f=squirtle&t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertCountFilter($crawler, 5, '.album-case');

        $this->assertCountFilter($crawler, 0, 'h2.box');
        $this->assertCountFilter($crawler, 0, '#bulbasaur');
        $this->assertCountFilter($crawler, 0, '#venusaur-f');
        $this->assertCountFilter($crawler, 0, '#charmander');
        $this->assertCountFilter($crawler, 1, '#wartortle');

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

    public function testOwnerFilterFamilyBulbasaur(): void
    {
        $client = static::createClient();

        $user = new User('789465465489');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/demo?f=bulbasaur');

        $this->assertCountFilter($crawler, 6, '.album-case');

        $this->assertCountFilter($crawler, 0, 'h2.box');
        $this->assertCountFilter($crawler, 1, '#bulbasaur');
        $this->assertCountFilter($crawler, 1, '#venusaur-f');
        $this->assertCountFilter($crawler, 0, '#charmander');
        $this->assertCountFilter($crawler, 0, '#wartortle');

        $this->assertCountFilter($crawler, 12, '.toast');
        $this->assertCountFilter($crawler, 6, '.toast.text-bg-success');
        $this->assertCountFilter($crawler, 6, '.toast.text-bg-danger');

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

    public function testFilterFamilyUnknown(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr/album/demo?f=unknown&t=7b52009b64fd0a2a49e6d8a939753077792b0554');

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
