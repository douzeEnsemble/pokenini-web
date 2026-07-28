# Pokédex Privacy & Add-to-Home Toggle Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let a trainer toggle their album's `is_private` and `is_on_home` flags directly from the Pokédex page (`/album/{dexSlug}`), instead of only from `/trainer`.

**Architecture:** Pure frontend addition. The offcanvas informations panel gains two `form-switch` checkboxes, gated on the page's existing `allowedToEdit` boolean. They reuse the already-generic `public/js/trainer_dex.js` (no JS changes) which `PUT`s to the existing `/trainer/dex/{dexSlug}` route (`TrainerUpsertController`, unchanged) and reuse existing `trainer.dex.attributes.*` / `trainer.dex.update.*` translation keys (no new translations).

**Tech Stack:** Symfony 8 Twig templates, vanilla JS (`trainer_dex.js`), PHPUnit `WebTestCase` (integration, Moco-mocked), Panther (`AbstractBrowserTestCase`, Selenium Chrome/Firefox).

**Design doc:** `docs/superpowers/specs/2026-07-28-pokedex-privacy-home-toggle-design.md`

## Global Constraints

- All commands run inside the `php` Docker container, never on the host (no local PHP toolchain).
- Never create git commits — leave changes staged/unstaged for the user (standing user preference, overrides this skill's default per-step commit instruction).
- Reuse `trainer.dex.attributes.is_private.{label,help}`, `trainer.dex.attributes.is_on_home.{label,help}`, `trainer.dex.update.success.{prefix,radical,suffix}`, `trainer.dex.update.error.{prefix,radical,suffix}` — already defined in both `translations/messages+intl-icu.en.yaml` and `translations/messages+intl-icu.fr.yaml`. No new translation keys.
- Reuse `public/js/trainer_dex.js` verbatim — no JS file changes.
- No backend or BFF changes — `PUT /trainer/dex/{dexSlug}` (`TrainerUpsertController` → `ModifyTrainerDexService` → `Service/Back/ModifyDexService`) is untouched.
- Do not change the existing read-only `.album-private` lock badge or its visibility rule in `Album/_offcanvas.html.twig`.
- Every new/modified integration test class stays `final`, `@internal`, with `#[CoversClass(...)]`; browser test classes use `#[CoversNothing]` (matching every existing file in `tests/src/Browser/`).

---

### Task 1: Render the `is_private`/`is_on_home` switches in the offcanvas

**Files:**
- Modify: `templates/Album/_offcanvas.html.twig` (informations block, right after the `<h2>{{ 'album.offcanvas.informations.title'|trans }}</h2>` line, before the existing `<ul class="list-group ...">` icons list)
- Test: `tests/src/Integration/Controller/Album/Display/OffcanvasTest.php`

**Interfaces:**
- Consumes: `currentDexSlug` (string), `dex.isPrivate` (bool), `dex.isOnHome` (bool), `allowedToEdit` (bool) — all already passed to `Album/_offcanvas.html.twig` today via `Album/index.html.twig`'s render call in `AlbumIndexController::index()`.
- Produces: a `<form data-dex="{{ currentDexSlug }}">` containing two checkboxes with `name="{{ currentDexSlug }}-is_private"` / `name="{{ currentDexSlug }}-is_on_home"` — Task 3's JS wiring depends on this exact `data-dex`/`name` convention (identical to `Trainer/Section/_dex.html.twig`'s existing markup).

- [ ] **Step 1: Write the failing integration tests**

Open `tests/src/Integration/Controller/Album/Display/OffcanvasTest.php` and add two new test methods (do not touch the existing 6 methods):

```php
    public function testSwitchesForOwner(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('12', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/goldsilvercrystal');

        $this->assertCountFilter($crawler, 1, '#offcanvas form[data-dex="goldsilvercrystal"]');

        $isPrivate = $crawler->filter('#offcanvas form[data-dex="goldsilvercrystal"] input[name="goldsilvercrystal-is_private"]');
        $this->assertCount(1, $isPrivate);
        $this->assertNotNull($isPrivate->attr('checked'));

        $isOnHome = $crawler->filter('#offcanvas form[data-dex="goldsilvercrystal"] input[name="goldsilvercrystal-is_on_home"]');
        $this->assertCount(1, $isOnHome);
        $this->assertNull($isOnHome->attr('checked'));
    }

    public function testSwitchesAbsentForAnotherTrainer(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('13', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/demo?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertCountFilter($crawler, 0, '#offcanvas form[data-dex]');
    }
```

