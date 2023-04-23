<?php

declare(strict_types=1);

namespace App\Tests\Functional\Admin;

use App\Security\User;
use App\Tests\Common\Traits\TestNavTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminInvalidateTest extends WebTestCase
{
    use TestNavTrait;

    /**
     * @dataProvider adminInvalidateSuccess
     */
    public function testAdminInvalidateSuccess(string $name): void
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
     * @dataProvider adminInvalidateError
     */
    public function testAdminInvalidateError(string $name): void
    {
        $client = static::createClient();

        $user = new User('8764532');
        $user->addAdminRole();
        $client->loginUser($user);

        $client->request('GET', "/fr/istration/action/invalidate/$name");

        $this->assertResponseStatusCodeSame(404);
    }

    /**
     * @return string[][]
     */
    public function adminInvalidateSuccess(): array
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
    public function adminInvalidateError(): array
    {
        return [
            ['labels'],
            ['games_and_dex'],
            ['pokemons'],
            ['regional_dex_numbers'],
            ['games_availabilities'],
            ['games_shinies_availabilities'],
            ['game_bundles_availabilities'],
            ['game_bundles_shinies_availabilities'],
            ['dex_availabilities'],
        ];
    }
}
