<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Admin;

use App\Controller\AdminActionController;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @internal
 */
#[CoversClass(AdminActionController::class)]
#[Group('api-mocked-testing')]
final class ActionUpdateTest extends WebTestCase
{
    use TestNavTrait;

    /**
     * Items sharing the 'update' action verb are split across two dedicated pages
     * (see AdminUpdateAvailabilitiesController / AdminUpdateDataController); this mirrors
     * AdminActionController::resolveRedirectRoute()'s own mapping.
     *
     * @var list<string>
     */
    private const array AVAILABILITIES_ITEMS = [
        'games_availabilities',
        'games_shinies_availabilities',
        'collections_availabilities',
    ];

    #[Test]
    public function adminUpdateLabels(): void
    {
        $this->testAdminUpdate('labels');
    }

    #[Test]
    public function adminUpdateGamesCollectionsAndDex(): void
    {
        $this->testAdminUpdate('games_collections_and_dex');
    }

    #[Test]
    public function adminUpdatePokemons(): void
    {
        $this->testAdminUpdate('pokemons');
    }

    #[Test]
    public function adminUpdateRegionalDexNumbers(): void
    {
        $this->testAdminUpdate('regional_dex_numbers');
    }

    #[Test]
    public function adminUpdateGamesAvailabilities(): void
    {
        $this->testAdminUpdate('games_availabilities');
    }

    #[Test]
    public function adminUpdateGamesShiniesAvailabilities(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        // We don't have to call 'action/update/games_shinies_availabilities' to see error
        // because it's the last action log we looking for
        $crawler = $client->request('GET', '/fr/istration/update_availabilities');

        $this->assertResponseStatusCodeSame(200);

        $this->assertCountFilter($crawler, 0, '#update_games_shinies_availabilities .icon-square.bg-success');
        $this->assertCountFilter($crawler, 1, '#update_games_shinies_availabilities .icon-square.bg-danger');
        $this->assertCountFilter($crawler, 1, '#update_games_shinies_availabilities .alert-danger');
        $this->assertSelectorTextSame('#update_games_shinies_availabilities .alert-danger', 'Exception has been thrown for X reason');

        $this->assertConnectedNavBar($crawler);
        $this->assertFrenchLangSwitch($crawler);

        $this->assertCountFilter($crawler, 0, 'script[src="/js/album.js"]');

        $this->assertStringNotContainsString('const catchStates = JSON.parse', $crawler->outerHtml());
        $this->assertStringNotContainsString('watchCatchStates();', $crawler->outerHtml());
        $this->assertStringNotContainsString('const types = JSON.parse', $crawler->outerHtml());
    }

    #[Test]
    public function adminUpdateCollections(): void
    {
        $this->testAdminUpdate('collections_availabilities');
    }

    #[Test]
    public function adminUpdateUnknown(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $client->catchExceptions(false);

        $this->expectException(NotFoundHttpException::class);

        $client->request('GET', '/fr/istration/action/update/truc');
    }

    #[Test]
    public function adminNonAdmin(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $client->loginUser($user, 'web');

        $client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);

        $client->request('POST', '/fr/istration/action/update/labels');
    }

    #[Test]
    public function adminUpdateThenGoToIndex(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/istration/update_data');
        $form = $crawler->filter('#update_labels form')->form();
        $client->submit($form);

        $this->assertResponseStatusCodeSame(302);
        $crawler = $client->followRedirect();

        $this->assertCountFilter($crawler, 1, '#update_labels .icon-square.bg-success');
        $this->assertCountFilter($crawler, 0, '#update_labels .icon-square.bg-warning');

        $crawler = $client->request('GET', '/fr/istration/update_data');

        $this->assertCountFilter($crawler, 0, '#update_labels .icon-square.bg-warning');
    }

    private function testAdminUpdate(string $name): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $page = \in_array($name, self::AVAILABILITIES_ITEMS, true)
            ? '/fr/istration/update_availabilities'
            : '/fr/istration/update_data';

        $crawler = $client->request('GET', $page);
        $form = $crawler->filter("#update_{$name} form")->form();
        $client->submit($form);

        $this->assertResponseStatusCodeSame(302);
        $crawler = $client->followRedirect();
        $this->assertSame("http://localhost{$page}", $client->getRequest()->getUri());

        $this->assertSame(
            'Pokénini Administration',
            $crawler->filter('title')->text()
        );

        $this->assertSame(
            'Administration',
            $crawler->filter('h1')->text()
        );

        $this->assertCountFilter($crawler, 1, "#update_{$name} .icon-square.bg-success");

        $this->assertConnectedNavBar($crawler);
        $this->assertFrenchLangSwitch($crawler);

        $this->assertCountFilter($crawler, 0, 'script[src="/js/album.js"]');

        $this->assertStringNotContainsString('const catchStates = JSON.parse', $crawler->outerHtml());
        $this->assertStringNotContainsString('watchCatchStates();', $crawler->outerHtml());
        $this->assertStringNotContainsString('const types = JSON.parse', $crawler->outerHtml());
    }
}
