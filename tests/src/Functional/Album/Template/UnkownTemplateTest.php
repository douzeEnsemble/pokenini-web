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
class UnkownTemplateTest extends WebTestCase
{
    use TestNavTrait;

    public function testDexUnknownTemplate(): void
    {
        $client = static::createClient();

        $user = new User('12', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request(
            'GET',
            '/fr/album/demounknowntemplate?t=7b52009b64fd0a2a49e6d8a939753077792b0554'
        );

        $this->assertCountFilter($crawler, 41, '.album-case.col');
        $this->assertCountFilter($crawler, 6, 'div.row.album-line', 0, '.album-case.col');
        $this->assertCountFilter($crawler, 6, 'div.row.album-line', 2, '.album-case.col');
        $this->assertCountFilter($crawler, 7, 'div.row.album-line');
        $this->assertCountFilter($crawler, 2, '.box');
        $this->assertCountFilter($crawler, 2, '.box h2');
    }

    public function testFilterDexUnknownTemplate(): void
    {
        $client = static::createClient();

        $user = new User('12', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request(
            'GET',
            '/fr/album/demounknowntemplate?cs=no&t=7b52009b64fd0a2a49e6d8a939753077792b0554'
        );

        $this->assertCountFilter($crawler, 36, '.album-case.col');
        $this->assertCountFilter($crawler, 36, 'div.row.album-line', 0, '.album-case.col');
        $this->assertCountFilter($crawler, 1, 'div.row.album-line');
        $this->assertCountFilter($crawler, 0, '.box');
        $this->assertCountFilter($crawler, 0, '.box h2');
    }
}
