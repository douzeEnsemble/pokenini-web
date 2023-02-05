<?php

declare(strict_types=1);

namespace App\Tests\Functional\Admin;

use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ActionInvalidateTest extends WebTestCase
{
    use TestNavTrait;

    /**
     * @dataProvider invalidateSuccessProvider
     */
    public function testInvalidateSuccess(string $name): void
    {

        $client = static::createClient();

        $user = new User('8764532');
        $user->addAdminRole();
        $client->loginUser($user);

        $client->request('GET', "/fr/istration/action/invalidate/$name");

        $this->assertResponseStatusCodeSame(302);
        $crawler = $client->followRedirect();

        $this->assertCountFilter($crawler, 1, '.list-group-item-success');
    }

    /**
     * @dataProvider invalidateNotExistsProvider
     */
    public function testInvalidateNotExists(string $name): void
    {
        $client = static::createClient();

        $user = new User('8764532');
        $user->addAdminRole();
        $client->loginUser($user);

        $client->catchExceptions(false);

        $this->expectException(NotFoundHttpException::class);

        $client->request('GET', "/fr/istration/action/invalidate/$name");
    }

    public function testAdminNonAdmin(): void
    {
        $client = static::createClient();

        $user = new User('8764532');
        $client->loginUser($user);

        $client->catchExceptions(false);

        $this->expectException(AccessDeniedException::class);

        $client->request('GET', "/fr/istration/action/invalidate/catch_states");
    }

    /**
     * @return string[][]
     */
    public function invalidateSuccessProvider(): array
    {
        return [
            ['catch_states'],
            ['dex'],
            ['albums'],
            ['reports'],
        ];
    }

    /**
     * @return string[][]
     */
    public function invalidateNotExistsProvider(): array
    {
        return [
            ['labels'],
            ['games_and_dex'],
            ['pokemons'],
            ['regional_dex_numbers'],
            ['games_availabilities'],
            ['game_bundles_availabilities'],
            ['dex_availabilities'],
        ];
    }
}
