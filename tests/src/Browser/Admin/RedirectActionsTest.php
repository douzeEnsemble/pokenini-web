<?php

declare(strict_types=1);

namespace App\Tests\Browser\Admin;

use App\Tests\Browser\AbstractBrowserTestCase;
use App\Tests\Utils\GetUserToken;
use Facebook\WebDriver\WebDriverBy;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Panther\Client;

/**
 * @internal
 */
#[CoversNothing]
final class RedirectActionsTest extends AbstractBrowserTestCase
{
    /**
     * Action/item combinations whose fixture (tests/resources/moco/Back/responses/action-logs.json)
     * reports the current run as still "pending" (created_at set, done_at null). Task 1/3 of the
     * force-pending-admin-action feature made their button always carry a `data-confirm-message`
     * attribute and pop a native `confirm()` on submit, so they can't use Panther's `submit()`
     * helper (it calls `createCrawler()` right after clicking, which throws
     * `UnexpectedAlertOpenException` while the alert is still open) — see ForceActionTest.
     *
     * @var list<string>
     */
    private const array PENDING_ACTION_ITEMS = [
        'update_games_collections_and_dex',
        'calculate_game_bundles_availabilities',
        'calculate_dex_availabilities',
    ];

    #[DataProvider('providerActionItems')]
    public function testActionItems(string $action, string $item): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken('109903422692691643666', 'TestProvider');
        $user->addAdminRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/istration/actions');

        match (\in_array("{$action}_{$item}", self::PENDING_ACTION_ITEMS, true)) {
            true => $this->clickPendingActionAndAcceptConfirm($client, $action, $item),
            false => $this->submitActionForm($client, $action, $item),
        };

        $rawUri = getenv('PANTHER_EXTERNAL_BASE_URI');
        $baseUri = rtrim(false !== $rawUri ? $rawUri : 'http://127.0.0.1:9080', '/');
        $expectedUrl = "{$baseUri}/fr/istration/actions#{$action}_{$item}";

        $client->wait()->until(static fn (): bool => $expectedUrl === $client->getCurrentURL());

        $this->assertSame(
            $expectedUrl,
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

    /**
     * Pending action items always show a `data-confirm-message` button (Task 1/3 of the
     * force-pending-admin-action feature) that pops a native `confirm()` on submit — Panther's
     * `submit()` helper can't be used here since it calls `createCrawler()` right after clicking,
     * which throws `UnexpectedAlertOpenException` while the alert is still open (see
     * ForceActionTest for the same pattern).
     */
    private function clickPendingActionAndAcceptConfirm(Client $client, string $action, string $item): void
    {
        $button = $client->findElement(WebDriverBy::cssSelector("#{$action}_{$item} button.admin-item-cta"));

        // The page nav (templates/_nav.html.twig) is `fixed-bottom`; a plain click() only scrolls
        // the element minimally into view, which can leave it directly behind that fixed bar and
        // throw ElementClickInterceptedException. Center it first so the click always lands on
        // the button itself.
        $client->executeScript('arguments[0].scrollIntoView({block: "center", inline: "nearest"});', [$button]);
        $button->click();
        $client->switchTo()->alert()->accept();
    }

    private function submitActionForm(Client $client, string $action, string $item): void
    {
        $form = $client->getCrawler()->filter("#{$action}_{$item} form")->form();
        $client->submit($form);
    }
}
