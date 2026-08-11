# Dex-Link Picker UX Fixes Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Fix the Album offcanvas "Liens" dex-picker so its filters actually work, match the Trainer page's filter set exactly (plus a new "already linked" filter), block self-links both client- and server-side, match the Trainer page's "voir le dex" button, and keep the direction selector visible while scrolling.

**Architecture:** Extract the Trainer page's 5-filter `<select>` markup into a shared Twig macro reused by both the Trainer page and the picker (so they structurally can't drift apart); rewrite the picker's client-side filter JS around that shared markup plus one picker-only "linked" filter; add a defense-in-depth self-link guard at both the JS layer (pokenini-web) and the input-validation layer (pokenini-back); align the picker's view-button markup with the Trainer page's; make the direction-selector block `position: sticky`.

**Tech Stack:** Symfony 8 / PHP 8.5 (Twig, plain JS, PHPUnit + Panther browser tests) in `pokenini-web`; Symfony 8 / PHP 8.5 (PHPUnit unit tests) in `pokenini-back`.

## Global Constraints

- Docker-only toolchain: no PHP/Composer/PHPUnit on the host. Every command runs via `docker compose exec php ...` inside each repo's own container (`cd` into the repo first — there is no top-level Makefile spanning the three repos).
- `declare(strict_types=1);` in every PHP file touched.
- `final` classes for Controller / Test classes.
- Test classes carry `/** @internal */` and `#[CoversClass(...)]` (or `#[CoversNothing]` for browser tests, matching this codebase's existing convention for Panther tests).
- Integration/browser tests use Moco HTTP mocks — never mock the HTTP client directly.
- `make quality` and `make measures` (100% coverage, 100% MSI) must stay green in each repo touched; PHP changes need accompanying unit test coverage.
- Each repo has its own git history — commit inside each repo separately, never from the workspace root.

---

### Task 1: pokenini-back — reject a link whose target equals its own dex

**Files:**
- Modify: `/home/renaud/projects/pokenini-back/src/Controller/Album/TrainerDexLinkController.php:39-72` (`create()` method)
- Test: `/home/renaud/projects/pokenini-back/tests/src/Unit/Controller/Album/TrainerDexLinkControllerTest.php`

**Interfaces:**
- Consumes: nothing from other tasks (fully independent).
- Produces: nothing consumed by later tasks — this is a standalone server-side guard.

- [ ] **Step 1: Write the failing test**

Add this test method to `TrainerDexLinkControllerTest.php`, right after `createRejectsNonBooleanBidirectional` (after line 175):

```php
    #[Test]
    public function createRejectsSelfLink(): void
    {
        $findDexBySlugService = $this->createStub(FindDexBySlugService::class);
        $findDexBySlugService->method('find')
            ->willReturn(['slug' => 'douze', 'flags' => ['is_premium' => false]])
        ;

        $trainerDexLinkService = $this->createMock(TrainerDexLinkService::class);
        $trainerDexLinkService->expects($this->never())->method('create');

        $controller = $this->controller($findDexBySlugService, $trainerDexLinkService, true);

        $response = $controller->create('douze', Request::create('test.local', 'POST', content: '{"targetDexSlug":"douze"}'));

        $this->assertSame(400, $response->getStatusCode());
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd /home/renaud/projects/pokenini-back && docker compose exec php php vendor/bin/phpunit --filter createRejectsSelfLink tests/src/Unit/Controller/Album/TrainerDexLinkControllerTest.php`

Expected: FAIL — `create()` currently calls `$trainerDexLinkService->create('douze', 'douze', false)` and returns 201, so the mock's `expects($this->never())` assertion fails.

- [ ] **Step 3: Implement the guard**

In `src/Controller/Album/TrainerDexLinkController.php`, add the check right after the existing `targetDexSlug` shape check (after the `if (!isset($content['targetDexSlug']) || !\is_string($content['targetDexSlug'])) { ... }` block, before the `bidirectional` check):

```php
        if ($content['targetDexSlug'] === $dexSlug) {
            return new JsonResponse([], Response::HTTP_BAD_REQUEST);
        }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `cd /home/renaud/projects/pokenini-back && docker compose exec php php vendor/bin/phpunit --filter createRejectsSelfLink tests/src/Unit/Controller/Album/TrainerDexLinkControllerTest.php`

Expected: PASS

- [ ] **Step 5: Run the full controller test file and quality gate**

Run: `cd /home/renaud/projects/pokenini-back && docker compose exec php php vendor/bin/phpunit tests/src/Unit/Controller/Album/TrainerDexLinkControllerTest.php`

Expected: all tests PASS (no regression in the other `create*`/`list*`/`delete` tests).

Then run `docker compose exec php php tools/phpstan/vendor/bin/phpstan` and `docker compose exec php php tools/psalm/vendor/bin/psalm --show-info=false` (or `make code-quality` if time allows) to confirm the new code passes static analysis — the added block introduces no new types, so this should be a no-op check.

- [ ] **Step 6: Commit**

```bash
cd /home/renaud/projects/pokenini-back
git add src/Controller/Album/TrainerDexLinkController.php tests/src/Unit/Controller/Album/TrainerDexLinkControllerTest.php
git commit -m "Reject dex-link creation when target equals source dex"
```

---

### Task 2: pokenini-web — extract the Trainer filter markup into a shared macro

**Files:**
- Create: `templates/common/Filter/_dex_attribute_select.html.twig`
- Modify: `templates/Trainer/Section/_dex_filters.html.twig`
- Test (regression only, no new test): `tests/src/Integration/Controller/Trainer/TrainerPageFiltersTest.php`

**Interfaces:**
- Produces: macro `attributeSelect(item, idPrefix, colClass, name, selectedValue)` in `common/Filter/_dex_attribute_select.html.twig`, importable via `{% import 'common/Filter/_dex_attribute_select.html.twig' as filters %}` then `{{ filters.attributeSelect(...) }}`. Parameters:
  - `item` (string): one of `privacy`, `homepaged`, `released`, `shiny`, `premium` — selects the icon/label/role-gate.
  - `idPrefix` (string): rendered element id is `{{ idPrefix }}-{{ item }}`.
  - `colClass` (string): CSS classes on the wrapping `<div>` (caller controls layout/grid sizing).
  - `name` (string|null): if non-null, rendered as the `<select>`'s `name` attribute (for server-side form submission); if `null`, no `name` attribute is rendered.
  - `selectedValue` (bool|null): pre-selects the "on" (`true`), "off" (`false`), or "all" (`null`) option.
  - Renders nothing (empty string) if `is_granted()` denies the item's required role — this is how role-gating is preserved.
- Task 3 consumes this macro directly.

- [ ] **Step 1: Run the existing filter tests as a baseline (must be green before refactoring)**

Run: `cd /home/renaud/projects/pokenini-web && docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Trainer/TrainerPageFiltersTest.php`

Expected: all 12 tests PASS. This test file is the regression safety net for this task — it asserts exact `id`, `name`-driven pre-selection, and role visibility of `select#filter-privacy`, `select#filter-homepaged`, `select#filter-released`, `select#filter-shiny`, `select#filter-premium`. No new test is written for this task; the refactor must keep this file green with zero changes to it.

- [ ] **Step 2: Create the macro file**

Create `templates/common/Filter/_dex_attribute_select.html.twig`:

```twig
{% macro attributeSelect(item, idPrefix, colClass, name, selectedValue) %}
  {% set filtersIcons = {
    'privacy': 'incognito',
    'homepaged': 'house',
    'released': 'lock',
    'shiny': 'stars',
    'premium': 'patch-plus',
  } %}
  {% set filtersRole = {
    'privacy': 'ROLE_TRAINER',
    'homepaged': 'ROLE_TRAINER',
    'released': 'ROLE_ADMIN',
    'shiny': 'ROLE_TRAINER',
    'premium': 'ROLE_COLLECTOR',
  } %}
  {% if is_granted(filtersRole[item]) %}
    <div class="{{ colClass }}">
      <div class="form-floating">
        <select class="form-select" {% if name is not null %}name="{{ name }}"{% endif %} id="{{ idPrefix }}-{{ item }}">
          <option value="" {{ selectedValue is null ? 'selected' : ''}}>
            {{ ('trainer.filters.attributes.'~item~'.all')|trans }}
          </option>
          <option value="1" {{ true == selectedValue ? 'selected' : ''}}>
            {{ ('trainer.filters.attributes.'~item~'.on')|trans }}
          </option>
          <option value="0" {{ selectedValue is not null and false == selectedValue ? 'selected' : ''}}>
            {{ ('trainer.filters.attributes.'~item~'.off')|trans }}
          </option>
        </select>
        <label for="{{ idPrefix }}-{{ item }}">
          <i class="bi bi-{{ filtersIcons[item] }}"></i>&nbsp;{{ ('trainer.filters.attributes.'~item~'.label')|trans }}
        </label>
      </div>
    </div>
  {% endif %}
{% endmacro %}
```

- [ ] **Step 3: Rewrite `_dex_filters.html.twig` to use the macro**

Replace the full contents of `templates/Trainer/Section/_dex_filters.html.twig` with:

```twig
{% import 'common/Filter/_dex_attribute_select.html.twig' as attributeSelectMacro %}
<form id="dexFilters" class="row">
  {% set filtersItems = [
    'privacy',
    'homepaged',
    'released',
    'shiny',
    'premium',
  ] %}
  {% set filtersName = {
    'privacy': 'p',
    'homepaged': 'h',
    'released': 'r',
    'shiny': 's',
    'premium': 'm',
  } %}
  {% for item in filtersItems %}
    {{ attributeSelectMacro.attributeSelect(item, 'filter', 'col-sm-12 col-md-6 col-lg-4 mb-3', filtersName[item], attribute(filters, item).value) }}
  {% endfor %}
</form>
```

The import alias is `attributeSelectMacro` (not `filters`) precisely because `filters` is already the name of the controller-supplied Twig variable holding the current filter values (`attribute(filters, item).value` reads from it, unchanged from the original file) — using `filters` as the import alias too would shadow that variable. This is a drop-in replacement with no controller/DTO changes needed.

- [ ] **Step 4: Run the baseline test again to confirm no regression**

Run: `cd /home/renaud/projects/pokenini-web && docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Trainer/TrainerPageFiltersTest.php`

Expected: all 12 tests still PASS, byte-for-byte same rendered `id`/`name`/`selected` attributes as before (this test file was not modified, so it's asserting on identical output).

- [ ] **Step 5: Commit**

```bash
cd /home/renaud/projects/pokenini-web
git add templates/common/Filter/_dex_attribute_select.html.twig templates/Trainer/Section/_dex_filters.html.twig
git commit -m "Extract Trainer page's attribute-filter select into a reusable macro"
```

---

### Task 3: pokenini-web — align picker filters with the Trainer page, add "already linked" filter, drop search, fix broken wiring

**Files:**
- Modify: `templates/Album/_offcanvas.html.twig:136-169` (filters block + picker card data attributes)
- Modify: `public/js/album-links.js` (`watchDexPickerFilters()`, `applyDexPickerFilters()`)
- Modify: `translations/messages+intl-icu.fr.yaml:167-185`
- Modify: `translations/messages+intl-icu.en.yaml:161-179`
- Test: Create `tests/src/Browser/Album/OffcanvasLinksPickerFilterTest.php`

**Interfaces:**
- Consumes: `attributeSelectMacro.attributeSelect(...)` from Task 2 (`templates/common/Filter/_dex_attribute_select.html.twig`).
- Produces: picker card DOM shape consumed by Task 4/5 (`.dex-pick-card` with `data-filter-privacy`, `data-filter-homepaged`, `data-filter-released`, `data-filter-shiny`, `data-filter-premium` attributes, `.linked` class toggled by `renderPickerGrid()`, `#dex-picker-filter-linked` select).

- [ ] **Step 1: Write the failing browser test**

Create `tests/src/Browser/Album/OffcanvasLinksPickerFilterTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Browser\Album;

use App\Tests\Browser\AbstractBrowserTestCase;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 */
#[CoversNothing]
#[Group('api-mocked-testing')]
final class OffcanvasLinksPickerFilterTest extends AbstractBrowserTestCase
{
    use TestNavTrait;

    #[Test]
    public function searchBoxIsRemoved(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken('109903422692691643666', 'TestProvider');
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $crawler = $client->request('GET', '/fr/album/demolite');

        $this->assertCount(0, $crawler->filter('#dex-picker-search'));
    }

    #[Test]
    public function privacyFilterHidesNonMatchingCards(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken('109903422692691643666', 'TestProvider');
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/album/demolite');

        $client->executeScript("document.querySelector('.open-offcanvas').click()");
        $this->assertSelectorWillBeVisible('#offcanvas');
        $client->waitFor('#offcanvas.show:not(.showing)');
        $client->waitFor('.dex-pick-card.linked');

        $this->assertSelectorWillBeVisible('.dex-pick-card[data-dex-slug="redgreenblueyellow"]');
        $this->assertSelectorWillBeVisible('.dex-pick-card[data-dex-slug="swordshield"]');

        $client->executeScript("
            var el = document.getElementById('dex-picker-filter-privacy');
            el.value = '1';
            el.dispatchEvent(new Event('change'));
        ");

        $this->assertSelectorWillBeVisible('.dex-pick-card[data-dex-slug="redgreenblueyellow"]');
        $this->assertSelectorWillNotBeVisible('.dex-pick-card[data-dex-slug="swordshield"]');
    }

    #[Test]
    public function releasedFilterIsHiddenForNonAdmin(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken('109903422692691643666', 'TestProvider');
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/album/demolite');

        $client->executeScript("document.querySelector('.open-offcanvas').click()");
        $this->assertSelectorWillBeVisible('#offcanvas');
        $client->waitFor('#offcanvas.show:not(.showing)');

        $this->assertSelectorIsNotVisible('#dex-picker-filter-released');
    }

    #[Test]
    public function linkedFilterShowsOnlyAlreadyLinkedDexes(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken('109903422692691643666', 'TestProvider');
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/album/demolite');

        $client->executeScript("document.querySelector('.open-offcanvas').click()");
        $this->assertSelectorWillBeVisible('#offcanvas');
        $client->waitFor('#offcanvas.show:not(.showing)');
        $client->waitFor('.dex-pick-card.linked');

        $client->executeScript("
            var el = document.getElementById('dex-picker-filter-linked');
            el.value = '1';
            el.dispatchEvent(new Event('change'));
        ");

        $this->assertSelectorWillBeVisible('.dex-pick-card[data-dex-slug="goldsilvercrystal"]');
        $this->assertSelectorWillBeVisible('.dex-pick-card[data-dex-slug="rubysapphireemerald"]');
        $this->assertSelectorWillNotBeVisible('.dex-pick-card[data-dex-slug="redgreenblueyellow"]');
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `cd /home/renaud/projects/pokenini-web && docker compose exec php php vendor/bin/phpunit tests/src/Browser/Album/OffcanvasLinksPickerFilterTest.php`

Expected: FAIL — `searchBoxIsRemoved` fails because `#dex-picker-search` still exists; `privacyFilterHidesNonMatchingCards` and `linkedFilterShowsOnlyAlreadyLinkedDexes` fail because `#dex-picker-filter-privacy`/`#dex-picker-filter-linked` don't exist yet (only `shiny`/`premium`/`custom` selects exist today); `releasedFilterIsHiddenForNonAdmin` fails because that select doesn't exist at all yet.

- [ ] **Step 3: Update translations — FR**

In `translations/messages+intl-icu.fr.yaml`, replace lines 167-185 (the `filters:` block under `links:`):

```yaml
      filters:
        attributes:
          shiny:
            label: Chromatique
            all: Tous
            "on": Chromatiques
            "off": Normaux
          premium:
            label: Premium
            all: Tous
            "on": Premiums
            "off": Gratuits
          linked:
            label: "Déjà lié"
            all: Tous
            "on": "Déjà liés"
            "off": "Non liés"
```

(This removes the `search` key and the `custom` attribute, and adds `linked`.)

Leave line 157 (`view: Voir ce dex`) untouched in this task — it's still referenced by the picker's view link until Task 5 replaces that markup and removes this key.

- [ ] **Step 4: Update translations — EN**

In `translations/messages+intl-icu.en.yaml`, replace lines 161-179 (the equivalent `filters:` block):

```yaml
      filters:
        attributes:
          shiny:
            label: Shiny
            all: All
            "on": Shiny
            "off": Regular
          premium:
            label: Premium
            all: All
            "on": Premium
            "off": Free
          linked:
            label: Already linked
            all: All
            "on": Linked
            "off": Not linked
```

- [ ] **Step 5: Update the offcanvas template's filters block**

In `templates/Album/_offcanvas.html.twig`, replace lines 136-154 (the `<div class="dex-picker-filters row g-2 mb-2">...</div>` block containing the search input and the `shiny`/`premium`/`custom` loop) with:

```twig
      <div class="dex-picker-filters row g-2 mb-2">
        {% import 'common/Filter/_dex_attribute_select.html.twig' as attributeSelectMacro %}
        {% for item in ['privacy', 'homepaged', 'released', 'shiny', 'premium'] %}
          {{ attributeSelectMacro.attributeSelect(item, 'dex-picker-filter', 'col-6', null, null) }}
        {% endfor %}
        <div class="col-6">
          <div class="form-floating">
            <select class="form-select" id="dex-picker-filter-linked">
              <option value="">{{ 'album.offcanvas.links.filters.attributes.linked.all'|trans }}</option>
              <option value="1">{{ 'album.offcanvas.links.filters.attributes.linked.on'|trans }}</option>
              <option value="0">{{ 'album.offcanvas.links.filters.attributes.linked.off'|trans }}</option>
            </select>
            <label for="dex-picker-filter-linked">
              <i class="bi bi-link-45deg"></i>&nbsp;{{ 'album.offcanvas.links.filters.attributes.linked.label'|trans }}
            </label>
          </div>
        </div>
      </div>
```

- [ ] **Step 6: Update the picker card data attributes**

In the same file, in the `#dex-picker-grid` loop, replace the card's opening tag (originally lines 161-169):

```twig
          <div
            class="dex-pick-card{{ isCurrent ? ' dex-pick-card-current' : '' }}"
            data-dex-slug="{{ item.settings.slug }}"
            data-filter-privacy="{{ item.flags.isPrivate ? '1' : '0' }}"
            data-filter-homepaged="{{ item.flags.isOnHome ? '1' : '0' }}"
            data-filter-released="{{ item.flags.isReleased ? '1' : '0' }}"
            data-filter-shiny="{{ item.flags.isShiny ? '1' : '0' }}"
            data-filter-premium="{{ item.flags.isPremium ? '1' : '0' }}"
            {% if not isCurrent %}role="button" tabindex="0"{% endif %}
          >
```

(This drops `data-name` and `data-is-custom`, and renames `data-is-shiny`/`data-is-premium` to `data-filter-shiny`/`data-filter-premium` for a consistent naming scheme across all 5 shared filters.)

- [ ] **Step 7: Rewrite the JS filter wiring**

In `public/js/album-links.js`, replace `watchDexPickerFilters()` (lines 61-73):

```js
function watchDexPickerFilters() {
  const filters = document.querySelectorAll('[id^="dex-picker-filter-"]');

  filters.forEach(function (filter) {
    filter.addEventListener("change", applyDexPickerFilters);
  });
}
```

Replace `applyDexPickerFilters()` (lines 75-100):

```js
function applyDexPickerFilters() {
  const attributeFilters = [
    { filterId: "dex-picker-filter-privacy", dataKey: "filterPrivacy" },
    { filterId: "dex-picker-filter-homepaged", dataKey: "filterHomepaged" },
    { filterId: "dex-picker-filter-released", dataKey: "filterReleased" },
    { filterId: "dex-picker-filter-shiny", dataKey: "filterShiny" },
    { filterId: "dex-picker-filter-premium", dataKey: "filterPremium" },
  ];
  const linkedFilter = document.getElementById("dex-picker-filter-linked");

  document.querySelectorAll(".dex-pick-card").forEach(function (card) {
    let visible = true;

    attributeFilters.forEach(function (entry) {
      const select = document.getElementById(entry.filterId);
      if (visible && select && select.value && card.dataset[entry.dataKey] !== select.value) {
        visible = false;
      }
    });

    if (visible && linkedFilter && linkedFilter.value) {
      const isLinked = card.classList.contains("linked") ? "1" : "0";
      if (isLinked !== linkedFilter.value) {
        visible = false;
      }
    }

    card.classList.toggle("dex-pick-hidden", !visible);
  });
}
```

Note `renderPickerGrid()` already calls `applyDexPickerFilters()` at its end (existing line 225) — this re-applies the current filter state (including the new `linked` filter) every time link data is (re)loaded, so no change needed there.

- [ ] **Step 8: Run the test to verify it passes**

Run: `cd /home/renaud/projects/pokenini-web && docker compose exec php php vendor/bin/phpunit tests/src/Browser/Album/OffcanvasLinksPickerFilterTest.php`

Expected: all 4 tests PASS.

- [ ] **Step 9: Run the existing offcanvas/links regression tests**

Run: `cd /home/renaud/projects/pokenini-web && docker compose exec php php vendor/bin/phpunit tests/src/Browser/Album/OffcanvasTest.php tests/src/Browser/Album/PrivacyHomeToggleTest.php`

Expected: all PASS (unaffected by this task's changes).

- [ ] **Step 10: Commit**

```bash
cd /home/renaud/projects/pokenini-web
git add templates/Album/_offcanvas.html.twig public/js/album-links.js translations/messages+intl-icu.fr.yaml translations/messages+intl-icu.en.yaml tests/src/Browser/Album/OffcanvasLinksPickerFilterTest.php
git commit -m "Rework dex-link picker filters: align with Trainer page, add 'already linked' filter, drop broken search"
```

---

### Task 4: pokenini-web — defensive self-link guard in the picker JS

**Files:**
- Modify: `public/js/album-links.js` (`selectCard()`, `createLink()`)
- Test: Create `tests/src/Browser/Album/OffcanvasLinksSelfLinkGuardTest.php`

**Interfaces:**
- Consumes: `.dex-pick-card-current` class and `#dex-picker-grid` from Task 3's (unchanged) template output; global `createLink(dexSlug, selectedTargetDexSlug)` function.
- Produces: nothing consumed by later tasks.

- [ ] **Step 1: Write the failing browser test**

Create `tests/src/Browser/Album/OffcanvasLinksSelfLinkGuardTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Browser\Album;

use App\Tests\Browser\AbstractBrowserTestCase;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 */
#[CoversNothing]
#[Group('api-mocked-testing')]
final class OffcanvasLinksSelfLinkGuardTest extends AbstractBrowserTestCase
{
    use TestNavTrait;

    #[Test]
    public function currentDexCardIsNeverSelectable(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken('109903422692691643666', 'TestProvider');
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/album/demolite');

        $client->executeScript("document.querySelector('.open-offcanvas').click()");
        $this->assertSelectorWillBeVisible('#offcanvas');
        $client->waitFor('#offcanvas.show:not(.showing)');
        $client->waitFor('.dex-pick-card.linked');

        $client->executeScript("document.querySelector('.dex-pick-card-current').click()");

        $disabled = $client->getCrawler()->filter('#create-link')->attr('disabled');
        $this->assertNotNull($disabled);
        $this->assertCount(0, $client->getCrawler()->filter('.dex-pick-card.selected'));
    }

    #[Test]
    public function createLinkGuardsAgainstSelfLinkEvenIfCalledDirectly(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken('109903422692691643666', 'TestProvider');
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/album/demolite');

        $client->executeScript("document.querySelector('.open-offcanvas').click()");
        $this->assertSelectorWillBeVisible('#offcanvas');
        $client->waitFor('#offcanvas.show:not(.showing)');

        $client->executeScript("createLink('demolite', 'demolite');");

        $this->assertSelectorIsNotVisible('#linksToastSuccess');
        $this->assertSelectorIsNotVisible('#linksToastError');
    }
}
```

- [ ] **Step 2: Run the test to verify the second test method fails**

Run: `cd /home/renaud/projects/pokenini-web && docker compose exec php php vendor/bin/phpunit tests/src/Browser/Album/OffcanvasLinksSelfLinkGuardTest.php`

Expected: `currentDexCardIsNeverSelectable` PASSes already (existing guards already block the click path — this test locks in current-and-required behaviour). `createLinkGuardsAgainstSelfLinkEvenIfCalledDirectly` FAILs: today, `createLink('demolite', 'demolite')` proceeds to `fetch()` a real POST request (intercepted by Moco, which returns 201 for any body per the investigation), so `#linksToastSuccess` becomes visible — the assertion `assertSelectorIsNotVisible('#linksToastSuccess')` fails.

- [ ] **Step 3: Add the defensive guards**

In `public/js/album-links.js`, in `selectCard(card)` (lines 17-28), add the guard right after the existing `linked` check:

```js
  function selectCard(card) {
    if (card.classList.contains("linked")) {
      return;
    }
    if (card.classList.contains("dex-pick-card-current")) {
      return;
    }

    document.querySelectorAll(".dex-pick-card").forEach(function (c) {
      c.classList.remove("selected");
    });
    card.classList.add("selected");
    selectedTargetDexSlug = card.dataset.dexSlug;
    document.getElementById("create-link").disabled = false;
  }
```

In `createLink(dexSlug, selectedTargetDexSlug)` (line 102), add the guard at the top:

```js
function createLink(dexSlug, selectedTargetDexSlug) {
  if (!selectedTargetDexSlug || selectedTargetDexSlug === dexSlug) {
    return;
  }
  ...
```

(This replaces the existing `if (!selectedTargetDexSlug) { return; }` line with the combined condition — keep the rest of the function body unchanged.)

- [ ] **Step 4: Run the test to verify it passes**

Run: `cd /home/renaud/projects/pokenini-web && docker compose exec php php vendor/bin/phpunit tests/src/Browser/Album/OffcanvasLinksSelfLinkGuardTest.php`

Expected: both tests PASS.

- [ ] **Step 5: Commit**

```bash
cd /home/renaud/projects/pokenini-web
git add public/js/album-links.js tests/src/Browser/Album/OffcanvasLinksSelfLinkGuardTest.php
git commit -m "Add defensive self-link guards to the dex-link picker JS"
```

---

### Task 5: pokenini-web — match the picker's "voir le dex" button to the Trainer page's

**Files:**
- Modify: `templates/Album/_offcanvas.html.twig:170-174`
- Modify: `public/css/album.css` (remove `.dex-pick-view`'s absolute/hover-reveal rules)
- Modify: `translations/messages+intl-icu.fr.yaml` (remove now-unused `view: Voir ce dex`)
- Modify: `translations/messages+intl-icu.en.yaml` (remove now-unused `view: View this dex`)
- Test: Create `tests/src/Browser/Album/OffcanvasLinksViewButtonTest.php`

**Interfaces:**
- Consumes: nothing new from earlier tasks.
- Produces: nothing consumed by later tasks.

- [ ] **Step 1: Write the failing browser test**

Create `tests/src/Browser/Album/OffcanvasLinksViewButtonTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Browser\Album;

use App\Tests\Browser\AbstractBrowserTestCase;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 */
#[CoversNothing]
#[Group('api-mocked-testing')]
final class OffcanvasLinksViewButtonTest extends AbstractBrowserTestCase
{
    use TestNavTrait;

    #[Test]
    public function viewButtonMatchesTrainerPageStyleAndIsAlwaysVisible(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken('109903422692691643666', 'TestProvider');
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $crawler = $client->request('GET', '/fr/album/demolite');

        $client->executeScript("document.querySelector('.open-offcanvas').click()");
        $this->assertSelectorWillBeVisible('#offcanvas');
        $client->waitFor('#offcanvas.show:not(.showing)');
        $client->waitFor('.dex-pick-card.linked');

        $this->assertSelectorWillBeVisible('.dex-pick-card[data-dex-slug="redgreenblueyellow"] a.btn.btn-light.btn-sm');
        $this->assertSelectorWillBeVisible('.dex-pick-card[data-dex-slug="redgreenblueyellow"] a.btn.btn-light.btn-sm i.bi-eye-fill');

        $link = $crawler->filter('.dex-pick-card[data-dex-slug="redgreenblueyellow"] a.btn.btn-light.btn-sm');
        $this->assertStringContainsString('Voir', trim($link->text()));
        $this->assertStringContainsString('/fr/album/redgreenblueyellow', $link->attr('href') ?? '');
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `cd /home/renaud/projects/pokenini-web && docker compose exec php php vendor/bin/phpunit tests/src/Browser/Album/OffcanvasLinksViewButtonTest.php`

Expected: FAIL — today's link has class `dex-pick-view` only (no `btn btn-light btn-sm`) and icon `bi-eye` (not `bi-eye-fill`), and has no visible text (icon-only).

- [ ] **Step 3: Update the template**

In `templates/Album/_offcanvas.html.twig`, replace lines 170-174:

```twig
            {% if not isCurrent %}
              <a class="dex-pick-view btn btn-light btn-sm d-block mb-1" href="{{ path('app_albumindex_index', { 'dexSlug': item.settings.slug }) }}" title="{{ 'trainer.dex.see'|trans }}">
                <i class="bi bi-eye-fill"></i>
                {{ 'trainer.dex.see'|trans }}
              </a>
            {% endif %}
```

(The `dex-pick-view` class is kept alongside the Trainer-style `btn btn-light btn-sm` classes because `public/js/album-links.js`'s `event.target.closest(".dex-pick-view")` checks in the card's click/keydown handlers still rely on it to avoid triggering `selectCard()` when this link is clicked — no JS change needed here.)

- [ ] **Step 4: Remove the now-obsolete hover-reveal CSS**

In `public/css/album.css`, remove the comment + rule block for `.dex-pick-card .dex-pick-view` (the `position: absolute` block) and its paired hover/focus rule:

```css
/* Small "view this dex" shortcut, symmetric to the unlink control, always
    present except on the current dex's own (disabled) card. */
.dex-pick-card .dex-pick-view {
    position: absolute;
    top: .2rem;
    right: .2rem;
    width: 1.15rem;
    height: 1.15rem;
    border-radius: 50%;
    border: none;
    background: var(--bs-secondary-bg);
    color: var(--bs-body-color);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .55rem;
    opacity: 0;
    transform: scale(.85);
    transition: opacity .12s ease, transform .12s ease;
    box-shadow: 0 0 0 2px var(--bs-body-bg);
}
.dex-pick-card:hover .dex-pick-view,
.dex-pick-card:focus-within .dex-pick-view {
    opacity: 1;
    transform: scale(1);
}
```

Delete both rules entirely — the button is now laid out in normal document flow via Bootstrap's `.btn`/`.d-block` utility classes, no custom positioning needed. Leave the neighbouring `.unlink-btn` rules untouched (still absolutely positioned/hover-revealed, unaffected by this task).

- [ ] **Step 5: Remove the now-unused `view` translation key**

In `translations/messages+intl-icu.fr.yaml`, delete line 157 (`      view: Voir ce dex`).

In `translations/messages+intl-icu.en.yaml`, delete line 151 (`      view: View this dex`).

- [ ] **Step 6: Run the test to verify it passes**

Run: `cd /home/renaud/projects/pokenini-web && docker compose exec php php vendor/bin/phpunit tests/src/Browser/Album/OffcanvasLinksViewButtonTest.php`

Expected: PASS.

- [ ] **Step 7: Run the full offcanvas/links browser test suite for regressions**

Run: `cd /home/renaud/projects/pokenini-web && docker compose exec php php vendor/bin/phpunit tests/src/Browser/Album/OffcanvasTest.php tests/src/Browser/Album/PrivacyHomeToggleTest.php tests/src/Browser/Album/OffcanvasLinksPickerFilterTest.php tests/src/Browser/Album/OffcanvasLinksSelfLinkGuardTest.php`

Expected: all PASS.

- [ ] **Step 8: Commit**

```bash
cd /home/renaud/projects/pokenini-web
git add templates/Album/_offcanvas.html.twig public/css/album.css translations/messages+intl-icu.fr.yaml translations/messages+intl-icu.en.yaml tests/src/Browser/Album/OffcanvasLinksViewButtonTest.php
git commit -m "Match the picker's 'voir le dex' link to the Trainer page's button"
```

---

### Task 6: pokenini-web — keep the direction selector visible while scrolling the grid

**Files:**
- Modify: `templates/Album/_offcanvas.html.twig:121-134`
- Modify: `public/css/album.css`
- Test: Create `tests/src/Browser/Album/OffcanvasLinksStickyControlsTest.php`

**Interfaces:**
- Consumes: nothing new from earlier tasks.
- Produces: nothing consumed by later tasks.

- [ ] **Step 1: Write the failing browser test**

Create `tests/src/Browser/Album/OffcanvasLinksStickyControlsTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Browser\Album;

use App\Tests\Browser\AbstractBrowserTestCase;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 */
#[CoversNothing]
#[Group('api-mocked-testing')]
final class OffcanvasLinksStickyControlsTest extends AbstractBrowserTestCase
{
    use TestNavTrait;

    #[Test]
    public function directionControlsStickToTopOfOffcanvas(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken('109903422692691643666', 'TestProvider');
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $client->request('GET', '/fr/album/demolite');

        $client->executeScript("document.querySelector('.open-offcanvas').click()");
        $this->assertSelectorWillBeVisible('#offcanvas');
        $client->waitFor('#offcanvas.show:not(.showing)');

        $position = $client->executeScript("return getComputedStyle(document.querySelector('.dex-link-controls')).position;");

        $this->assertSame('sticky', $position);
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `cd /home/renaud/projects/pokenini-web && docker compose exec php php vendor/bin/phpunit tests/src/Browser/Album/OffcanvasLinksStickyControlsTest.php`

Expected: FAIL — `.dex-link-controls` doesn't exist yet (`querySelector` returns `null`, `getComputedStyle(null)` throws, `executeScript` call errors out / returns null, not `'sticky'`).

- [ ] **Step 3: Wrap the direction controls in a sticky container**

In `templates/Album/_offcanvas.html.twig`, replace lines 121-134:

```twig
      <div class="dex-link-controls">
        <div class="btn-group w-100 mb-2" role="group" aria-label="{{ 'album.offcanvas.links.title'|trans }}">
          <input type="radio" class="btn-check" name="link-direction" id="link-direction-to" value="to" autocomplete="off" checked>
          <label class="btn btn-outline-secondary btn-sm" for="link-direction-to"><i class="bi bi-arrow-right"></i> {{ 'album.offcanvas.links.direction.to'|trans }}</label>

          <input type="radio" class="btn-check" name="link-direction" id="link-direction-from" value="from" autocomplete="off">
          <label class="btn btn-outline-secondary btn-sm" for="link-direction-from"><i class="bi bi-arrow-left"></i> {{ 'album.offcanvas.links.direction.from'|trans }}</label>

          <input type="radio" class="btn-check" name="link-direction" id="link-direction-both" value="both" autocomplete="off">
          <label class="btn btn-outline-secondary btn-sm" for="link-direction-both"><i class="bi bi-arrow-left-right"></i> {{ 'album.offcanvas.links.direction.both'|trans }}</label>
        </div>

        <button type="button" class="btn btn-primary w-100 mb-3" id="create-link" disabled>
          <i class="bi bi-plus-lg"></i> {{ 'album.offcanvas.links.create'|trans }}
        </button>
      </div>
```

- [ ] **Step 4: Add the sticky CSS rule**

In `public/css/album.css`, add this rule right after the `#offcanvas { width: 480px; max-width: 100%; }` rule's closing brace (after line 120, before `.dex-picker-grid`):

```css
.dex-link-controls {
    position: sticky;
    top: 0;
    z-index: 2;
    background: var(--bs-body-bg);
    padding-top: .25rem;
}
```

- [ ] **Step 5: Run the test to verify it passes**

Run: `cd /home/renaud/projects/pokenini-web && docker compose exec php php vendor/bin/phpunit tests/src/Browser/Album/OffcanvasLinksStickyControlsTest.php`

Expected: PASS.

- [ ] **Step 6: Run the full offcanvas/links browser test suite one last time**

Run: `cd /home/renaud/projects/pokenini-web && docker compose exec php php vendor/bin/phpunit tests/src/Browser/Album/OffcanvasTest.php tests/src/Browser/Album/PrivacyHomeToggleTest.php tests/src/Browser/Album/OffcanvasLinksPickerFilterTest.php tests/src/Browser/Album/OffcanvasLinksSelfLinkGuardTest.php tests/src/Browser/Album/OffcanvasLinksViewButtonTest.php tests/src/Browser/Album/OffcanvasLinksStickyControlsTest.php`

Expected: all PASS.

- [ ] **Step 7: Full quality gate for the repo**

Run: `cd /home/renaud/projects/pokenini-web && make code-quality` (covers editorconfig, jsonlint, phpcsfixer, phpmd, psalm, phpstan, deptrac, w3c — catches any Twig/CSS/JS lint issues introduced across all six tasks).

- [ ] **Step 8: Commit**

```bash
cd /home/renaud/projects/pokenini-web
git add templates/Album/_offcanvas.html.twig public/css/album.css tests/src/Browser/Album/OffcanvasLinksStickyControlsTest.php
git commit -m "Keep the dex-link direction selector visible while scrolling the picker grid"
```

---

## Spec coverage checklist (self-review)

1. Filters don't do anything → Task 3 (rewritten wiring, `searchBoxIsRemoved` + filter tests). ✅
2. "Lien actif"/"lien non actif" filter → Task 3 (`dex-picker-filter-linked`, `linkedFilterShowsOnlyAlreadyLinkedDexes`). ✅
3. Exactly the Trainer page's filters, no text search → Task 2 (shared macro) + Task 3 (5 shared items wired in, search removed, `releasedFilterIsHiddenForNonAdmin` proves role-gate parity). ✅
4. Current dex selectable as its own link target → Task 4 (JS defensive guards) + Task 1 (server-side guard in pokenini-back). ✅
5. "Voir le dex" button parity → Task 5. ✅
6. Direction selector requires scrolling → Task 6 (sticky). ✅
