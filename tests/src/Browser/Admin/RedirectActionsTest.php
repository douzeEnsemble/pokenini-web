<?php

declare(strict_types=1);

namespace App\Tests\Browser\Admin;

use App\Tests\Browser\AbstractBrowserTestCase;
use App\Security\User;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * @internal
 */
#[CoversNothing]
#[Group('browser-testing')]
class RedirectActionsTest extends AbstractBrowserTestCase
{
    #[DataProvider('providerActionItems')]
    public function testActionItems(string $action, string $item): void
    {
        $client = $this->getNewClient();

        $user = new User('109903422692691643666', 'TestProvider');
        $user->addAdminRole();
        $this->loginUser($client, $user);

        $client->request('GET', "/fr/istration/action/{$action}/{$item}");

        $this->assertSame(
            "http://127.0.0.1:9080/fr/istration#{$action}_{$item}",
            $client->getCurrentURL()
        );
    }

    /**
     * @return array<string, array<string, string>>
     */
    public static function providerActionItems(): array
    {
        return [
            'update_labels' => [
                'action' => 'update',
                'item' => 'labels',
            ],
            'update_games_collections_and_dex' => [
                'action' => 'update',
                'item' => 'games_collections_and_dex',
            ],
            'update_pokemons' => [
                'action' => 'update',
                'item' => 'pokemons',
            ],
            'update_regional_dex_numbers' => [
                'action' => 'update',
                'item' => 'regional_dex_numbers',
            ],
            'update_games_availabilities' => [
                'action' => 'update',
                'item' => 'games_availabilities',
            ],
            'update_games_shinies_availabilities' => [
                'action' => 'update',
                'item' => 'games_shinies_availabilities',
            ],
            'update_collections_availabilities' => [
                'action' => 'update',
                'item' => 'collections_availabilities',
            ],
            'calculate_game_bundles_availabilities' => [
                'action' => 'calculate',
                'item' => 'game_bundles_availabilities',
            ],
            'calculate_game_bundles_shinies_availabilities' => [
                'action' => 'calculate',
                'item' => 'game_bundles_shinies_availabilities',
            ],
            'calculate_dex_availabilities' => [
                'action' => 'calculate',
                'item' => 'dex_availabilities',
            ],
            'calculate_pokemon_availabilities' => [
                'action' => 'calculate',
                'item' => 'pokemon_availabilities',
            ],
            'invalidate_labels' => [
                'action' => 'invalidate',
                'item' => 'labels',
            ],
            'invalidate_dex' => [
                'action' => 'invalidate',
                'item' => 'dex',
            ],
            'invalidate_albums' => [
                'action' => 'invalidate',
                'item' => 'albums',
            ],
        ];
    }
}