These reuse the exact same login/route setup as the existing `testIntroGoldSilverCrystal` (owner, `allowedToEdit` true, `goldsilvercrystal` fixture has `is_private: true`, `is_on_home: false`) and `testIntroDemoAnotherTrainer` (non-owner, `allowedToEdit` false) methods further down in the same file — no new Moco fixtures needed.

- [ ] **Step 2: Run the tests to verify they fail**

Run: `docker compose exec php php vendor/bin/phpunit --filter 'testSwitchesForOwner|testSwitchesAbsentForAnotherTrainer' tests/src/Integration/Controller/Album/Display/OffcanvasTest.php`
Expected: `testSwitchesForOwner` FAILs (`#offcanvas form[data-dex="goldsilvercrystal"]` not found, count 0 instead of 1). `testSwitchesAbsentForAnotherTrainer` PASSes trivially (nothing to render yet) — that's fine, it becomes a real regression guard once Step 3 is done.

- [ ] **Step 3: Add the switches markup**

In `templates/Album/_offcanvas.html.twig`, locate:

```twig
    <div>
      <h2>
        {{ 'album.offcanvas.informations.title'|trans }}
      </h2>

      <ul
        class="list-group list-group-horizontal group-icons">
```

Insert the switches form between the `<h2>` and the `<ul>`:

```twig
    <div>
      <h2>
        {{ 'album.offcanvas.informations.title'|trans }}
      </h2>

      {% if allowedToEdit %}
        <form data-dex="{{ currentDexSlug }}">
          {% set flagAttributes = ['is_private', 'is_on_home'] %}
          {% set flagAttributesIcons = {'is_private': 'incognito', 'is_on_home': 'house-check'} %}
          {% set flagMap = {'is_private': dex.isPrivate, 'is_on_home': dex.isOnHome} %}
          {% for flagAttribute in flagAttributes %}
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" role="switch"
                name="{{ currentDexSlug }}-{{ flagAttribute }}"
                id="offcanvas-{{ currentDexSlug }}-{{ flagAttribute }}"
                {{ flagMap[flagAttribute] ? 'checked' : '' }}>
              <label class="form-check-label" for="offcanvas-{{ currentDexSlug }}-{{ flagAttribute }}">
                <i class="bi bi-{{ flagAttributesIcons[flagAttribute] }}"></i>
                {{ ('trainer.dex.attributes.'~flagAttribute~'.label')|trans }}
              </label>
              <p class="form-text">
                {{ ('trainer.dex.attributes.'~flagAttribute~'.help')|trans }}
              </p>
            </div>
          {% endfor %}
        </form>
      {% endif %}

      <ul
        class="list-group list-group-horizontal group-icons">
```

(Variable names are prefixed `flag` to avoid shadowing the offcanvas's own filter-form `attribute`-less scope — this offcanvas file has no pre-existing `attributes`/`flagMap` variables, so there's no actual collision today, but the prefix keeps intent clear if the file grows.)

- [ ] **Step 4: Run the tests to verify they pass**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Album/Display/OffcanvasTest.php`
Expected: all 8 methods (6 existing + 2 new) PASS.

---

### Task 2: Add success/error toasts for the flag update

**Files:**
- Modify: `templates/Album/_toasts.html.twig`
- Test: `tests/src/Integration/Controller/Album/Display/OffcanvasTest.php`

**Interfaces:**
- Consumes: `allowedToEdit` (bool), `currentDexSlug` (string), `dex.frenchName` / `dex.name` (string), `locale` (string) — `locale` is set in `Album/index.html.twig` (`{% set locale = app.request.locale %}`) and available to includes.
- Produces: `#successToast-{{ currentDexSlug }}` and `#errorToast-{{ currentDexSlug }}` DOM elements — Task 3's browser tests assert visibility of these exact ids, matching the id convention `trainer_dex.js`'s `saveChange()` already hardcodes (`'successToast-'+dexSlug`, `'errorToast-'+dexSlug`).

- [ ] **Step 1: Write the failing integration test**

Add to `tests/src/Integration/Controller/Album/Display/OffcanvasTest.php`:

