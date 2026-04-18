<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Admin;

use App\Controller\AdminActionController;
use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @internal
 */
#[CoversClass(AdminActionController::class)]
#[Group('api-mocked-testing')]
final class ActionCalculateTest extends WebTestCase
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
        $client = self::createClient();

        $user = new User('8764532', 'TestProvider', new AccessToken(['access_token' => sha1('8764532')]));
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
                .' "http://moco.back/istration/action/calculate/dex_availabilities".'
        );
    }

    public function testAdminCalculateWithErrorsThenGoToIndex(): void
    {
        $client = self::createClient();

        $user = new User('8764532', 'TestProvider', new AccessToken(['access_token' => sha1('8764532')]));
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
                .' "http://moco.back/istration/action/calculate/dex_availabilities".'
        );

        $crawler = $client->request('GET', '/fr/istration');

        $this->assertCountFilter($crawler, 0, '.icon-square.bg-success');
        $this->assertCountFilter($crawler, 1, '.icon-square.bg-danger');
        $this->assertCountFilter($crawler, 2, '.alert-danger');
    }

    public function testAdminCalculateUnknown(): void
    {
        $client = self::createClient();

        $user = new User('8764532', 'TestProvider', new AccessToken(['access_token' => sha1('8764532')]));
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $client->catchExceptions(false);

        $this->expectException(NotFoundHttpException::class);

        $client->request('GET', '/fr/istration/action/calculate/truc');
    }

    public function testAdminNonAdmin(): void
    {
        $client = self::createClient();

        $user = new User('8764532', 'TestProvider', new AccessToken(['access_token' => sha1('8764532')]));
        $client->loginUser($user, 'web');

        $client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);

        $client->request('GET', '/fr/istration/action/calculate/dex_availabilities');
    }

    private function testAdminCalculate(string $name): void
    {
        $client = self::createClient();

        $user = new User('8764532', 'TestProvider', new AccessToken(['access_token' => sha1('8764532')]));
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $client->request('GET', "/fr/istration/action/calculate/{$name}");

        $this->assertResponseStatusCodeSame(302);
        $crawler = $client->followRedirect();

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
