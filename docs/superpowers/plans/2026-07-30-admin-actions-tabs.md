# Admin Actions Tabs Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Turn the five stacked sections of the Admin Actions page
(`Admin/_actions.html.twig`, route `app_admin_actions`) into Bootstrap 5
tabs, one section visible at a time, while preserving the existing
"jump to and highlight the triggered item after a POST" redirect behavior.

**Architecture:** Replace the five sequential `row px-4 py-5` blocks with a
Bootstrap `nav-pills` + `tab-content`/`tab-pane` structure (native
Bootstrap 5 JS component, already loaded globally — no new dependency).
Move `Admin/_pipeline_status.html.twig` inside the `trigger_pipeline` pane.
Add one small JS function to `public/js/admin.js` that, on page load,
reads `window.location.hash`, finds which pane contains that element, and
activates that pane's tab via `bootstrap.Tab` before the browser's native
anchor scroll would otherwise land on a hidden element.

**Tech Stack:** Symfony 8 / Twig, Bootstrap 5.3.8 (CSS + `bootstrap.bundle.min.js`,
already loaded in `templates/base.html.twig`), vanilla JS (`public/js/admin.js`),
PHPUnit `WebTestCase` (integration), Panther/Selenium (browser tests).

## Global Constraints

- Design doc: `docs/superpowers/specs/2026-07-30-admin-actions-tabs-design.md`.
- Scope is the Actions page only (`Admin/_actions.html.twig`,
  `templates/Admin/actions.html.twig`). The Reports page
  (`Admin/_reports.html.twig`) is untouched.
- No changes to `AdminActionController`, `Admin/_macros.html.twig`, or
  `public/css/admin.css` — tab labels reuse existing translation keys
  (`admin.actions.<section>.title`), item ids/classes from the `action`
  macro are unchanged, tabs use Bootstrap's default styling.
- No new JS dependency — `bootstrap.Tab` is already available globally via
  `bootstrap.bundle.min.js` (see `templates/base.html.twig:34` and its
  existing `new bootstrap.Tooltip(...)` usage for precedent).
- Per standing user preference: **do not create git commits** while
  executing this plan. Leave changes staged/unstaged for the user to
  review and commit themselves. Each task below ends with a verification
  step instead of a commit step.
- Test commands run inside the `php` container per this repo's `CLAUDE.md`,
  e.g. `docker compose exec php php vendor/bin/phpunit --filter <name> <path>`
  for integration tests, `make tests-browser-chrome` for browser tests.

---

## Task 1: Restructure `_actions.html.twig` into Bootstrap tabs

**Files:**
- Modify: `templates/Admin/_actions.html.twig`
- Test: `tests/src/Integration/Controller/Admin/AdminPageTest.php`

**Interfaces:**
- Consumes: `Admin/_macros.html.twig`'s `admin.action(...)` macro (unchanged
  signature), `Admin/_pipeline_status.html.twig` (unchanged, only its
  include location moves).
- Produces: pane ids `tab-pane-update_data`, `tab-pane-update_availabilities`,
  `tab-pane-calculate_data`, `tab-pane-invalidate_data`,
  `tab-pane-trigger_pipeline`; nav button ids `tab-update_data-tab`,
  `tab-update_availabilities-tab`, `tab-calculate_data-tab`,
  `tab-invalidate_data-tab`, `tab-trigger_pipeline-tab`, each with
  `data-bs-target="#tab-pane-<section>"`. Task 2 depends on these exact
  id patterns for its hash-lookup JS and its browser-test tab activation
  helper.

