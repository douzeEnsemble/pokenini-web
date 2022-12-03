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

        $this->assertCount(
            1718,
            $crawler->filter('.album-case')
        );

        $this->assertCount(0, $crawler->filter('h2.box'));
        $this->assertCount(1, $crawler->filter('#bulbasaur'));
        $this->assertCount(0, $crawler->filter('#venusaur-f'));
        $this->assertCount(0, $crawler->filter('#charmander'));

        $this->assertCount(0, $crawler->filter('.toast'));

        $this->assertCount(7, $crawler->filter('table a'));
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

        $this->assertCount(
            9,
            $crawler->filter('.album-case')
        );

        $this->assertCount(0, $crawler->filter('h2.box'));
        $this->assertCount(0, $crawler->filter('#bulbasaur'));
        $this->assertCount(0, $crawler->filter('#venusaur-f'));
        $this->assertCount(1, $crawler->filter('#charmander'));

        $this->assertCount(0, $crawler->filter('.toast'));

        $this->assertCount(7, $crawler->filter('table a'));
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

        $this->assertCount(
            2,
            $crawler->filter('.album-case')
        );

        $this->assertCount(0, $crawler->filter('h2.box'));
        $this->assertCount(0, $crawler->filter('#bulbasaur'));
        $this->assertCount(0, $crawler->filter('#venusaur-f'));
        $this->assertCount(1, $crawler->filter('#charmander'));

        $this->assertCount(4, $crawler->filter('.toast'));
        $this->assertCount(2, $crawler->filter('.toast.text-bg-success'));
        $this->assertCount(2, $crawler->filter('.toast.text-bg-danger'));

        $this->assertCount(7, $crawler->filter('table a'));
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

        $this->assertCount(0, $crawler->filter('.album-case'));

        $this->assertCount(0, $crawler->filter('h2.box'));

        $this->assertCount(7, $crawler->filter('table a'));
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
