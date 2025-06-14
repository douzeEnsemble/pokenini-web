<?php

declare(strict_types=1);

namespace App\Tests\Functional\Album\Template;

use App\Controller\AlbumIndexController;
use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(AlbumIndexController::class)]
class List3Test extends WebTestCase
{
    use TestNavTrait;

    public function testDexList3Template(): void
    {
        $client = static::createClient();

        $user = new User('12', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/demolist3?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertCountFilter($crawler, 41, '.album-case.col');
        $this->assertCountFilter($crawler, 3, 'div.row.album-line', 0, '.album-case.col');
        $this->assertCountFilter($crawler, 3, 'div.row.album-line', 12, '.album-case.col');
        $this->assertCountFilter($crawler, 14, 'div.row.album-line');
        $this->assertCountFilter($crawler, 0, '.box');
    }

    public function testFilterDexList3Template(): void
    {
        $client = static::createClient();

        $user = new User('12', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/demolist3?cs=no&t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertCountFilter($crawler, 35, '.album-case.col');
        $this->assertCountFilter($crawler, 3, 'div.row.album-line', 0, '.album-case.col');
        $this->assertCountFilter($crawler, 3, 'div.row.album-line', 2, '.album-case.col');
        $this->assertCountFilter($crawler, 12, 'div.row.album-line');
        $this->assertCountFilter($crawler, 0, '.box');
    }
}
