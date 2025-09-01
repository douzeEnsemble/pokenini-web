<?php

declare(strict_types=1);

namespace App\Tests\Functional\Admin;

use App\Controller\AdminActionController;
use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @internal
 */
#[CoversClass(AdminActionController::class)]
class ActionInvalidateTest extends WebTestCase
{
    use TestNavTrait;

    #[DataProvider('providerInvalidateSuccess')]
    public function testInvalidateSuccess(string $name): void
    {
        $client = static::createClient();

        $user = new User('8764532', 'TestProvider', new AccessToken(['access_token' => sha1('8764532')]));
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $client->request('GET', "/fr/istration/action/invalidate/{$name}");

        $this->assertResponseStatusCodeSame(302);
        $crawler = $client->followRedirect();

        $this->assertCountFilter($crawler, 1, '.icon-square.bg-success');
        file_put_contents('tests/last.html', $client->getCrawler()->html());
    }

    /**
     * @return string[][]
     */
    public static function providerInvalidateSuccess(): array
    {
        return [
            ['labels'],
            ['dex'],
            ['albums'],
            ['reports'],
        ];
    }

    #[DataProvider('providerInvalidateNotExists')]
    public function testInvalidateNotExists(string $name): void
    {
        $client = static::createClient();

        $user = new User('8764532', 'TestProvider', new AccessToken(['access_token' => sha1('8764532')]));
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $client->catchExceptions(false);

        $this->expectException(NotFoundHttpException::class);

        $client->request('GET', "/fr/istration/action/invalidate/{$name}");
    }

    /**
     * @return string[][]
     */
    public static function providerInvalidateNotExists(): array
    {
        return [
            ['catch_states'],
            ['types'],
            ['games_collections_and_dex'],
            ['pokemons'],
            ['regional_dex_numbers'],
            ['games_availabilities'],
            ['games_shinies_availabilities'],
            ['game_bundles_availabilities'],
            ['game_bundles_shinies_availabilities'],
            ['dex_availabilities'],
            ['pokemon_availabilities'],
            ['collections'],
            ['collections_availabilities'],
        ];
    }

    public function testAdminNonAdmin(): void
    {
        $client = static::createClient();

        $user = new User('8764532', 'TestProvider', new AccessToken(['access_token' => sha1('8764532')]));
        $client->loginUser($user, 'web');

        $client->catchExceptions(false);

        $this->expectException(NotFoundHttpException::class);

        $client->request('GET', '/fr/istration/action/invalidate/catch_states');
    }
}
