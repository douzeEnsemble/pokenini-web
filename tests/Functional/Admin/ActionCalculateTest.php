<?php

declare(strict_types=1);

namespace App\Tests\Functional\Admin;

use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ActionCalculateTest extends WebTestCase
{
    use TestNavTrait;
    use ReportsAssertionTrait;

    public function testAdminCalculateGamesBundlesAvailabilities(): void
    {
        $this->testAdminCalculate(
            'game_bundles_availabilities',
            [
                'Dispo des bundles' => '18',
            ]
        );
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

        $this->assertCountFilter($crawler, 1, '.list-group-item-danger');
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

    /**
     * @param array<string, string> $expectedReport
     */
    private function testAdminCalculate(string $name, array $expectedReport = []): void
    {
        $client = static::createClient();

        $user = new User('8764532');
        $user->addAdminRole();
        $client->loginUser($user);

        $client->request('GET', "/fr/istration/action/calculate/$name");

        $this->assertResponseStatusCodeSame(302);
        $crawler = $client->followRedirect();

        $this->assertCountFilter($crawler, 1, '.list-group-item-success');

        $this->assertConnectedNavBar($crawler);
        $this->assertFrenchLangSwitch($crawler);

        $this->assertCountFilter($crawler, 0, 'script[src="/js/album.js"]');

        $this->assertStringNotContainsString('const catchStates = JSON.parse', $crawler->outerHtml());
        $this->assertStringNotContainsString('watchCatchStates();', $crawler->outerHtml());

        $this->assertReport($crawler, $expectedReport);
    }
}
