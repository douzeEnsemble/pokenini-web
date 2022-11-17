<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\AlbumController;

use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AlbumControllerDisplayFormTest extends WebTestCase
{
    use TestNavTrait;

    public function testDisplayForm(): void
    {
        $client = static::createClient();

        $user = new User('12');
        $user->addTrainerRole();
        $client->loginUser($user);

        $client->request('GET', '/fr/album/r/home?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $mainCrawler = $client->getCrawler();

        $this->assertEquals('Printemps', $mainCrawler->filter('#deerling-spring .album-case-forms')->text());
    }

    public function testNonDisplayForm(): void
    {
        $client = static::createClient();

        $user = new User('12');
        $user->addTrainerRole();
        $client->loginUser($user);

        $client->request('GET', '/fr/album/r/homepokemongo?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $mainCrawler = $client->getCrawler();

        $this->assertEquals(' ', $mainCrawler->filter('#deerling-spring .album-case-forms')->text());
    }
}
