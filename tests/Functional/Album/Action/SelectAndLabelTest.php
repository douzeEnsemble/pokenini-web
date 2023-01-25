<?php

declare(strict_types=1);

namespace App\Tests\Functional\Album\Action;

use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SelectAndLabelTest extends WebTestCase
{
    use TestNavTrait;

    public function testActionCatchStateGoldSilverCrystal(): void
    {
        $client = static::createClient();

        $user = new User('109903422692691643666');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/goldsilvercrystal');

        $this->assertCountFilter($crawler, 278, '.album-case');
        $this->assertCountFilter($crawler, 278, '.album-case-action');
        $this->assertCountFilter($crawler, 278, '.album-case-action[hidden]');
        $this->assertCountFilter($crawler, 278, '.album-case-catch-state');
        $this->assertCountFilter($crawler, 0, '.album-case-catch-state[hidden]');
        $this->assertCountFilter($crawler, 278, '.album-case-catch-state .album-case-catch-state-label');
        $this->assertCountFilter(
            $crawler,
            278,
            '.album-case-catch-state .album-case-catch-state-label .album-case-catch-state-edit-action'
        );
    }

    public function testActionCatchStateDemo(): void
    {
        $client = static::createClient();

        $user = new User('109903422692691643666');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/demo');

        $this->assertCountFilter($crawler, 1738, '.album-case');
        $this->assertCountFilter($crawler, 1738, '.album-case-action');
        $this->assertCountFilter($crawler, 1738, '.album-case-action[hidden]');
        $this->assertCountFilter($crawler, 1738, '.album-case-catch-state');
        $this->assertCountFilter($crawler, 0, '.album-case-catch-state[hidden]');
        $this->assertCountFilter($crawler, 1738, '.album-case-catch-state .album-case-catch-state-label');
        $this->assertCountFilter(
            $crawler,
            1738,
            '.album-case-catch-state .album-case-catch-state-label .album-case-catch-state-edit-action'
        );
    }

    public function testActionCatchStateDemoList3(): void
    {
        $client = static::createClient();

        $user = new User('109903422692691643666');
        $user->addTrainerRole();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/fr/album/demolist3');

        $this->assertCountFilter($crawler, 1738, '.album-case');
        $this->assertCountFilter($crawler, 1738, '.album-case-action');
        $this->assertCountFilter($crawler, 1738, '.album-case-action[hidden]');
        $this->assertCountFilter($crawler, 1738, '.album-case-catch-state');
        $this->assertCountFilter($crawler, 0, '.album-case-catch-state[hidden]');
        $this->assertCountFilter($crawler, 1738, '.album-case-catch-state .album-case-catch-state-label');
        $this->assertCountFilter(
            $crawler,
            1738,
            '.album-case-catch-state .album-case-catch-state-label .album-case-catch-state-edit-action'
        );
    }
}