- [ ] **Step 1: Write the failing test**

  In `tests/src/Integration/Controller/Admin/AdminPageTest.php`, inside
  `testAdminHome()`, add these assertions right after the existing
  `assertCountFilter($crawler, 6, 'h2')` line (line 81):

  ```php
        $this->assertCountFilter($crawler, 5, '#admin-actions-tab > .nav-item > .nav-link');
        $this->assertCountFilter($crawler, 1, '#admin-actions-tab .nav-link.active');
        $this->assertCountFilter($crawler, 5, '.tab-content > .tab-pane');
        $this->assertCountFilter($crawler, 1, '.tab-content > .tab-pane.show.active');

        foreach ([
            'update_data',
            'update_availabilities',
            'calculate_data',
            'invalidate_data',
            'trigger_pipeline',
        ] as $section) {
            $this->assertCountFilter($crawler, 1, "#tab-{$section}-tab[data-bs-target=\"#tab-pane-{$section}\"]");
            $this->assertCountFilter($crawler, 1, "#tab-pane-{$section}");
        }

        $this->assertCountFilter($crawler, 1, '#tab-pane-update_data.show.active');
        $this->assertCountFilter($crawler, 1, '#tab-pane-update_data #update_labels');
        $this->assertCountFilter($crawler, 1, '#tab-pane-invalidate_data #invalidate_labels');
        $this->assertCountFilter($crawler, 1, '#tab-pane-trigger_pipeline .admin-pipeline-status');
  ```

- [ ] **Step 2: Run test to verify it fails**

  Run: `docker compose exec php php vendor/bin/phpunit --filter testAdminHome tests/src/Integration/Controller/Admin/AdminPageTest.php`
  Expected: FAIL — none of `#admin-actions-tab`, `.tab-pane`,
  `#tab-pane-update_data`, etc. exist yet in the current markup.

