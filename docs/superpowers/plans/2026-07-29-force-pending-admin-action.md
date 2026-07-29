# Force a Pending Admin Action Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let an admin force-trigger a pending admin action from the UI (with a confirmation dialog showing how long it's been pending), instead of the button being permanently disabled.

**Architecture:** Pure front-end change — no backend/API changes. The `admin-item-cta` button is never rendered `disabled`; when the underlying action is `pending`, it carries a `data-confirm-message` attribute (translated string with elapsed duration, computed server-side in Twig using the same `.diff(date('now'))` pattern the codebase already uses for the progress bar). A new vanilla-JS function intercepts the form's `submit` event and shows `window.confirm(...)` only for buttons carrying that attribute; canceling prevents the submit, confirming lets the existing POST flow run unchanged.

**Tech Stack:** Symfony 8 Twig macros, plain JS (no framework — matches existing `public/js/admin.js` style), Symfony Translation (ICU MessageFormat), PHPUnit `WebTestCase` (Moco-backed integration test), Panther browser test.

## Global Constraints

- No server-side check/lock is added anywhere (`AdminActionController` stays untouched) — confirmed the backend already allows duplicate/concurrent triggers, so this feature only replaces a client-side restriction, per the approved spec `docs/superpowers/specs/2026-07-29-force-pending-admin-action-design.md`.
- `declare(strict_types=1)` in any PHP file touched; test classes are `final`, `@internal`, and carry the appropriate PHPUnit attributes (`#[CoversClass(...)]` or `#[CoversNothing]` for browser tests, matching existing sibling tests).
- Follow existing code style exactly (no unrelated refactors of `progress` macro or `admin.js`'s existing functions).
- `make code-quality` (phpcsfixer, phpstan, psalm, phpmd, deptrac) and `make tests-integration` / `make tests-browser` must pass before considering a task done.

---

### Task 1: Twig — always-clickable button with `data-confirm-message`

**Files:**
- Modify: `templates/Admin/_macros.html.twig:84` (call site) and `templates/Admin/_macros.html.twig:111-142` (macro `actionButton`)
- Test: `tests/src/Integration/Controller/Admin/AdminPageTest.php:99-102` (existing `testAdminHome` method)

**Interfaces:**
- Produces: macro `_self.formatElapsed(timeDiff)` (Twig macro, takes a `DateInterval`-like object with `.days`, `.h`, `.i` properties, returns a formatted string like `"3j 12h"`, `"2h 5min"`, or `"1 min"`).
- Produces: `data-confirm-message` HTML attribute on `button.admin-item-cta`, present only when the action's `currentState == 'pending'`, containing the fully-translated confirmation string.
- Consumes: existing `admin.action.force_confirm` translation key (added in Task 2) via `|trans({'duration': duration})`.

- [ ] **Step 1: Write the failing integration test assertions**

Open `tests/src/Integration/Controller/Admin/AdminPageTest.php` and replace lines 99-102:

```php
        $this->assertCountFilter($crawler, 3, '.admin-item-cta.disabled');
        $this->assertCountFilter($crawler, 1, '#update_games_collections_and_dex .admin-item-cta.disabled');
        $this->assertCountFilter($crawler, 1, '#calculate_game_bundles_availabilities .admin-item-cta.disabled');
        $this->assertCountFilter($crawler, 1, '#calculate_dex_availabilities .admin-item-cta.disabled');
```

with:

```php
        $this->assertCountFilter($crawler, 0, '.admin-item-cta.disabled');

        $this->assertCountFilter($crawler, 3, '.admin-item-cta[data-confirm-message]');
        $this->assertCountFilter($crawler, 1, '#update_games_collections_and_dex .admin-item-cta[data-confirm-message]');
        $this->assertCountFilter($crawler, 1, '#calculate_game_bundles_availabilities .admin-item-cta[data-confirm-message]');
        $this->assertCountFilter($crawler, 1, '#calculate_dex_availabilities .admin-item-cta[data-confirm-message]');

        $confirmMessage = $crawler->filter('#update_games_collections_and_dex .admin-item-cta')->attr('data-confirm-message') ?? '';
        $this->assertStringContainsString('Une exécution est en cours depuis', $confirmMessage);
        $this->assertStringContainsString('j ', $confirmMessage);
        $this->assertStringContainsString('Voulez-vous quand même relancer cette action', $confirmMessage);
```

The fixture `tests/resources/moco/Back/responses/action-logs.json` has `update_games_collections_and_dex.current.created_at` = `2023-09-01T08:00:20+00:00` with `done_at: null` — always more than a day in the past relative to any real test run, so asserting `'j '` (the "days" unit in the formatted duration) in the message is deterministic without pinning an exact number.

- [ ] **Step 2: Run the test to verify it fails**

```bash
docker compose exec php php vendor/bin/phpunit --filter testAdminHome tests/src/Integration/Controller/Admin/AdminPageTest.php
```

Expected: FAIL — `.admin-item-cta.disabled` still exists (3, not 0) and `[data-confirm-message]` doesn't exist yet (0, not 3).

- [ ] **Step 3: Add the `formatElapsed` macro and update `actionButton`**

In `templates/Admin/_macros.html.twig`, change the call site at line 84 from:

```twig
        {{ _self.actionButton(action, actionPrefix, item, currentState, currentBgStyle) }}
```

to:

```twig
        {{ _self.actionButton(action, actionPrefix, item, currentState, currentBgStyle, currentActionLog) }}
```

Then replace the `actionButton` macro (lines 111-142) with:

```twig
{% macro actionButton (
    action,
    actionPrefix,
    item,
    currentState,
    bgStyle,
    currentActionLog
) %}
  {% set csrfId = 'admin_' ~ actionPrefix %}
  {% set confirmMessage = null %}
  {% if currentState == 'pending' %}
    {% set timeDiff = currentActionLog.createdAt.diff(date('now')) %}
    {% set confirmMessage = ('admin.action.force_confirm')|trans({'duration': _self.formatElapsed(timeDiff)}) %}
  {% endif %}
  <div class="text-end border-top border-{{ bgStyle }} pt-3">
    <form method="post" action="{{ path('app_adminaction_'~actionPrefix, {'name': item}) }}">
      <input type="hidden" name="_token" value="{{ csrf_token(csrfId) }}">
      <button
        type="submit"
        class="btn btn-outline-primary admin-item-cta"
        {% if confirmMessage is not null %}data-confirm-message="{{ confirmMessage }}"{% endif %}
      >
        {{ ('admin.actions.'~action~'.'~item~'.cta')|trans }}
      </button>

    {% if currentState == 'pending' %}
      {% set query = app.request.query.all %}
      {% set query = query|merge({
    'refresh': 'now'|date('U'),
    '_fragment': actionPrefix~'_'~item,
    }) %}
      <a href="{{ path(app.request.attributes.get('_route'), query) }}" class="btn btn-outline-info btn-sm admin-item-refresh">
        <i class="bi bi-arrow-clockwise"></i>
      </a>
    {% endif %}
    </form>
  </div>
{% endmacro %}

{% macro formatElapsed(timeDiff) %}
  {%- if timeDiff.days > 0 -%}
    {{ timeDiff.days }}j {{ timeDiff.h }}h
  {%- elseif timeDiff.h > 0 -%}
    {{ timeDiff.h }}h {{ timeDiff.i }}min
  {%- else -%}
    {{ timeDiff.i > 0 ? timeDiff.i : 1 }} min
  {%- endif -%}
{% endmacro %}
```

Note: `timeDiff.days` (total whole days elapsed) is the same property already used by the existing `progress` macro at line ~264 (`actionData.createdAt.diff(date('now'))`) — this is not a new pattern, just reused.

This step alone will still fail the test because the translation key doesn't exist yet (Task 2) — that's expected; do not skip to running tests yet.

- [ ] **Step 4: Commit**

```bash
git add templates/Admin/_macros.html.twig tests/src/Integration/Controller/Admin/AdminPageTest.php
git commit -m "feat(admin): always render action button clickable with data-confirm-message when pending"
```

---

### Task 2: Translations for the confirmation message

**Files:**
- Modify: `translations/messages+intl-icu.fr.yaml` (under the existing `admin: action:` block, currently lines 361-364)
- Modify: `translations/messages+intl-icu.en.yaml` (under the existing `admin: action:` block, currently lines 356-359)

**Interfaces:**
- Consumes: nothing new.
- Produces: translation key `admin.action.force_confirm` with an ICU `{duration}` placeholder, used by Task 1's Twig code (`'admin.action.force_confirm'|trans({'duration': ...})`).

- [ ] **Step 1: Add the French translation**

In `translations/messages+intl-icu.fr.yaml`, the `admin: action:` block currently reads:

```yaml
  action:
    created_at: "Démarré le"
    done_at: "Terminé le"
    execution_time: "Terminé en"
```

Change it to:

```yaml
  action:
    created_at: "Démarré le"
    done_at: "Terminé le"
    execution_time: "Terminé en"
    force_confirm: "Une exécution est en cours depuis {duration}. Voulez-vous quand même relancer cette action ?"
```

- [ ] **Step 2: Add the English translation**

In `translations/messages+intl-icu.en.yaml`, the `admin: action:` block currently reads:

```yaml
  action:
    created_at: "Start at"
    done_at: "End at"
    execution_time: "Execution time"
```

Change it to:

```yaml
  action:
    created_at: "Start at"
    done_at: "End at"
    execution_time: "Execution time"
    force_confirm: "An execution has been running for {duration}. Do you still want to trigger this action?"
```

- [ ] **Step 3: Run the integration test from Task 1 to verify it now passes**

```bash
docker compose exec php php vendor/bin/phpunit --filter testAdminHome tests/src/Integration/Controller/Admin/AdminPageTest.php
```

Expected: PASS.

- [ ] **Step 4: Run jsonlint/yaml lint for the translation files**

```bash
docker compose exec php php vendor/bin/console lint:yaml translations/messages+intl-icu.fr.yaml translations/messages+intl-icu.en.yaml
```

Expected: both files reported valid.

- [ ] **Step 5: Commit**

```bash
git add translations/messages+intl-icu.fr.yaml translations/messages+intl-icu.en.yaml
git commit -m "feat(admin): add force_confirm translation for pending action override"
```

---

### Task 3: JS confirmation handler

**Files:**
- Modify: `public/js/admin.js` (append new function, following the existing plain-function style of `watchActionLogToggles`)
- Modify: `templates/Admin/actions.html.twig:42-46` (inline `foot_javascript` block)
- Test: `tests/src/Browser/Admin/ForceActionTest.php` (new)

**Interfaces:**
- Consumes: `button.admin-item-cta[data-confirm-message]` markup produced by Task 1.
- Produces: global function `watchForceConfirm()` (no args, no return value), to be called once at page load exactly like `watchActionLogToggles()` already is.

- [ ] **Step 1: Write the failing browser test**

Create `tests/src/Browser/Admin/ForceActionTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Browser\Admin;

use App\Tests\Browser\AbstractBrowserTestCase;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Utils\GetUserToken;
use Facebook\WebDriver\WebDriverBy;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * @internal
 */
#[CoversNothing]
final class ForceActionTest extends AbstractBrowserTestCase
{
    use TestNavTrait;

    public function testDismissingConfirmationKeepsActionPending(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken('109903422692691644111', 'TestProvider');
        $user->addAdminRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/istration/actions');

        $client
            ->findElement(WebDriverBy::cssSelector('#update_games_collections_and_dex button.admin-item-cta'))
            ->click();

        $alert = $client->switchTo()->alert();
        $this->assertStringContainsString('Une exécution est en cours depuis', $alert->getText());
        $alert->dismiss();

        $crawler = $client->refreshCrawler();
        $this->assertSame('', $crawler->filter('#update_games_collections_and_dex')->attr('data-updated-state'));
    }

    public function testAcceptingConfirmationSubmitsTheForm(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken('109903422692691644222', 'TestProvider');
        $user->addAdminRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/istration/actions');

        $client
            ->findElement(WebDriverBy::cssSelector('#update_games_collections_and_dex button.admin-item-cta'))
            ->click();

        $alert = $client->switchTo()->alert();
        $alert->accept();

        // The click triggers a real page navigation (POST then 302 redirect back to the GET page).
        // Panther's own submit() helper has Firefox-specific waiting logic for exactly this reason
        // (vendor/symfony/panther/src/Client.php:227-246); since we bypass that helper (see note
        // below), wait explicitly for the post-redirect DOM state instead of assuming the click()
        // call already blocked until navigation finished.
        $client->wait()->until(static function () use ($client) {
            return '' !== $client->findElement(WebDriverBy::cssSelector('#update_games_collections_and_dex'))->getAttribute('data-updated-state');
        });

        $crawler = $client->refreshCrawler();
        $this->assertNotSame('', $crawler->filter('#update_games_collections_and_dex')->attr('data-updated-state'));
    }
}
```

Note: this uses `$client->findElement(...)->click()` (raw WebDriver) instead of Panther's `Client::click()`/`submit()` helpers, because those helpers call `createCrawler()` right after clicking — which would hit the open native `confirm()` dialog and throw `UnexpectedAlertOpenException` before the test gets a chance to inspect it. `$client->switchTo()->alert()`, `$client->refreshCrawler()`, and `$client->wait()` are all public methods on `Symfony\Component\Panther\Client` (confirmed at `vendor/symfony/panther/src/Client.php:646`, `:257`, and `:625`). The assertion on `data-updated-state` (set in `templates/Admin/_macros.html.twig:77` from the `updatedState` flash) is what actually distinguishes "no POST happened" (dismiss — attribute stays `''`, its value with no flash set) from "POST happened" (accept — `AdminActionController::execute()` always redirects back with an item/action/state flash, confirmed in `src/Controller/AdminActionController.php`, so the attribute becomes non-empty either way, success or failure).

- [ ] **Step 2: Run the browser test to verify it fails**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Browser/Admin/ForceActionTest.php
```

Expected: FAIL — no `data-confirm-message` handling exists yet, so clicking submits the form immediately (no JS alert appears), and `$client->switchTo()->alert()` throws a `NoSuchAlertException`.

- [ ] **Step 3: Implement `watchForceConfirm()` in `public/js/admin.js`**

Append at the end of the file:

```js
function watchForceConfirm() {
    document.querySelectorAll(".admin-item-cta[data-confirm-message]").forEach(function (button) {
        const form = button.closest('form');
        if (!form) {
            return;
        }
        form.addEventListener('submit', function (event) {
            if (!window.confirm(button.getAttribute('data-confirm-message'))) {
                event.preventDefault();
            }
        });
    });
}
```

- [ ] **Step 4: Wire it up in `templates/Admin/actions.html.twig`**

Replace the `foot_javascript` block (lines 39-47):

```twig
{% block foot_javascript %}
  {{ parent() }}

  <script>
    (function () {
      watchActionLogToggles();
    })();
  </script>
{% endblock %}
```

with:

```twig
{% block foot_javascript %}
  {{ parent() }}

  <script>
    (function () {
      watchActionLogToggles();
      watchForceConfirm();
    })();
  </script>
{% endblock %}
```

- [ ] **Step 5: Run the browser test to verify it passes**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Browser/Admin/ForceActionTest.php
```

Expected: PASS for both `testDismissingConfirmationKeepsActionPending` and `testAcceptingConfirmationSubmitsTheForm`.

- [ ] **Step 6: Run the full existing browser suite for the Admin folder to check for regressions**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Browser/Admin/
```

Expected: all tests (including the pre-existing `ToggleActionsTest` and `RedirectActionsTest`) still PASS.

- [ ] **Step 7: Commit**

```bash
git add public/js/admin.js templates/Admin/actions.html.twig tests/src/Browser/Admin/ForceActionTest.php
git commit -m "feat(admin): confirm before submitting a pending admin action"
```

---

### Task 4: Full regression pass

**Files:** none (verification only)

**Interfaces:** none

- [ ] **Step 1: Run the full integration suite**

```bash
docker compose exec php php vendor/bin/phpunit --group api-mocked-testing
```

Expected: PASS, no regressions in other Admin-page assertions (report tables, descriptions, etc. from `testAdminHome`).

- [ ] **Step 2: Run the full browser suite**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Browser
```

Expected: PASS.

- [ ] **Step 3: Run code-quality checks**

```bash
docker compose exec php php tools/phpcsfixer/vendor/bin/php-cs-fixer fix --dry-run --diff
docker compose exec php php tools/phpstan/vendor/bin/phpstan analyse --memory-limit=-1
docker compose exec php php tools/psalm/vendor/bin/psalm --show-info=false
```

Expected: all clean (no new baseline entries needed — no PHP class signatures changed, only Twig/JS/YAML).

- [ ] **Step 4: Manual smoke check**

Log in as admin via `http://localhost/fr/connect/f/c?t=admin`, go to `/fr/istration/actions`, find a pending action (or trigger one and reload before it completes), click its button, verify the confirm dialog text shows a plausible elapsed duration, cancel it (page stays put, no request sent), click again and accept (request is sent, page reloads/flashes as a normal trigger would).

- [ ] **Step 5: Commit** (only if any fixups were needed in prior steps; otherwise skip — nothing to commit)