```php
    public function testFlagToastsForOwner(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('12', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/goldsilvercrystal');

        $this->assertCountFilter($crawler, 1, '#successToast-goldsilvercrystal');
        $this->assertCountFilter($crawler, 1, '#errorToast-goldsilvercrystal');
    }

    public function testFlagToastsAbsentForAnotherTrainer(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('13', 'TestProvider');
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/demo?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $this->assertCountFilter($crawler, 0, '#successToast-demo');
        $this->assertCountFilter($crawler, 0, '#errorToast-demo');
    }
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `docker compose exec php php vendor/bin/phpunit --filter 'testFlagToastsForOwner|testFlagToastsAbsentForAnotherTrainer' tests/src/Integration/Controller/Album/Display/OffcanvasTest.php`
Expected: `testFlagToastsForOwner` FAILs (toast ids not found, count 0 instead of 1). `testFlagToastsAbsentForAnotherTrainer` PASSes trivially.

- [ ] **Step 3: Add the toast markup**

Current `templates/Album/_toasts.html.twig`:

```twig
{% import "Album/_album_macros.html.twig" as macro %}

{% if allowedToEdit or not dex.isPrivate %}
    <div class="toast-container position-fixed bottom-0 mb-5 end-0 p-3">
        {% if allowedToEdit %}
            {% for item in list %}
                {{ macro.toasts(item) }}
            {% endfor %}
        {% endif %}

        {% if not dex.isPrivate %}
            {{ macro.shareToasts() }}
        {% endif %}
    </div>
{% endif %}
```

Replace the `{% if allowedToEdit %}` per-Pokémon block with:

```twig
{% import "Album/_album_macros.html.twig" as macro %}

{% if allowedToEdit or not dex.isPrivate %}
    <div class="toast-container position-fixed bottom-0 mb-5 end-0 p-3">
        {% if allowedToEdit %}
            {% for item in list %}
                {{ macro.toasts(item) }}
            {% endfor %}

            {% set dexName = locale is same as('fr') ? dex.frenchName : dex.name %}
            <div id="successToast-{{ currentDexSlug }}" class="toast text-bg-success" role="alert" aria-live="assertive" aria-atomic="true">
              <div class="d-flex">
                <div class="toast-body">
                  {{ 'trainer.dex.update.success.prefix'|trans }}
                  <strong>{{ 'trainer.dex.update.success.radical'|trans({'dexName': dexName}) }}</strong>
                  {{ 'trainer.dex.update.success.suffix'|trans }}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
              </div>
            </div>

            <div id="errorToast-{{ currentDexSlug }}" class="toast text-bg-danger" role="alert" aria-live="assertive" aria-atomic="true">
              <div class="d-flex">
                <div class="toast-body">
                  {{ 'trainer.dex.update.error.prefix'|trans }}
                  <strong>{{ 'trainer.dex.update.error.radical'|trans({'dexName': dexName}) }}</strong>
                  {{ 'trainer.dex.update.error.suffix'|trans }}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
              </div>
            </div>
        {% endif %}

        {% if not dex.isPrivate %}
            {{ macro.shareToasts() }}
        {% endif %}
    </div>
{% endif %}
```

- [ ] **Step 4: Run the tests to verify they pass**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Album/Display/OffcanvasTest.php`
Expected: all 10 methods PASS.

---

### Task 3: Wire the existing `trainer_dex.js` into the Album page

**Files:**
- Modify: `templates/Album/index.html.twig` (the existing `{% if allowedToEdit %}` block inside `foot_javascript`)
- Test: new `tests/src/Browser/Album/PrivacyHomeToggleTest.php`

**Interfaces:**
- Consumes: `public/js/trainer_dex.js`'s global `watchAttributes()` function (no signature change — it already binds every `input[type="checkbox"]` on the page to `onChangeAttributes`/`saveChange`, which reads `form[data-dex]` and PUTs to `/trainer/dex/{dexSlug}`); the global `const locale` already defined in this same script block.
- Produces: none for later tasks — this is the last task in the plan.

- [ ] **Step 1: Write the failing browser test**

