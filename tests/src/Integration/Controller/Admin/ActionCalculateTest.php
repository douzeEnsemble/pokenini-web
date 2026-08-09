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
final class ActionCalculateTest extends WebTestCase
{
    use TestNavTrait;

    private const string PAGE = '/fr/istration/calculate_data';

    #[Test]
    public function adminCalculateGamesBundlesAvailabilities(): void
    {
        $this->testAdminCalculate('game_bundles_availabilities');
    }

    #[Test]
    public function adminCalculateGamesBundlesShiniesAvailabilities(): void
    {
        $this->testAdminCalculate('game_bundles_shinies_availabilities');
    }

    #[Test]
    public function adminCalculatePokemonAvailabilities(): void
    {
        $this->testAdminCalculate('pokemon_availabilities');
    }

    #[Test]
    public function adminCalculateDexAvailabilities(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        // For testing purpose, this case will fail in API side
        $crawler = $client->request('GET', self::PAGE);
        $form = $crawler->filter('#calculate_dex_availabilities form')->form();
        $client->submit($form);

        $this->assertResponseStatusCodeSame(302);
        $crawler = $client->followRedirect();

        $this->assertCountFilter($crawler, 0, '#calculate_dex_availabilities .icon-square.bg-success');
        $this->assertCountFilter($crawler, 1, '#calculate_dex_availabilities .icon-square.bg-danger');
        $this->assertCountFilter($crawler, 1, '.admin-item-calculate_dex_availabilities .alert-danger');
        $this->assertSelectorTextSame(
            '.admin-item-calculate_dex_availabilities .alert',
            'HTTP/1.1 500 Internal Server Error returned for'
                .' "http://moco.back/istration/action/calculate/dex_availabilities".'
        );
    }

    #[Test]
    public function adminCalculateWithErrorsThenGoToIndex(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        // For testing purpose, this case will fail in API side
        $crawler = $client->request('GET', self::PAGE);
        $form = $crawler->filter('#calculate_dex_availabilities form')->form();
        $client->submit($form);

        $this->assertResponseStatusCodeSame(302);
        $crawler = $client->followRedirect();

        $this->assertCountFilter($crawler, 1, '#calculate_dex_availabilities .icon-square.bg-danger');
        $this->assertCountFilter($crawler, 1, '.admin-item-calculate_dex_availabilities .alert-danger');
        $this->assertSelectorTextSame(
            '.admin-item-calculate_dex_availabilities .alert',
            'HTTP/1.1 500 Internal Server Error returned for'
                .' "http://moco.back/istration/action/calculate/dex_availabilities".'
        );

        $crawler = $client->request('GET', self::PAGE);

        $this->assertCountFilter($crawler, 0, '#calculate_dex_availabilities .icon-square.bg-success');
    }

    #[Test]
    public function adminCalculateUnknown(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $client->catchExceptions(false);

        $this->expectException(NotFoundHttpException::class);

        $client->request('GET', '/fr/istration/action/calculate/truc');
    }

    #[Test]
    public function adminNonAdmin(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $client->loginUser($user, 'web');

        $client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);

        $client->request('POST', '/fr/istration/action/calculate/dex_availabilities');
    }

    private function testAdminCalculate(string $name): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', self::PAGE);
        $form = $crawler->filter("#calculate_{$name} form")->form();
        $client->submit($form);

        $this->assertResponseStatusCodeSame(302);
        $crawler = $client->followRedirect();

        $this->assertSame(
            'Pokénini Administration',
            $crawler->filter('title')->text()
        );

        $this->assertSame(
            'Administration',
            $crawler->filter('h1')->text()
        );

        $this->assertCountFilter($crawler, 1, "#calculate_{$name} .icon-square.bg-success");

        $this->assertConnectedNavBar($crawler);
        $this->assertFrenchLangSwitch($crawler);

        $this->assertCountFilter($crawler, 0, 'script[src="/js/album.js"]');

        $this->assertStringNotContainsString('const catchStates = JSON.parse', $crawler->outerHtml());
        $this->assertStringNotContainsString('watchCatchStates();', $crawler->outerHtml());
        $this->assertStringNotContainsString('const types = JSON.parse', $crawler->outerHtml());
    }
}
