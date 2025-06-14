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
class ActionUpdateTest extends WebTestCase
{
    use TestNavTrait;

    public function testAdminUpdateLabels(): void
    {
        $this->testAdminUpdate('labels');
    }

    public function testAdminUpdateGamesCollectionsAndDex(): void
    {
        $this->testAdminUpdate('games_collections_and_dex');
    }

    public function testAdminUpdatePokemons(): void
    {
        $this->testAdminUpdate('pokemons');
    }

    public function testAdminUpdateRegionalDexNumbers(): void
    {
        $this->testAdminUpdate('regional_dex_numbers');
    }

    public function testAdminUpdateGamesAvailabilities(): void
    {
        $this->testAdminUpdate('games_availabilities');
    }

    public function testAdminUpdateGamesShiniesAvailabilities(): void
    {
        $client = static::createClient();

        $user = new User('8764532', 'TestProvider');
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        // We don't have to call 'action/update/games_shinies_availabilities' to see error
        // because it's the last action log we looking for
        $crawler = $client->request('GET', '/fr/istration');

        $this->assertResponseStatusCodeSame(200);

        $this->assertCountFilter($crawler, 0, '.icon-square.bg-success');
        $this->assertCountFilter($crawler, 1, '.icon-square.bg-danger');
        $this->assertCountFilter($crawler, 2, '.alert-danger');
        $this->assertSelectorTextSame('.alert-danger', 'Exception has been thrown for X reason');

        $this->assertConnectedNavBar($crawler);
        $this->assertFrenchLangSwitch($crawler);

        $this->assertCountFilter($crawler, 0, 'script[src="/js/album.js"]');

        $this->assertStringNotContainsString('const catchStates = JSON.parse', $crawler->outerHtml());
        $this->assertStringNotContainsString('watchCatchStates();', $crawler->outerHtml());
        $this->assertStringNotContainsString('const types = JSON.parse', $crawler->outerHtml());
    }

    public function testAdminUpdateCollections(): void
    {
        $this->testAdminUpdate('collections_availabilities');
    }

    public function testAdminUpdateUnknown(): void
    {
        $client = static::createClient();

        $user = new User('8764532', 'TestProvider');
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $client->catchExceptions(false);

        $this->expectException(NotFoundHttpException::class);

        $client->request('GET', '/fr/istration/action/update/truc');
    }

    public function testAdminNonAdmin(): void
    {
        $client = static::createClient();

        $user = new User('8764532', 'TestProvider');
        $client->loginUser($user, 'web');

        $client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);

        $client->request('GET', '/fr/istration/action/update/labels');
    }

    public function testAdminUpdateThenGoToIndex(): void
    {
        $client = static::createClient();

        $user = new User('8764532', 'TestProvider');
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $client->request('GET', '/fr/istration/action/update/labels');

        $this->assertResponseStatusCodeSame(302);
        $crawler = $client->followRedirect();

        $this->assertCountFilter($crawler, 1, '.icon-square.bg-success');
        $this->assertCountFilter($crawler, 0, '.icon-square.bg-warning');

        $crawler = $client->request('GET', '/fr/istration');

        $this->assertCountFilter($crawler, 0, '.icon-square.bg-success');
        $this->assertCountFilter($crawler, 0, '.icon-square.bg-warning');
    }

    private function testAdminUpdate(string $name): void
    {
        $client = static::createClient();

        $user = new User('8764532', 'TestProvider');
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $client->request('GET', "/fr/istration/action/update/{$name}");

        $this->assertResponseStatusCodeSame(302);
        $crawler = $client->followRedirect();
        $this->assertSame('http://localhost/fr/istration', $client->getRequest()->getUri());

        $this->assertCountFilter($crawler, 1, '.icon-square.bg-success');

        $this->assertConnectedNavBar($crawler);
        $this->assertFrenchLangSwitch($crawler);

        $this->assertCountFilter($crawler, 0, 'script[src="/js/album.js"]');

        $this->assertStringNotContainsString('const catchStates = JSON.parse', $crawler->outerHtml());
        $this->assertStringNotContainsString('watchCatchStates();', $crawler->outerHtml());
        $this->assertStringNotContainsString('const types = JSON.parse', $crawler->outerHtml());
    }
}
