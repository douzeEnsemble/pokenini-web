<?php

declare(strict_types=1);

namespace App\Tests\Browser\Admin;

use App\Tests\Browser\AbstractBrowserTestCase;
use App\Tests\Utils\GetUserToken;
use Facebook\WebDriver\WebDriverBy;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
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
    #[Test]
    public function actionItems(string $action, string $item, string $section): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken('109903422692691643666', 'TestProvider');
        $user->addAdminRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/istration/actions');

        $this->activateTab($client, $section);

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

        // The redirect landed back on the default-active tab; the item lives in a
        // different pane until admin.js's activateTabForHash() reads the URL hash
        // and activates the right one — this is what this assertion proves.
        $this->assertSelectorWillBeVisible("#{$action}_{$item}");
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
                'section' => 'update_data',
            ],
            'update_games_collections_and_dex' => [
                'action' => 'update',
                'item' => 'games_collections_and_dex',
                'section' => 'update_data',
            ],
            'update_pokemons' => [
                'action' => 'update',
                'item' => 'pokemons',
                'section' => 'update_data',
            ],
            'update_regional_dex_numbers' => [
                'action' => 'update',
                'item' => 'regional_dex_numbers',
                'section' => 'update_data',
            ],
            'update_games_availabilities' => [
                'action' => 'update',
                'item' => 'games_availabilities',
                'section' => 'update_availabilities',
            ],
            'update_games_shinies_availabilities' => [
                'action' => 'update',
                'item' => 'games_shinies_availabilities',
                'section' => 'update_availabilities',
            ],
            'update_collections_availabilities' => [
                'action' => 'update',
                'item' => 'collections_availabilities',
                'section' => 'update_availabilities',
            ],
            'calculate_game_bundles_availabilities' => [
                'action' => 'calculate',
                'item' => 'game_bundles_availabilities',
                'section' => 'calculate_data',
            ],
            'calculate_game_bundles_shinies_availabilities' => [
                'action' => 'calculate',
                'item' => 'game_bundles_shinies_availabilities',
                'section' => 'calculate_data',
            ],
            'calculate_dex_availabilities' => [
                'action' => 'calculate',
                'item' => 'dex_availabilities',
                'section' => 'calculate_data',
            ],
            'calculate_pokemon_availabilities' => [
                'action' => 'calculate',
                'item' => 'pokemon_availabilities',
                'section' => 'calculate_data',
            ],
            'invalidate_labels' => [
                'action' => 'invalidate',
                'item' => 'labels',
                'section' => 'invalidate_data',
            ],
            'invalidate_catch_states' => [
                'action' => 'invalidate',
                'item' => 'catch_states',
                'section' => 'invalidate_data',
            ],
            'invalidate_types' => [
                'action' => 'invalidate',
                'item' => 'types',
                'section' => 'invalidate_data',
            ],
            'invalidate_dex' => [
                'action' => 'invalidate',
                'item' => 'dex',
                'section' => 'invalidate_data',
            ],
            'invalidate_albums' => [
                'action' => 'invalidate',
                'item' => 'albums',
                'section' => 'invalidate_data',
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

    /**
     * Opens the nav-pill tab for the given section (templates/Admin/_actions.html.twig) so its
     * items become visible/interactable — items outside the default-active `update_data` tab
     * start hidden (`display: none`) on a fresh page load.
     */
    private function activateTab(Client $client, string $section): void
    {
        $tab = $client->findElement(WebDriverBy::cssSelector("#tab-{$section}-tab"));
        $client->executeScript('arguments[0].scrollIntoView({block: "center", inline: "nearest"});', [$tab]);
        $tab->click();
    }
}
