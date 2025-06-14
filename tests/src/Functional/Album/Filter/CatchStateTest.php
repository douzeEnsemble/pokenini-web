<?php

declare(strict_types=1);

namespace App\Tests\Functional\Album\Filter;

use App\Controller\AlbumIndexController;
use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(AlbumIndexController::class)]
class CatchStateTest extends WebTestCase
{
    use TestNavTrait;

    public function testFilterCatchStateNo(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr/album/demo?cs=no&t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertCountFilter($crawler, 16, '.album-case');

        $this->assertCountFilter($crawler, 0, 'h2.box');
        $this->assertCountFilter($crawler, 1, '#bulbasaur');
        $this->assertCountFilter($crawler, 0, '#venusaur-f');
        $this->assertCountFilter($crawler, 0, '#charmander');
        $this->assertCountFilter($crawler, 1, '#charizard');

        $this->assertCountFilter($crawler, 0, '.toast');

        $this->assertCountFilter($crawler, 13, 'table a');
        $this->assertEquals(
            '/fr/album/demo?cs=no&t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $crawler->filter('table a')->first()->attr('href')
        );
        $this->assertEquals(
            'Filtre les “Non”',
            $crawler->filter('table a')->first()->attr('data-bs-title')
        );
        $this->assertEquals(
            '/fr/album/demo?cs=!no&t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $crawler->filter('table a')->eq(1)->attr('href')
        );
        $this->assertEquals(
            'Filtre tous sauf les “Non”',
            $crawler->filter('table a')->eq(1)->attr('data-bs-title')
        );
        $this->assertEquals(
            '/fr/album/demo?t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $crawler->filter('table a')->last()->attr('href')
        );
        $this->assertEquals(
            'Toute la collection',
            $crawler->filter('table a')->last()->attr('data-bs-title')
        );
        $this->assertCountFilter($crawler, 1, '.progress');
        $this->assertCountFilter($crawler, 6, '.progress-bar');

        $this->assertEquals(
            '100%',
            $crawler->filter('.progress-bar.catch-state-no')->text()
        );
        $this->assertEmpty(
            $crawler->filter('.progress-bar.catch-state-toevolve')->text()
        );
        $this->assertEmpty(
            $crawler->filter('.progress-bar.catch-state-tobreed')->text()
        );
        $this->assertEmpty(
            $crawler->filter('.progress-bar.catch-state-totransfer')->text()
        );
        $this->assertEquals(
            '0%',
            $crawler->filter('.progress-bar.catch-state-yes')->text()
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
        $this->assertCountFilter($crawler, 0, '#charizard');

        $this->assertCountFilter($crawler, 0, '.toast');

        $this->assertCountFilter($crawler, 13, 'table a');
        $this->assertEquals(
            '/fr/album/demo?cs=no&t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $crawler->filter('table a')->first()->attr('href')
        );
        $this->assertEquals(
            '/fr/album/demo?t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $crawler->filter('table a')->last()->attr('href')
        );
        $this->assertCountFilter($crawler, 1, '.progress');
        $this->assertCountFilter($crawler, 6, '.progress-bar');

        $this->assertEquals(
            '0%',
            $crawler->filter('.progress-bar.catch-state-no')->text()
        );
        $this->assertEmpty(
            $crawler->filter('.progress-bar.catch-state-toevolve')->text()
        );
        $this->assertEmpty(
            $crawler->filter('.progress-bar.catch-state-tobreed')->text()
        );
        $this->assertEmpty(
            $crawler->filter('.progress-bar.catch-state-totransfer')->text()
        );
        $this->assertEquals(
            '100%',
            $crawler->filter('.progress-bar.catch-state-yes')->text()
        );
    }

    public function testOwnerFilterCatchStateYes(): void
    {
        $client = static::createClient();

        $user = new User('789465465489', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/demo?cs=yes');

        $this->assertCountFilter($crawler, 2, '.album-case');

        $this->assertCountFilter($crawler, 0, 'h2.box');
        $this->assertCountFilter($crawler, 0, '#bulbasaur');
        $this->assertCountFilter($crawler, 0, '#venusaur-f');
        $this->assertCountFilter($crawler, 1, '#charmander');
        $this->assertCountFilter($crawler, 0, '#charizard');

        $this->assertCountFilter($crawler, 4, '.toast');
        $this->assertCountFilter($crawler, 2, '.toast.text-bg-success');
        $this->assertCountFilter($crawler, 2, '.toast.text-bg-danger');

        $this->assertCountFilter($crawler, 13, 'table a');
        $this->assertEquals(
            '/fr/album/demo?cs=no',
            $crawler->filter('table a')->first()->attr('href')
        );
        $this->assertEquals(
            '/fr/album/demo',
            $crawler->filter('table a')->last()->attr('href')
        );
        $this->assertCountFilter($crawler, 1, '.progress');
        $this->assertCountFilter($crawler, 6, '.progress-bar');

        $this->assertEquals(
            '0%',
            $crawler->filter('.progress-bar.catch-state-no')->text()
        );
        $this->assertEmpty(
            $crawler->filter('.progress-bar.catch-state-toevolve')->text()
        );
        $this->assertEmpty(
            $crawler->filter('.progress-bar.catch-state-tobreed')->text()
        );
        $this->assertEmpty(
            $crawler->filter('.progress-bar.catch-state-totransfer')->text()
        );
        $this->assertEquals(
            '100%',
            $crawler->filter('.progress-bar.catch-state-yes')->text()
        );
    }

    public function testFilterCatchStateUnknown(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr/album/demo?cs=unknown&t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertCountFilter($crawler, 0, '.album-case');

        $this->assertCountFilter($crawler, 0, 'h2.box');

        $this->assertCountFilter($crawler, 13, 'table a');
        $this->assertEquals(
            '/fr/album/demo?cs=no&t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $crawler->filter('table a')->first()->attr('href')
        );
        $this->assertEquals(
            '/fr/album/demo?t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $crawler->filter('table a')->last()->attr('href')
        );
        $this->assertCountFilter($crawler, 1, '.progress');
        $this->assertCountFilter($crawler, 6, '.progress-bar');

        $this->assertEquals(
            '0%',
            $crawler->filter('.progress-bar.catch-state-no')->text()
        );
        $this->assertEmpty(
            $crawler->filter('.progress-bar.catch-state-toevolve')->text()
        );
        $this->assertEmpty(
            $crawler->filter('.progress-bar.catch-state-tobreed')->text()
        );
        $this->assertEmpty(
            $crawler->filter('.progress-bar.catch-state-totransfer')->text()
        );
        $this->assertEquals(
            '0%',
            $crawler->filter('.progress-bar.catch-state-yes')->text()
        );
    }

    public function testFilterCatchStateNegativeNo(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/fr/album/demo?cs=!no&t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertCountFilter($crawler, 9, '.album-case');

        $this->assertCountFilter($crawler, 0, 'h2.box');
        $this->assertCountFilter($crawler, 0, '#bulbasaur');
        $this->assertCountFilter($crawler, 1, '#venusaur-f');
        $this->assertCountFilter($crawler, 1, '#charmander');
        $this->assertCountFilter($crawler, 0, '#charizard');

        $this->assertCountFilter($crawler, 0, '.toast');

        $this->assertCountFilter($crawler, 13, 'table a');
        $this->assertEquals(
            '/fr/album/demo?cs=no&t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $crawler->filter('table a')->first()->attr('href')
        );
        $this->assertEquals(
            '/fr/album/demo?t=7b52009b64fd0a2a49e6d8a939753077792b0554',
            $crawler->filter('table a')->last()->attr('href')
        );
        $this->assertCountFilter($crawler, 1, '.progress');
        $this->assertCountFilter($crawler, 6, '.progress-bar');

        $this->assertEquals(
            '0%',
            $crawler->filter('.progress-bar.catch-state-no')->text()
        );
        $this->assertEmpty(
            $crawler->filter('.progress-bar.catch-state-toevolve')->text()
        );
        $this->assertEmpty(
            $crawler->filter('.progress-bar.catch-state-tobreed')->text()
        );
        $this->assertEmpty(
            $crawler->filter('.progress-bar.catch-state-totransfer')->text()
        );
        $this->assertEquals(
            '33.33%',
            $crawler->filter('.progress-bar.catch-state-yes')->text()
        );
    }
}