Create `tests/src/Browser/Album/PrivacyHomeToggleTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Browser\Album;

use App\Tests\Browser\AbstractBrowserTestCase;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Panther\DomCrawler\Field\ChoiceFormField;

/**
 * @internal
 */
#[CoversNothing]
#[Group('api-mocked-testing')]
final class PrivacyHomeToggleTest extends AbstractBrowserTestCase
{
    use TestNavTrait;

    public function testSuccessTickIsOnHome(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $crawler = $client->request('GET', '/fr/album/goldsilvercrystal');

        $this->assertSelectorIsNotVisible('#successToast-goldsilvercrystal');

        $form = $crawler->filter('#offcanvas form[data-dex="goldsilvercrystal"]')->form();

        /** @var ChoiceFormField $field */
        $field = $form->get('goldsilvercrystal-is_on_home');
        $field->tick();

        $this->assertSelectorWillBeVisible('#successToast-goldsilvercrystal');
        $this->assertSelectorWillNotBeVisible('#successToast-goldsilvercrystal');
        $this->assertSelectorWillNotBeVisible('#errorToast-goldsilvercrystal');
    }

    public function testSuccessUntickIsPrivate(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $crawler = $client->request('GET', '/fr/album/goldsilvercrystal');

        $this->assertSelectorIsNotVisible('#successToast-goldsilvercrystal');

        $form = $crawler->filter('#offcanvas form[data-dex="goldsilvercrystal"]')->form();

        /** @var ChoiceFormField $field */
        $field = $form->get('goldsilvercrystal-is_private');
        $field->untick();

        $this->assertSelectorWillBeVisible('#successToast-goldsilvercrystal');
        $this->assertSelectorWillNotBeVisible('#successToast-goldsilvercrystal');
        $this->assertSelectorWillNotBeVisible('#errorToast-goldsilvercrystal');
    }

    public function testErrorTickIsOnHome(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addAdminRole();
        $this->loginUser($client, $user);

        $crawler = $client->request('GET', '/fr/album/redgreenblueyellow');

        $this->assertSelectorIsNotVisible('#errorToast-redgreenblueyellow');

        $form = $crawler->filter('#offcanvas form[data-dex="redgreenblueyellow"]')->form();

        /** @var ChoiceFormField $field */
        $field = $form->get('redgreenblueyellow-is_on_home');
        $field->tick();

        $this->assertSelectorWillBeVisible('#errorToast-redgreenblueyellow');
        $this->assertSelectorWillNotBeVisible('#errorToast-redgreenblueyellow');
        $this->assertSelectorWillNotBeVisible('#successToast-redgreenblueyellow');
    }

    public function testErrorUntickIsPrivate(): void
    {
        $client = $this->getNewClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addAdminRole();
        $this->loginUser($client, $user);

        $crawler = $client->request('GET', '/fr/album/redgreenblueyellow');

        $this->assertSelectorIsNotVisible('#errorToast-redgreenblueyellow');

        $form = $crawler->filter('#offcanvas form[data-dex="redgreenblueyellow"]')->form();

        /** @var ChoiceFormField $field */
        $field = $form->get('redgreenblueyellow-is_private');
        $field->untick();

        $this->assertSelectorWillBeVisible('#errorToast-redgreenblueyellow');
        $this->assertSelectorWillNotBeVisible('#errorToast-redgreenblueyellow');
        $this->assertSelectorWillNotBeVisible('#successToast-redgreenblueyellow');
    }
}
```

This mirrors `tests/src/Browser/Trainer/CustomAlbumTrainerTest.php` exactly (same slugs, same tick/untick pairing), reusing its proven Moco fixtures: `PUT /trainer/dex/goldsilvercrystal` falls through to the generic `put /trainer/dex/[\w-]* → 201` rule (success); `PUT /trainer/dex/redgreenblueyellow` matches a specific rule returning `500` (error) for the roles/token this test logs in with.

`redgreenblueyellow`'s fixture has `is_released: false`, so the test logs in as admin (`addAdminRole()`) rather than plain trainer — `AlbumIndexController::accessDexIsGranted()` blocks non-admins from unreleased dexes with a 404, but doesn't affect `editDexIsGranted()` (which only cares about ownership + premium, and this dex has `is_premium: false`), so the switches still render and are editable.

- [ ] **Step 2: Run the tests to verify they fail**

