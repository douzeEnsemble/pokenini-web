<?php

declare(strict_types=1);

namespace App\Tests\Functional\Album\Display;

use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FormTest extends WebTestCase
{
    use TestNavTrait;

    public function testDisplayForm(): void
    {
        $client = static::createClient();

        $user = new User('12');
        $user->addTrainerRole();
        $client->loginUser($user);

        $client->request('GET', '/fr/album/home?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $crawler = $client->getCrawler();

        $this->assertEquals('♂️', $crawler->filter('#venusaur .album-case-forms')->text());
    }

    public function testNonDisplayForm(): void
    {
        $client = static::createClient();

        $user = new User('12');
        $user->addTrainerRole();
        $client->loginUser($user);

        $client->request('GET', '/fr/album/homepokemongo?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $crawler = $client->getCrawler();

        $this->assertEquals(' ', $crawler->filter('#venusaur .album-case-forms')->text());
    }
}