- [ ] **Step 3: Rewrite the template**

  Replace the entire contents of `templates/Admin/_actions.html.twig`
  with:

  ```twig
  {% set locale = app.request.locale %}

  {% import "Admin/_macros.html.twig" as admin %}

  <h2>{{ 'title.admin_actions'|trans }}</h2>

  <ul class="nav nav-pills mb-4" id="admin-actions-tab" role="tablist">
    <li class="nav-item" role="presentation">
      <button
        class="nav-link active"
        id="tab-update_data-tab"
        data-bs-toggle="tab"
        data-bs-target="#tab-pane-update_data"
        type="button"
        role="tab"
        aria-controls="tab-pane-update_data"
        aria-selected="true"
      >
        {{ 'admin.actions.update_data.title'|trans }}
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button
        class="nav-link"
        id="tab-update_availabilities-tab"
        data-bs-toggle="tab"
        data-bs-target="#tab-pane-update_availabilities"
        type="button"
        role="tab"
        aria-controls="tab-pane-update_availabilities"
        aria-selected="false"
      >
        {{ 'admin.actions.update_availabilities.title'|trans }}
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button
        class="nav-link"
        id="tab-calculate_data-tab"
        data-bs-toggle="tab"
        data-bs-target="#tab-pane-calculate_data"
        type="button"
        role="tab"
        aria-controls="tab-pane-calculate_data"
        aria-selected="false"
      >
        {{ 'admin.actions.calculate_data.title'|trans }}
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button
        class="nav-link"
        id="tab-invalidate_data-tab"
        data-bs-toggle="tab"
        data-bs-target="#tab-pane-invalidate_data"
        type="button"
        role="tab"
        aria-controls="tab-pane-invalidate_data"
        aria-selected="false"
      >
        {{ 'admin.actions.invalidate_data.title'|trans }}
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button
        class="nav-link"
        id="tab-trigger_pipeline-tab"
        data-bs-toggle="tab"
        data-bs-target="#tab-pane-trigger_pipeline"
        type="button"
        role="tab"
        aria-controls="tab-pane-trigger_pipeline"
        aria-selected="false"
      >
        {{ 'admin.actions.trigger_pipeline.title'|trans }}
      </button>
    </li>
  </ul>

  <div class="tab-content" id="admin-actions-tab-content">
    <div class="tab-pane fade show active" id="tab-pane-update_data" role="tabpanel" aria-labelledby="tab-update_data-tab" tabindex="0">
      <div class="row px-4 py-5">
        <h2 class="mb-3 pb-2 border-bottom">{{ 'admin.actions.update_data.title'|trans }}</h2>
        {% set updateDataItems = {
          'labels': 'tags-fill',
          'games_collections_and_dex': 'joystick',
          'pokemons': 'bug-fill',
          'regional_dex_numbers': 'globe-asia-australia',
        } %}
        {% for item, icon in updateDataItems %}
          {{ admin.action('update_data', 'update', item, icon, updatedItem, updatedAction, updatedState, actionLogsData) }}
        {% endfor %}
      </div>
    </div>

    <div class="tab-pane fade" id="tab-pane-update_availabilities" role="tabpanel" aria-labelledby="tab-update_availabilities-tab" tabindex="0">
      <div class="row px-4 py-5">
        <h2 class="mb-3 pb-2 border-bottom">{{ 'admin.actions.update_availabilities.title'|trans }}</h2>
        {% set updateAvailabilitiesItems = {
          'games_availabilities': 'box2',
          'games_shinies_availabilities': 'box2-fill',
          'collections_availabilities': 'collection-fill',
        } %}
        {% for item, icon in updateAvailabilitiesItems %}
          {{ admin.action('update_availabilities', 'update', item, icon, updatedItem, updatedAction, updatedState, actionLogsData) }}
        {% endfor %}
      </div>
    </div>

    <div class="tab-pane fade" id="tab-pane-calculate_data" role="tabpanel" aria-labelledby="tab-calculate_data-tab" tabindex="0">
      <div class="row px-4 py-5">
        <h2 class="mb-3 pb-2 border-bottom">{{ 'admin.actions.calculate_data.title'|trans }}</h2>
        {% set calculateDataItems = {
          'game_bundles_availabilities': 'box-seam',
          'game_bundles_shinies_availabilities': 'box-seam-fill',
          'dex_availabilities': 'list-check',
          'pokemon_availabilities': 'calculator',
        } %}
        {% for item, icon in calculateDataItems %}
          {{ admin.action('calculate_data', 'calculate', item, icon, updatedItem, updatedAction, updatedState, actionLogsData) }}
        {% endfor %}
      </div>
    </div>

    <div class="tab-pane fade" id="tab-pane-invalidate_data" role="tabpanel" aria-labelledby="tab-invalidate_data-tab" tabindex="0">
      <div class="row px-4 py-5">
        <h2 class="mb-3 pb-2 border-bottom">{{ 'admin.actions.invalidate_data.title'|trans }}</h2>
        {% set invalidateDataItems = {
          'labels': 'tags-fill',
          'catch_states': 'check2-square',
          'types': 'shapes',
          'dex': 'journals',
          'albums': 'journal-album',
        } %}
        {% for item, icon in invalidateDataItems %}
          {{ admin.action('invalidate_data', 'invalidate', item, icon, updatedItem, updatedAction, updatedState, actionLogsData) }}
        {% endfor %}
      </div>
    </div>

    <div class="tab-pane fade" id="tab-pane-trigger_pipeline" role="tabpanel" aria-labelledby="tab-trigger_pipeline-tab" tabindex="0">
      <div class="row px-4 py-5">
        <h2 class="mb-3 pb-2 border-bottom">{{ 'admin.actions.trigger_pipeline.title'|trans }}</h2>
        <p class="text-body-secondary small">{{ 'admin.actions.trigger_pipeline.help'|trans }}</p>
        {% set triggerPipelineItems = {
          'update_images': 'images',
        } %}
        {% for item, icon in triggerPipelineItems %}
          {{ admin.action('trigger_pipeline', 'trigger', item, icon, updatedItem, updatedAction, updatedState, actionLogsData) }}
        {% endfor %}
      </div>

      {% include 'Admin/_pipeline_status.html.twig' %}
    </div>
  </div>
  ```

