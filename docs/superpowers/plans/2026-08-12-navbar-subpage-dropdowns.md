# Navbar Dresseurs/Admin subpage dropdowns Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let the bottom navbar's "Dresseurs" and "Admin" items jump directly to any of their sub-pages via a dropdown, while the main label keeps navigating to the first (default) sub-page exactly as today.

**Architecture:** Pure Twig template change in `templates/_nav.html.twig`. Each of the two nav items becomes a Bootstrap 5 split dropdown (`nav-link` label + separate `dropdown-toggle-split` button, both inside a `.dropdown.dropup` wrapper) listing that section's pages, reusing the exact `{key, label, route}` lists already defined in `templates/Trainer/_tabs.html.twig` and `templates/Admin/_tabs.html.twig` (duplicated locally, matching the project's existing convention of no shared data source between these partials). No controller, route, or JS changes — Bootstrap's bundled JS (already loaded in `base.html.twig`) drives the dropdown.

**Tech Stack:** Symfony 8 / Twig 3.21+ (`|map` filter with arrow function), Bootstrap 5.3.8 (CDN, JS bundle already global), PHPUnit `WebTestCase` integration tests against Moco fixtures.

## Global Constraints

- `declare(strict_types=1)` in every PHP file touched (test files only here).
- Test classes are `final`, carry `/** @internal */`, and `#[CoversClass(...)]` — this plan only edits existing test classes, no new ones, so just keep those attributes intact.
- No new translation strings for sub-page labels — reuse `trainer.dex.title`, `trainer.links.title`, `trainer.personnal_data.title`, `admin.actions.update_data.title`, `admin.actions.update_availabilities.title`, `admin.actions.calculate_data.title`, `admin.actions.invalidate_data.title`, `admin.actions.trigger_pipeline.title`, `title.admin_reports`, `title.admin_versions`. One new key is needed for the toggle button's accessible name: `nav.toggle_submenu` (fr + en), added in Task 1.
- Bootstrap detects `_inNavbar = true` because the dropdown lives inside `<nav class="navbar ...">`, so it skips Popper.js and positions the menu with plain CSS. This means the `.dropdown-toggle-split` `<button>` and the `<ul class="dropdown-menu">` MUST be direct siblings inside the same immediate wrapper as each other (Bootstrap's JS looks up the menu via "next sibling with class `dropdown-menu`" relative to the toggle) — do not nest the `<ul>` at a different depth than the toggle button.
- `make code-quality` (includes w3c HTML validation) must stay green — every task's markup must be valid HTML5 (e.g. `<button>` not `<a href="#">` for the non-navigating toggle).

---

### Task 1: Trainer nav item — split dropdown

**Files:**
- Modify: `templates/_nav.html.twig:26-47`
- Modify: `translations/messages+intl-icu.fr.yaml` (inside the `nav:` block, e.g. after line 24 `cookie-manager: "Gère tes cookies"`)
- Modify: `translations/messages+intl-icu.en.yaml` (same location, English file)
- Test: `tests/src/Integration/Controller/Trainer/TrainerPageTest.php`

**Interfaces:**
- Consumes: existing routes `app_trainerindex_index`, `app_trainerlinks_index`, `app_trainerpersonnaldata_index`; existing translation keys `title.trainer`, `trainer.dex.title`, `trainer.links.title`, `trainer.personnal_data.title`, `nav.login`.
- Produces: translation key `nav.toggle_submenu` (fr: "Voir les sous-pages", en: "View sub-pages") — Task 2 (Admin) reuses this same key, no new key needed there.

- [ ] **Step 1: Add the `nav.toggle_submenu` translation key**

In `translations/messages+intl-icu.fr.yaml`, inside the existing `nav:` block, add a new line right after `cookie-manager: "Gère tes cookies"` (keep 2-space indent matching the sibling keys):

```yaml
  cookie-manager: "Gère tes cookies"
  toggle_submenu: "Voir les sous-pages"
```

In `translations/messages+intl-icu.en.yaml`, same spot, after `cookie-manager: "Cookie manager"`:

```yaml
  cookie-manager: "Cookie manager"
  toggle_submenu: "View sub-pages"
```

- [ ] **Step 2: Replace the trainer nav item markup**

In `templates/_nav.html.twig`, replace lines 26-47:

```twig
                <li class="nav-item trainer-link">
                    {% if is_granted("ROLE_TRAINER") %}
                    {% set onTrainer = currentRoute in [
                        'app_trainerindex_index',
                        'app_trainerlinks_index',
                        'app_trainerpersonnaldata_index',
                    ] %}
                    <a
                        class="nav-link {{ onTrainer ? ' active' : '' }}"
                        {{ onTrainer ? 'aria-current="page"' : '' }}
                        href="{{ path('app_trainerindex_index') }}"
                    >
                        <i class="bi bi-person-badge"></i>
                        {{ 'title.trainer'|trans }}
                    </a>
                    {% else %}
                    <a class="nav-link" href="{{ path('app_connect_index') }}">
                        <i class="bi bi-person"></i>
                        {{ 'nav.login'|trans }}
                    </a>
                    {% endif %}
                </li>
```

with:

```twig
                <li class="nav-item trainer-link">
                    {% if is_granted("ROLE_TRAINER") %}
                    {% set trainerPages = [
                        {'key': 'customization', 'label': 'trainer.dex.title', 'route': 'app_trainerindex_index'},
                        {'key': 'links', 'label': 'trainer.links.title', 'route': 'app_trainerlinks_index'},
                        {'key': 'personnal_data', 'label': 'trainer.personnal_data.title', 'route': 'app_trainerpersonnaldata_index'},
                    ] %}
                    {% set onTrainer = currentRoute in trainerPages|map(p => p.route) %}
                    <div class="dropdown dropup d-flex align-items-center">
                        <a
                            class="nav-link {{ onTrainer ? ' active' : '' }}"
                            {{ onTrainer ? 'aria-current="page"' : '' }}
                            href="{{ path('app_trainerindex_index') }}"
                        >
                            <i class="bi bi-person-badge"></i>
                            {{ 'title.trainer'|trans }}
                        </a>
                        <button
                            class="nav-link dropdown-toggle dropdown-toggle-split"
                            type="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                        >
                            <span class="visually-hidden">{{ 'nav.toggle_submenu'|trans }}</span>
                        </button>
                        <ul class="dropdown-menu">
                            {% for page in trainerPages %}
                            <li>
                                <a
                                    class="dropdown-item{{ currentRoute == page.route ? ' active' : '' }}"
                                    {{ currentRoute == page.route ? 'aria-current="page"' : '' }}
                                    href="{{ path(page.route) }}"
                                >
                                    {{ page.label|trans }}
                                </a>
                            </li>
                            {% endfor %}
                        </ul>
                    </div>
                    {% else %}
                    <a class="nav-link" href="{{ path('app_connect_index') }}">
                        <i class="bi bi-person"></i>
                        {{ 'nav.login'|trans }}
                    </a>
                    {% endif %}
                </li>
```

- [ ] **Step 3: Write the failing test assertions**

In `tests/src/Integration/Controller/Trainer/TrainerPageTest.php`, in the `trainerPage()` test method, right after the existing line `$this->assertCount(3, $crawler->filter('#trainer-section-tab .nav-link'));`, add:

```php
        $this->assertCountFilter($crawler, 3, '.navbar-nav .trainer-link .dropdown-menu .dropdown-item');
        $this->assertCountFilter($crawler, 1, '.navbar-nav .trainer-link .dropdown-item.active');
        $this->assertStringContainsString(
            '/fr/trainer',
            $crawler->filter('.navbar-nav .trainer-link .dropdown-item.active')->attr('href') ?? ''
        );
        $this->assertStringContainsString(
            '/fr/trainer/links',
            $crawler->filter('.navbar-nav .trainer-link .dropdown-item')->eq(1)->attr('href') ?? ''
        );
        $this->assertStringContainsString(
            '/fr/trainer/personnal_data',
            $crawler->filter('.navbar-nav .trainer-link .dropdown-item')->eq(2)->attr('href') ?? ''
        );
```

- [ ] **Step 4: Run the test to verify it currently fails**

Run: `docker compose exec php php vendor/bin/phpunit --filter trainerPage tests/src/Integration/Controller/Trainer/TrainerPageTest.php`
Expected: FAIL (dropdown markup doesn't exist yet) — if Step 2 was already applied before running this, it will PASS instead; either order is fine as long as you've seen it fail once against the old markup. If you applied Step 2 first, temporarily revert `templates/_nav.html.twig` with `git stash`, run the test to confirm the FAIL, then `git stash pop`.

- [ ] **Step 5: Run the test to verify it passes**

Run: `docker compose exec php php vendor/bin/phpunit --filter trainerPage tests/src/Integration/Controller/Trainer/TrainerPageTest.php`
Expected: PASS

- [ ] **Step 6: Run the full nav-touching integration suite**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Trainer`
Expected: PASS (all trainer integration tests, including `TrainerLinksPageTest` and `TrainerPersonnalDataPageTest` which also render the navbar via `TestNavTrait`)

- [ ] **Step 7: Commit**

```bash
git add templates/_nav.html.twig translations/messages+intl-icu.fr.yaml translations/messages+intl-icu.en.yaml tests/src/Integration/Controller/Trainer/TrainerPageTest.php
git commit -m "feat(nav): add direct-link dropdown for trainer sub-pages"
```

---

### Task 2: Admin nav item — split dropdown

**Files:**
- Modify: `templates/_nav.html.twig` (the admin `<li>` block, originally lines 87-107, now shifted down by the Task 1 diff — locate it via the `is_granted("ROLE_ADMIN")` condition)
- Test: `tests/src/Integration/Controller/Admin/AdminUpdateDataTest.php`

**Interfaces:**
- Consumes: existing routes `app_admin_update_data`, `app_admin_update_availabilities`, `app_admin_calculate_data`, `app_admin_invalidate_data`, `app_admin_trigger_pipeline`, `app_admin_reports`, `app_admin_versions`; existing translation keys `admin.actions.update_data.title`, `admin.actions.update_availabilities.title`, `admin.actions.calculate_data.title`, `admin.actions.invalidate_data.title`, `admin.actions.trigger_pipeline.title`, `title.admin_reports`, `title.admin_versions`, `nav.admin`; the `nav.toggle_submenu` key produced by Task 1.
- Produces: nothing consumed by later tasks (this is the last task).

- [ ] **Step 1: Replace the admin nav item markup**

In `templates/_nav.html.twig`, find and replace:

```twig
                {% if is_granted("ROLE_ADMIN") %}
                {% set onAdmin = currentRoute in [
                    'app_admin_update_data',
                    'app_admin_update_availabilities',
                    'app_admin_calculate_data',
                    'app_admin_invalidate_data',
                    'app_admin_trigger_pipeline',
                    'app_admin_reports',
                    'app_admin_versions',
                ] %}
                <li class="nav-item admin-link">
                    <a
                        class="nav-link {{ onAdmin ? ' active' : '' }}"
                        {{ onAdmin ? 'aria-current="page"' : '' }}
                        href="{{ path('app_admin_update_data') }}"
                    >
                        <i class="bi bi-wrench-adjustable-circle"></i>
                        {{ 'nav.admin'|trans }}
                    </a>
                </li>
                {% endif %}
```

with:

```twig
                {% if is_granted("ROLE_ADMIN") %}
                {% set adminPages = [
                    {'key': 'update_data', 'label': 'admin.actions.update_data.title', 'route': 'app_admin_update_data'},
                    {'key': 'update_availabilities', 'label': 'admin.actions.update_availabilities.title', 'route': 'app_admin_update_availabilities'},
                    {'key': 'calculate_data', 'label': 'admin.actions.calculate_data.title', 'route': 'app_admin_calculate_data'},
                    {'key': 'invalidate_data', 'label': 'admin.actions.invalidate_data.title', 'route': 'app_admin_invalidate_data'},
                    {'key': 'trigger_pipeline', 'label': 'admin.actions.trigger_pipeline.title', 'route': 'app_admin_trigger_pipeline'},
                    {'key': 'reports', 'label': 'title.admin_reports', 'route': 'app_admin_reports'},
                    {'key': 'versions', 'label': 'title.admin_versions', 'route': 'app_admin_versions'},
                ] %}
                {% set onAdmin = currentRoute in adminPages|map(p => p.route) %}
                <li class="nav-item admin-link">
                    <div class="dropdown dropup d-flex align-items-center">
                        <a
                            class="nav-link {{ onAdmin ? ' active' : '' }}"
                            {{ onAdmin ? 'aria-current="page"' : '' }}
                            href="{{ path('app_admin_update_data') }}"
                        >
                            <i class="bi bi-wrench-adjustable-circle"></i>
                            {{ 'nav.admin'|trans }}
                        </a>
                        <button
                            class="nav-link dropdown-toggle dropdown-toggle-split"
                            type="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                        >
                            <span class="visually-hidden">{{ 'nav.toggle_submenu'|trans }}</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            {% for page in adminPages %}
                            <li>
                                <a
                                    class="dropdown-item{{ currentRoute == page.route ? ' active' : '' }}"
                                    {{ currentRoute == page.route ? 'aria-current="page"' : '' }}
                                    href="{{ path(page.route) }}"
                                >
                                    {{ page.label|trans }}
                                </a>
                            </li>
                            {% endfor %}
                        </ul>
                    </div>
                </li>
                {% endif %}
```

Note: this uses `dropdown-menu-end` here (unlike Task 1's trainer menu) because the admin item sits near the right edge of the navbar, right before the `logout-link` — without it, a 7-item-tall menu risks overflowing past the right edge of the viewport on narrow screens. The trainer item sits near the left edge, so the default (start-aligned) menu has room to grow rightward.

- [ ] **Step 2: Write the failing test assertions**

In `tests/src/Integration/Controller/Admin/AdminUpdateDataTest.php`, in `testUpdateData()`, right after the existing line `$this->assertCountFilter($crawler, 1, '#admin-actions-tab .nav-link.active');`, add:

```php
        $this->assertCountFilter($crawler, 7, '.navbar-nav .admin-link .dropdown-menu .dropdown-item');
        $this->assertCountFilter($crawler, 1, '.navbar-nav .admin-link .dropdown-item.active');
        $this->assertStringContainsString(
            '/fr/istration/update_data',
            $crawler->filter('.navbar-nav .admin-link .dropdown-item.active')->attr('href') ?? ''
        );
        $this->assertStringContainsString(
            '/fr/istration/reports',
            $crawler->filter('.navbar-nav .admin-link .dropdown-item')->eq(5)->attr('href') ?? ''
        );
        $this->assertStringContainsString(
            '/fr/istration/versions',
            $crawler->filter('.navbar-nav .admin-link .dropdown-item')->eq(6)->attr('href') ?? ''
        );
```

- [ ] **Step 3: Run the test to verify it currently fails**

Run: `docker compose exec php php vendor/bin/phpunit --filter testUpdateData tests/src/Integration/Controller/Admin/AdminUpdateDataTest.php`
Expected: FAIL against the old markup (see Task 1 Step 4 for the stash trick if Step 1 here was already applied).

- [ ] **Step 4: Run the test to verify it passes**

Run: `docker compose exec php php vendor/bin/phpunit --filter testUpdateData tests/src/Integration/Controller/Admin/AdminUpdateDataTest.php`
Expected: PASS

- [ ] **Step 5: Run the full admin integration suite**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Admin`
Expected: PASS (covers every other Admin page that also renders this navbar item, e.g. `AdminReportsTest`, `AdminVersionsTest` if present, via `TestNavTrait`)

- [ ] **Step 6: Commit**

```bash
git add templates/_nav.html.twig tests/src/Integration/Controller/Admin/AdminUpdateDataTest.php
git commit -m "feat(nav): add direct-link dropdown for admin sub-pages"
```

---

### Task 3: Quality gates and visual verification

**Files:** none created; this task only runs checks and, if needed, adds a targeted CSS fix to `public/css/base.css`.

**Interfaces:**
- Consumes: the finished markup from Tasks 1 and 2.
- Produces: nothing (terminal task).

- [ ] **Step 1: Run the full test suite**

Run: `make tests-integration`
Expected: PASS, no regressions in any other test using `TestNavTrait` (e.g. `AccessReleasedTest`, `AccessPremiumTest`, `AccessPrivateTest` under `tests/src/Integration/Controller/Album/Access/`, which assert `.admin-link`/`.trainer-link` presence but not their internal structure — see Context in the design spec).

- [ ] **Step 2: Run code quality checks**

Run: `make code-quality`
Expected: PASS, including the w3c HTML validator step (`<button>` used for the non-navigating toggle keeps this valid — no `<a href="#">`).

- [ ] **Step 3: Manual visual check in a real browser**

Run: `make start`, then open `http://localhost/fr/connect/f/c?t=admin` (Fake authenticator, dev-only route, grants `ROLE_ADMIN` which includes `ROLE_TRAINER`) to land on an authenticated page. Navigate to any page and check the bottom navbar:

- Click the "Dresseurs" label → navigates straight to `/fr/trainer` (unchanged behavior).
- Click the small caret next to "Dresseurs" → dropdown opens **upward** (not clipped below the viewport), listing all 3 trainer pages, with the current page highlighted.
- Same checks for "Admin" (caret opens upward, lists all 7 pages, current page highlighted, menu doesn't overflow the right edge of the screen).
- Resize the browser below the `sm` breakpoint (or use device toolbar) to confirm the collapsed hamburger menu still shows both dropdowns working the same way.

If the split button's caret and label look visually misaligned or cramped (e.g. no gap between them, or the caret's hit-area feels too small on touch), add a small rule to `public/css/base.css`, appended after the existing rules:

```css
.navbar .dropdown-toggle-split {
    padding-left: .5rem;
    padding-right: .5rem;
}
```

Only add this if the manual check shows a real problem — do not add it speculatively.

- [ ] **Step 4: Commit (only if Step 3 required a CSS change)**

```bash
git add public/css/base.css
git commit -m "style(nav): adjust dropdown-toggle-split padding for touch targets"
```
