<?php

declare(strict_types=1);

namespace App\Tests\Functional\Admin;

use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ActionCalculateTest extends WebTestCase
{
    use TestNavTrait;

    public function testAdminCalculateGamesBundlesAvailabilities(): void
    {
        $this->testAdminCalculate('game_bundles_availabilities');
    }

    public function testAdminCalculateDexAvailabilities(): void
    {
        $client = static::createClient();

        $user = new User('8764532');
        $user->addAdminRole();
        $client->loginUser($user);

        # For testing purpose, this case will fail in API side
        $client->request('GET', "/fr/istration/action/calculate/dex_availabilities");

        $this->assertResponseStatusCodeSame(302);
        $crawler = $client->followRedirect();

        $this->assertCountFilter($crawler, 0, '.list-group-item-success');
        $this->assertCountFilter($crawler, 1, '.list-group-item-danger');
        $this->assertCountFilter($crawler, 1, '.alert-danger');
    }

    public function testAdminCalculateWithErrorsThenGoToIndex(): void
    {
        $client = static::createClient();

        $user = new User('8764532');
        $user->addAdminRole();
        $client->loginUser($user);

        # For testing purpose, this case will fail in API side
        $client->request('GET', '/fr/istration/action/calculate/dex_availabilities');

        $this->assertResponseStatusCodeSame(302);
        $crawler = $client->followRedirect();

        $this->assertCountFilter($crawler, 0, '.list-group-item-success');
        $this->assertCountFilter($crawler, 1, '.list-group-item-danger');
        $this->assertCountFilter($crawler, 1, '.alert-danger');

        $crawler = $client->request('GET', '/fr/istration');

        $this->assertCountFilter($crawler, 0, '.list-group-item-success');
        $this->assertCountFilter($crawler, 0, '.list-group-item-danger');
        $this->assertCountFilter($crawler, 0, '.alert-danger');
    }

    public function testAdminCalculateUnknown(): void
    {
        $client = static::createClient();

        $user = new User('8764532');
        $user->addAdminRole();
        $client->loginUser($user);

        $client->catchExceptions(false);

        $this->expectException(NotFoundHttpException::class);

        $client->request('GET', "/fr/istration/action/calculate/truc");
    }

    public function testAdminNonAdmin(): void
    {
        $client = static::createClient();

        $user = new User('8764532');
        $client->loginUser($user);

        $client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);

        $client->request('GET', "/fr/istration/action/calculate/dex_availabilities");
    }

    private function testAdminCalculate(string $name): void
    {
        $client = static::createClient();

        $user = new User('8764532');
        $user->addAdminRole();
        $client->loginUser($user);

        $client->request('GET', "/fr/istration/action/calculate/$name");

        $this->assertResponseStatusCodeSame(302);
        $crawler = $client->followRedirect();

        $this->assertCountFilter($crawler, 1, '.list-group-item-success');
        $this->assertCountFilter($crawler, 0, '.list-group-item-danger');

        $this->assertConnectedNavBar($crawler);
        $this->assertFrenchLangSwitch($crawler);

        $this->assertCountFilter($crawler, 0, 'script[src="/js/album.js"]');

        $this->assertStringNotContainsString('const catchStates = JSON.parse', $crawler->outerHtml());
        $this->assertStringNotContainsString('watchCatchStates();', $crawler->outerHtml());
    }
}
