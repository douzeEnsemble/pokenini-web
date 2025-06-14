<?php

declare(strict_types=1);

namespace App\Tests\Functional\Admin;

use App\Controller\AdminActionController;
use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @internal
 */
#[CoversClass(AdminActionController::class)]
class ActionCalculateTest extends WebTestCase
{
    use TestNavTrait;

    public function testAdminCalculateGamesBundlesAvailabilities(): void
    {
        $this->testAdminCalculate('game_bundles_availabilities');
    }

    public function testAdminCalculateGamesBundlesShiniesAvailabilities(): void
    {
        $this->testAdminCalculate('game_bundles_shinies_availabilities');
    }

    public function testAdminCalculatePokemonAvailabilities(): void
    {
        $this->testAdminCalculate('pokemon_availabilities');
    }

    public function testAdminCalculateDexAvailabilities(): void
    {
        $client = static::createClient();

        $user = new User('8764532', 'TestProvider');
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        // For testing purpose, this case will fail in API side
        $client->request('GET', '/fr/istration/action/calculate/dex_availabilities');

        $this->assertResponseStatusCodeSame(302);
        $crawler = $client->followRedirect();

        $this->assertCountFilter($crawler, 0, '.icon-square.bg-success');
        $this->assertCountFilter($crawler, 2, '.icon-square.bg-danger');
        $this->assertCountFilter($crawler, 3, '.alert-danger');
        $this->assertSelectorTextSame(
            '.admin-item-calculate_dex_availabilities .alert',
            'HTTP/1.1 500 Internal Server Error returned for'
                .' "http://moco.api.test/istration/calculate/dex_availabilities".'
        );
    }

    public function testAdminCalculateWithErrorsThenGoToIndex(): void
    {
        $client = static::createClient();

        $user = new User('8764532', 'TestProvider');
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        // For testing purpose, this case will fail in API side
        $client->request('GET', '/fr/istration/action/calculate/dex_availabilities');

        $this->assertResponseStatusCodeSame(302);
        $crawler = $client->followRedirect();

        $this->assertCountFilter($crawler, 0, '.icon-square.bg-success');
        $this->assertCountFilter($crawler, 2, '.icon-square.bg-danger');
        $this->assertCountFilter($crawler, 3, '.alert-danger');
        $this->assertCountFilter($crawler, 1, '.admin-item-calculate_dex_availabilities .alert');
        $this->assertSelectorTextSame(
            '.admin-item-calculate_dex_availabilities .alert',
            'HTTP/1.1 500 Internal Server Error returned for'
                .' "http://moco.api.test/istration/calculate/dex_availabilities".'
        );

        $crawler = $client->request('GET', '/fr/istration');

        $this->assertCountFilter($crawler, 0, '.icon-square.bg-success');
        $this->assertCountFilter($crawler, 1, '.icon-square.bg-danger');
        $this->assertCountFilter($crawler, 2, '.alert-danger');
    }

    public function testAdminCalculateUnknown(): void
    {
        $client = static::createClient();

        $user = new User('8764532', 'TestProvider');
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $client->catchExceptions(false);

        $this->expectException(NotFoundHttpException::class);

        $client->request('GET', '/fr/istration/action/calculate/truc');
    }

    public function testAdminNonAdmin(): void
    {
        $client = static::createClient();

        $user = new User('8764532', 'TestProvider');
        $client->loginUser($user, 'web');

        $client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);

        $client->request('GET', '/fr/istration/action/calculate/dex_availabilities');
    }

    private function testAdminCalculate(string $name): void
    {
        $client = static::createClient();

        $user = new User('8764532', 'TestProvider');
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $client->request('GET', "/fr/istration/action/calculate/{$name}");

        $this->assertResponseStatusCodeSame(302);
        $crawler = $client->followRedirect();

        file_put_contents('tests/last.html', $client->getCrawler()->html());

        $this->assertCountFilter($crawler, 1, '.icon-square.bg-success');
        $this->assertCountFilter($crawler, 1, '.icon-square.bg-danger');

        $this->assertConnectedNavBar($crawler);
        $this->assertFrenchLangSwitch($crawler);

        $this->assertCountFilter($crawler, 0, 'script[src="/js/album.js"]');

        $this->assertStringNotContainsString('const catchStates = JSON.parse', $crawler->outerHtml());
        $this->assertStringNotContainsString('watchCatchStates();', $crawler->outerHtml());
        $this->assertStringNotContainsString('const types = JSON.parse', $crawler->outerHtml());
    }
}