Run each in turn (the container needs the `chrome` service up, started by `make start`):
```bash
docker compose exec -e PANTHER_SELENIUM_HOST=http://chrome:4444/wd/hub -e PANTHER_BROWSER_NAME=chrome php php vendor/bin/phpunit tests/src/Browser/Album/PrivacyHomeToggleTest.php
```
Expected: FAIL — `$crawler->filter('#offcanvas form[data-dex="goldsilvercrystal"]')->form()` throws because that form doesn't exist yet without the JS being wired (the switches markup exists from Task 1, but ticking them does nothing without `watchAttributes()` being called, so no toast ever appears and `assertSelectorWillBeVisible` times out).

- [ ] **Step 3: Wire `trainer_dex.js`**

In `templates/Album/index.html.twig`, the existing `{% if allowedToEdit %}` block in `foot_javascript` currently reads:

```twig
    {% if allowedToEdit %}
    <script src="{{ asset('js/album-edit.js') }}"></script>

    <script>
    const catchStates = JSON.parse('{{ catchStates | map(cs => {name: cs.name, frenchName: cs.frenchName, slug: cs.slug, color: cs.color}) | json_encode | raw }}');
    const locale = '{{ locale }}';
    const dex = '{{ currentDexSlug }}';
    </script>

    <script>
    (function() {
        watchToggleEditMode();
        watchCatchStates();
        watchToggleShinyMode();
        watchToAdjustSelectSizes();
    })();
    </script>
    {% endif %}
```

Change it to:

```twig
    {% if allowedToEdit %}
    <script src="{{ asset('js/album-edit.js') }}"></script>
    <script src="{{ asset('js/trainer_dex.js') }}"></script>

    <script>
    const catchStates = JSON.parse('{{ catchStates | map(cs => {name: cs.name, frenchName: cs.frenchName, slug: cs.slug, color: cs.color}) | json_encode | raw }}');
    const locale = '{{ locale }}';
    const dex = '{{ currentDexSlug }}';
    </script>

    <script>
    (function() {
        watchToggleEditMode();
        watchCatchStates();
        watchToggleShinyMode();
        watchToAdjustSelectSizes();
        watchAttributes();
    })();
    </script>
    {% endif %}
```

- [ ] **Step 4: Run the tests to verify they pass**

Run:
```bash
docker compose exec -e PANTHER_SELENIUM_HOST=http://chrome:4444/wd/hub -e PANTHER_BROWSER_NAME=chrome php php vendor/bin/phpunit --display-all --cache-directory=.phpunit.cache/chrome tests/src/Browser/Album/PrivacyHomeToggleTest.php
docker compose exec -e PANTHER_SELENIUM_HOST=http://firefox:4444/wd/hub -e PANTHER_BROWSER_NAME=firefox php php vendor/bin/phpunit --display-all --cache-directory=.phpunit.cache/firefox tests/src/Browser/Album/PrivacyHomeToggleTest.php
```
Expected: all 4 methods PASS against both Chrome and Firefox.

- [ ] **Step 5: Run the full test suite and quality gates**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Integration
```
Expected: PASS (no regressions in `OffcanvasTest.php` or elsewhere).

```bash
docker compose exec php php tools/phpstan/vendor/bin/phpstan analyse
docker compose exec php php tools/psalm/vendor/bin/psalm --show-info=false
docker compose exec php php tools/phpcsfixer/vendor/bin/php-cs-fixer fix --dry-run --diff
```
Expected: no new errors (only Twig/JS files changed, so PHPStan/Psalm should be unaffected; CS Fixer only touches PHP, and the one new PHP file — `PrivacyHomeToggleTest.php` — should already follow the style of its sibling `CustomAlbumTrainerTest.php`).

---

## Self-Review Notes

- **Spec coverage:** Design doc's four numbered "Changes" sections map 1:1 to Tasks 1–3 (offcanvas markup → Task 1, toasts → Task 2, JS wiring → Task 3); translations needed no task since no new keys are introduced.
- **Placeholder scan:** none — every step has literal file paths, literal Twig/PHP code, and literal run commands.
- **Type/name consistency:** `data-dex` / `name="{slug}-{attribute}"` convention is identical across Task 1 (Twig), Task 3 (browser test's `$form->get(...)`), and the untouched `trainer_dex.js`; toast ids (`successToast-{slug}` / `errorToast-{slug}`) are identical across Task 2 (Twig) and Task 3 (test assertions).