- [ ] **Step 4: Run test to verify it passes**

  Run: `docker compose exec php php vendor/bin/phpunit --filter testAdminHome tests/src/Integration/Controller/Admin/AdminPageTest.php`
  Expected: PASS.

  Also run the full `AdminPageTest` and `PipelineStatusTest` files (neither
  needs code changes, but both read markup produced by this template and
  must stay green):

  Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Admin/AdminPageTest.php tests/src/Integration/Controller/Admin/PipelineStatusTest.php`
  Expected: PASS (`PipelineStatusTest` uses `Crawler::filter()`, which is
  DOM-based and ignores CSS `display: none`, so moving the pipeline-status
  include inside a hidden tab pane doesn't affect it).

**Note:** After this task, the browser tests `RedirectActionsTest` will
fail for every item outside the default-active `update_data` pane, because
those items are now hidden (`display: none`) until their tab is opened —
Task 2 fixes this. Do not run `make tests-browser` yet.

---

## Task 2: Deep-link tab activation + fix browser tests

**Files:**
- Modify: `public/js/admin.js`
- Modify: `templates/Admin/actions.html.twig`
- Modify: `tests/src/Browser/Admin/RedirectActionsTest.php`

**Interfaces:**
- Consumes: pane/button id patterns produced by Task 1
  (`tab-pane-<section>`, `tab-<section>-tab`, `data-bs-target`).
- Produces: `activateTabForHash()` global function in `admin.js`, called
  once on page load from `actions.html.twig`'s `foot_javascript` block.

- [ ] **Step 1: Write the failing test**

  Replace the entire contents of
  `tests/src/Browser/Admin/RedirectActionsTest.php` with:

  ```php
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
      public function testActionItems(string $action, string $item, string $section): void
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
  ```

- [ ] **Step 2: Run test to verify it fails**

  Run: `make tests-browser-chrome` filtered to this file (or run PHPUnit
  directly against the running Panther/Selenium stack):
  `docker compose exec php php vendor/bin/phpunit tests/src/Browser/Admin/RedirectActionsTest.php`
  Expected: FAIL on `assertSelectorWillBeVisible("#{$action}_{$item}")` for
  every case where `section !== 'update_data'` (7 of 16 cases: the
  `update_availabilities`, `calculate_data`, and `invalidate_data` items) —
  the redirect lands back on the default `update_data` pane and nothing
  yet re-activates the right tab from the URL hash.

- [ ] **Step 3: Add the deep-link activation function**

  Append to `public/js/admin.js`:

  ```js
  function activateTabForHash() {
    const hash = window.location.hash;
    if (!hash) {
      return;
    }
    const target = document.querySelector(hash);
    if (!target) {
      return;
    }
    const pane = target.closest('.tab-pane');
    if (!pane || pane.classList.contains('active')) {
      return;
    }
    const trigger = document.querySelector(`[data-bs-target="#${pane.id}"]`);
    if (!trigger) {
      return;
    }
    new bootstrap.Tab(trigger).show();
    target.scrollIntoView();
  }
  ```

- [ ] **Step 4: Wire the call into the Actions page**

  In `templates/Admin/actions.html.twig`, update the `foot_javascript`
  block (currently lines 39-48) to:

  ```twig
  {% block foot_javascript %}
    {{ parent() }}

    <script>
      (function () {
        watchActionLogToggles();
        watchForceConfirm();
        activateTabForHash();
      })();
    </script>
  {% endblock %}
  ```

- [ ] **Step 5: Run test to verify it passes**

  Run: `docker compose exec php php vendor/bin/phpunit tests/src/Browser/Admin/RedirectActionsTest.php`
  Expected: PASS for all 16 cases.

  Then run the other two admin browser tests that share this page to
  confirm no regression (`ToggleActionsTest` only touches
  `update_labels`, inside the default-active pane, so it needs no code
  changes but must still pass; `ForceActionTest` only touches
  `update_games_collections_and_dex`, also default-active):

  Run: `docker compose exec php php vendor/bin/phpunit tests/src/Browser/Admin/ToggleActionsTest.php tests/src/Browser/Admin/ForceActionTest.php`
  Expected: PASS, unchanged.

**Verification for this task:** no commit — leave the three modified files
(`public/js/admin.js`, `templates/Admin/actions.html.twig`,
`tests/src/Browser/Admin/RedirectActionsTest.php`) and Task 1's
`templates/Admin/_actions.html.twig` /
`tests/src/Integration/Controller/Admin/AdminPageTest.php` for the user to
review.

---

## Final check

- [ ] Run the full admin test surface one more time end to end:
  `docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Admin tests/src/Browser/Admin tests/src/Unit/Controller/AdminControllerTest.php`
  Expected: PASS.
- [ ] Manually load `http://localhost/fr/connect/f/c?t=admin` then
  `/fr/istration/actions` in a browser: confirm the five tabs render, only
  `update_data` shows by default, clicking a pill switches panes, and
  triggering an action in a non-default pane (e.g. an "Invalidation"
  button) lands back on that same pane with the item scrolled into view.
