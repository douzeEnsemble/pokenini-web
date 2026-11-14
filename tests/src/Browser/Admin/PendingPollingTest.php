<?php

declare(strict_types=1);

namespace App\Tests\Browser\Admin;

use App\Tests\Browser\AbstractBrowserTestCase;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 */
#[CoversNothing]
final class PendingPollingTest extends AbstractBrowserTestCase
{
    #[Test]
    public function pendingCardIsRefreshedWithoutReloadingThePage(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken('109903422692691644333', 'TestProvider');
        $user->addAdminRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/istration/update_data');

        // Marks the DOM node and the window: a swapped card loses the mark, a real navigation loses both.
        $client->executeScript(
            'window.__noNav = true;'
            .'document.querySelector("#update_games_collections_and_dex").dataset.stale = "1";'
            .'document.querySelectorAll(".admin-item-refresh").forEach(l => l.hidden = false);'
        );

        $this->waitUntil(
            $client,
            static fn () => (bool) $client->executeScript(
                'return undefined === document.querySelector("#update_games_collections_and_dex").dataset.stale;'
            ),
            'the pending card to be swapped by the polling',
        );

        $this->assertTrue((bool) $client->executeScript('return window.__noNav === true;'));
        $this->assertTrue((bool) $client->executeScript(
            'return "pending" === document.querySelector("#update_games_collections_and_dex").dataset.currentState;'
        ));
        $this->assertTrue((bool) $client->executeScript(
            'return document.querySelector("#update_games_collections_and_dex .admin-item-refresh").hidden;'
        ));
    }
}
