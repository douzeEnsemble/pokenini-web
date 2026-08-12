# Trainer/Admin Sidebar Navigation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the horizontal tab bar on the 3 Trainer pages and 7 Admin pages with an always-visible vertical sidebar menu (`col-md-3` beside `col-md-9` content at `md`+, stacked full-width below `md` — no toggle, no JS).

**Architecture:** Pure Twig/CSS change across `templates/Trainer/_tabs.html.twig`, `templates/Admin/_tabs.html.twig`, and the 10 page templates that include them. `nav nav-tabs nav-fill` becomes `nav flex-column nav-pills` (Bootstrap's own documented tabs→sidebar conversion); every page's `{% block container %}` moves from three stacked full-width rows to a title row plus a two-column row. No controller, route, or translation changes.

**Tech Stack:** Symfony 8 / Twig, Bootstrap 5.3.8 (`flex-column`, `nav-pills`, `sticky-top` utilities — all CSS-only, no new JS), PHPUnit `WebTestCase` integration tests (existing assertions, unmodified), Panther for manual visual verification.

## Global Constraints

- Every existing test selector must keep matching unmodified: `#trainer-section-tab .nav-link`, `#trainer-section-tab .nav-link.active`, `#admin-actions-tab > .nav-item > .nav-link`, `#admin-actions-tab .nav-link.active`, `a.nav-link` by position within `#admin-actions-tab`. This means `id`, `.nav-item`, `.nav-link`, `.active` classes and DOM order must not change — only the `<ul>`'s own class list (`nav-tabs nav-fill` → `flex-column nav-pills`) and the `role` attributes being dropped.
- `role="tablist"` (on the `<ul>`) and `role="presentation"` (on each `<li>`) are removed in both partials — confirmed no test asserts on these.
- `Admin/_versions.html.twig`'s root `<div class="list-group col-4">` must stay inside a `row justify-content-center` context (its content-column wrapper needs `class="col-md-9 row justify-content-center"`, not a bare `col-md-9`) — this is the one page that isn't a mechanical copy of the others.
- No new translation strings. No changes to `Admin/_macros.html.twig` or any `_update_data.html.twig`-style content partial — they already open their own internal `.row` and nest unmodified inside the new narrower content column.
- `make code-quality`'s `w3c` sub-check is currently not reliable evidence (see prior branch's final review — it validates the anonymous page, not the authenticated Trainer/Admin pages, and doesn't fail the build on errors). Don't treat a clean `make w3c` run as proof; rely on `make tests-integration` and real-browser visual verification instead.

---

### Task 1: Trainer sidebar

**Files:**
- Modify: `templates/Trainer/_tabs.html.twig`
- Modify: `templates/Trainer/index.html.twig`
- Modify: `templates/Trainer/links.html.twig`
- Modify: `templates/Trainer/personnal_data.html.twig`

**Interfaces:**
- Consumes: existing `trainerPages` array (routes `app_trainerindex_index`, `app_trainerlinks_index`, `app_trainerpersonnaldata_index`; labels `trainer.dex.title`, `trainer.links.title`, `trainer.personnal_data.title`), existing content partials `Trainer/Section/_dex.html.twig`, `Trainer/Section/_links.html.twig`, `Trainer/Section/_personnal_data.html.twig` (unmodified).
- Produces: nothing consumed by Task 2 (independent section).

- [ ] **Step 1: Rewrite `templates/Trainer/_tabs.html.twig`**

Replace the entire file content:

```twig
{% set trainerPages = [
  {'key': 'customization', 'label': 'trainer.dex.title', 'route': 'app_trainerindex_index'},
  {'key': 'links', 'label': 'trainer.links.title', 'route': 'app_trainerlinks_index'},
  {'key': 'personnal_data', 'label': 'trainer.personnal_data.title', 'route': 'app_trainerpersonnaldata_index'},
] %}

<ul class="nav nav-tabs nav-fill mb-4" id="trainer-section-tab" role="tablist">
  {% for page in trainerPages %}
    <li class="nav-item" role="presentation">
      <a
        class="nav-link{{ page.key == active ? ' active' : '' }}"
        {{ page.key == active ? 'aria-current="page"' : '' }}
        href="{{ path(page.route) }}"
      >
        {{ page.label|trans }}
      </a>
    </li>
  {% endfor %}
</ul>
```

with:

```twig
{% set trainerPages = [
  {'key': 'customization', 'label': 'trainer.dex.title', 'route': 'app_trainerindex_index'},
  {'key': 'links', 'label': 'trainer.links.title', 'route': 'app_trainerlinks_index'},
  {'key': 'personnal_data', 'label': 'trainer.personnal_data.title', 'route': 'app_trainerpersonnaldata_index'},
] %}

<ul class="nav flex-column nav-pills mb-4 mb-md-0" id="trainer-section-tab">
  {% for page in trainerPages %}
    <li class="nav-item">
      <a
        class="nav-link{{ page.key == active ? ' active' : '' }}"
        {{ page.key == active ? 'aria-current="page"' : '' }}
        href="{{ path(page.route) }}"
      >
        {{ page.label|trans }}
      </a>
    </li>
  {% endfor %}
</ul>
```

- [ ] **Step 2: Rewrite the container block in `templates/Trainer/index.html.twig`**

Replace:

```twig
{% block container %}
  <div class="row">
    <div class="col-md-10 mx-auto text-center row">
      <h1>{{ 'title.trainer'|trans }}</h1>
    </div>
  </div>

  <div class="row">
    <div class="col-md-12 mx-auto">
      {{ include('Trainer/_tabs.html.twig', {'active': 'customization'}) }}
    </div>
  </div>

  <div class="row">
    <div class="col-md-12 mx-auto row">

      <p class="text-secondary">{{ 'trainer.welcome'|trans|htmlNl2br }}</p>

      {{ include('Trainer/Section/_dex.html.twig') }}
    </div>
  </div>
{% endblock container %}
```

with:

```twig
{% block container %}
  <div class="row">
    <div class="col-md-10 mx-auto text-center row">
      <h1>{{ 'title.trainer'|trans }}</h1>
    </div>
  </div>

  <div class="row">
    <div class="col-md-3 sticky-top">
      {{ include('Trainer/_tabs.html.twig', {'active': 'customization'}) }}
    </div>
    <div class="col-md-9">
      <p class="text-secondary">{{ 'trainer.welcome'|trans|htmlNl2br }}</p>

      {{ include('Trainer/Section/_dex.html.twig') }}
    </div>
  </div>
{% endblock container %}
```

- [ ] **Step 3: Rewrite the container block in `templates/Trainer/links.html.twig`**

Replace:

```twig
{% block container %}
  <div class="row">
    <div class="col-md-10 mx-auto text-center row">
      <h1>{{ 'title.trainer'|trans }}</h1>
    </div>
  </div>

  <div class="row">
    <div class="col-md-12 mx-auto">
      {{ include('Trainer/_tabs.html.twig', {'active': 'links'}) }}
    </div>
  </div>

  <div class="row">
    <div class="col-md-12 mx-auto row">
      {{ include('Trainer/Section/_links.html.twig') }}
    </div>
  </div>
{% endblock container %}
```

with:

```twig
{% block container %}
  <div class="row">
    <div class="col-md-10 mx-auto text-center row">
      <h1>{{ 'title.trainer'|trans }}</h1>
    </div>
  </div>

  <div class="row">
    <div class="col-md-3 sticky-top">
      {{ include('Trainer/_tabs.html.twig', {'active': 'links'}) }}
    </div>
    <div class="col-md-9">
      {{ include('Trainer/Section/_links.html.twig') }}
    </div>
  </div>
{% endblock container %}
```

- [ ] **Step 4: Rewrite the container block in `templates/Trainer/personnal_data.html.twig`**

Replace:

```twig
{% block container %}
  <div class="row">
    <div class="col-md-10 mx-auto text-center row">
      <h1>{{ 'title.trainer'|trans }}</h1>
    </div>
  </div>

  <div class="row">
    <div class="col-md-12 mx-auto">
      {{ include('Trainer/_tabs.html.twig', {'active': 'personnal_data'}) }}
    </div>
  </div>

  <div class="row">
    <div class="col-md-12 mx-auto row">
      {{ include('Trainer/Section/_personnal_data.html.twig') }}
    </div>
  </div>
{% endblock container %}
```

with:

```twig
{% block container %}
  <div class="row">
    <div class="col-md-10 mx-auto text-center row">
      <h1>{{ 'title.trainer'|trans }}</h1>
    </div>
  </div>

  <div class="row">
    <div class="col-md-3 sticky-top">
      {{ include('Trainer/_tabs.html.twig', {'active': 'personnal_data'}) }}
    </div>
    <div class="col-md-9">
      {{ include('Trainer/Section/_personnal_data.html.twig') }}
    </div>
  </div>
{% endblock container %}
```

- [ ] **Step 5: Run the Trainer integration suite**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Trainer`
Expected: PASS, no changes needed — these tests assert on `#trainer-section-tab` ids/classes/hrefs, all preserved by Steps 1-4.

- [ ] **Step 6: Visual verification (one Trainer page, two widths)**

Write a throwaway Panther test (not committed — same disposable-test technique used on the earlier navbar-dropdown branch: create it under `tests/src/Browser/`, run it, read the screenshot, delete the test file and screenshot afterward). Cover `/fr/trainer`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Browser;

use App\Tests\Utils\GetUserToken;
use Facebook\WebDriver\WebDriverDimension;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 */
#[CoversNothing]
final class _ManualSidebarCheckTest extends AbstractBrowserTestCase
{
    #[Test]
    public function trainerSidebarAtTwoWidths(): void
    {
        $client = self::getNewClient();
        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $this->loginUser($client, $user);

        $client->manage()->window()->setSize(new WebDriverDimension(1280, 900));
        $client->request('GET', '/fr/trainer');
        $client->takeScreenshot('/app/var/screenshots/manual_sidebar_trainer_wide.png');

        $client->manage()->window()->setSize(new WebDriverDimension(375, 900));
        $client->request('GET', '/fr/trainer');
        $client->takeScreenshot('/app/var/screenshots/manual_sidebar_trainer_narrow.png');

        self::assertTrue(true);
    }
}
```

Run: `docker compose exec -e PANTHER_SELENIUM_HOST=http://chrome:4444/wd/hub -e PANTHER_BROWSER_NAME=chrome php php vendor/bin/phpunit --filter trainerSidebarAtTwoWidths tests/src/Browser/_ManualSidebarCheckTest.php`

Read both screenshots. Confirm:
- At 1280px: the "Ton espace de dresseur" / "C'est quoi ton dex ?" / "Données personnelles" menu sits as a narrow column to the left of the page content, not above it.
- At 375px: the menu sits as a full-width block stacked above the page content, not squeezed beside it.

Delete the throwaway test file and both screenshots afterward.

- [ ] **Step 7: Commit**

```bash
git add templates/Trainer/_tabs.html.twig templates/Trainer/index.html.twig templates/Trainer/links.html.twig templates/Trainer/personnal_data.html.twig
git commit -m "feat(trainer): replace section tabs with a sidebar menu"
```

---

### Task 2: Admin sidebar

**Files:**
- Modify: `templates/Admin/_tabs.html.twig`
- Modify: `templates/Admin/update_data.html.twig`
- Modify: `templates/Admin/update_availabilities.html.twig`
- Modify: `templates/Admin/calculate_data.html.twig`
- Modify: `templates/Admin/invalidate_data.html.twig`
- Modify: `templates/Admin/trigger_pipeline.html.twig`
- Modify: `templates/Admin/reports.html.twig`
- Modify: `templates/Admin/versions.html.twig`

**Interfaces:**
- Consumes: existing `adminPages` array (7 routes/labels, unchanged), existing content partials `Admin/_update_data.html.twig` and five siblings, `Admin/_reports.html.twig`, `Admin/_versions.html.twig` (all unmodified — each already opens its own internal `.row`).
- Produces: nothing consumed by Task 1 (independent section).

- [ ] **Step 1: Rewrite `templates/Admin/_tabs.html.twig`**

Replace the entire file content:

```twig
{% set adminPages = [
  {'key': 'update_data', 'label': 'admin.actions.update_data.title', 'route': 'app_admin_update_data'},
  {'key': 'update_availabilities', 'label': 'admin.actions.update_availabilities.title', 'route': 'app_admin_update_availabilities'},
  {'key': 'calculate_data', 'label': 'admin.actions.calculate_data.title', 'route': 'app_admin_calculate_data'},
  {'key': 'invalidate_data', 'label': 'admin.actions.invalidate_data.title', 'route': 'app_admin_invalidate_data'},
  {'key': 'trigger_pipeline', 'label': 'admin.actions.trigger_pipeline.title', 'route': 'app_admin_trigger_pipeline'},
  {'key': 'reports', 'label': 'title.admin_reports', 'route': 'app_admin_reports'},
  {'key': 'versions', 'label': 'title.admin_versions', 'route': 'app_admin_versions'},
] %}

<ul class="nav nav-tabs nav-fill mb-4" id="admin-actions-tab" role="tablist">
  {% for page in adminPages %}
    <li class="nav-item" role="presentation">
      <a
        class="nav-link{{ page.key == active ? ' active' : '' }}"
        {{ page.key == active ? 'aria-current="page"' : '' }}
        href="{{ path(page.route) }}"
      >
        {{ page.label|trans }}
      </a>
    </li>
  {% endfor %}
</ul>
```

with:

```twig
{% set adminPages = [
  {'key': 'update_data', 'label': 'admin.actions.update_data.title', 'route': 'app_admin_update_data'},
  {'key': 'update_availabilities', 'label': 'admin.actions.update_availabilities.title', 'route': 'app_admin_update_availabilities'},
  {'key': 'calculate_data', 'label': 'admin.actions.calculate_data.title', 'route': 'app_admin_calculate_data'},
  {'key': 'invalidate_data', 'label': 'admin.actions.invalidate_data.title', 'route': 'app_admin_invalidate_data'},
  {'key': 'trigger_pipeline', 'label': 'admin.actions.trigger_pipeline.title', 'route': 'app_admin_trigger_pipeline'},
  {'key': 'reports', 'label': 'title.admin_reports', 'route': 'app_admin_reports'},
  {'key': 'versions', 'label': 'title.admin_versions', 'route': 'app_admin_versions'},
] %}

<ul class="nav flex-column nav-pills mb-4 mb-md-0" id="admin-actions-tab">
  {% for page in adminPages %}
    <li class="nav-item">
      <a
        class="nav-link{{ page.key == active ? ' active' : '' }}"
        {{ page.key == active ? 'aria-current="page"' : '' }}
        href="{{ path(page.route) }}"
      >
        {{ page.label|trans }}
      </a>
    </li>
  {% endfor %}
</ul>
```

- [ ] **Step 2: Rewrite the container block in `templates/Admin/update_data.html.twig`**

Replace:

```twig
{% block container %}
<div id="admin" class="row">
  <div class="col-12">
    <h1 class="text-center">{{ 'title.admin'|trans }}</h1>
    {% include 'Admin/_tabs.html.twig' with {'active': 'update_data'} %}
    {% include 'Admin/_update_data.html.twig' %}
  </div>
</div>
{% endblock %}
```

with:

```twig
{% block container %}
<div id="admin" class="row">
  <div class="col-12">
    <h1 class="text-center">{{ 'title.admin'|trans }}</h1>
  </div>
  <div class="col-md-3 sticky-top">
    {% include 'Admin/_tabs.html.twig' with {'active': 'update_data'} %}
  </div>
  <div class="col-md-9">
    {% include 'Admin/_update_data.html.twig' %}
  </div>
</div>
{% endblock %}
```

- [ ] **Step 3: Rewrite the container block in `templates/Admin/update_availabilities.html.twig`**

Replace:

```twig
{% block container %}
<div id="admin" class="row">
  <div class="col-12">
    <h1 class="text-center">{{ 'title.admin'|trans }}</h1>
    {% include 'Admin/_tabs.html.twig' with {'active': 'update_availabilities'} %}
    {% include 'Admin/_update_availabilities.html.twig' %}
  </div>
</div>
{% endblock %}
```

with:

```twig
{% block container %}
<div id="admin" class="row">
  <div class="col-12">
    <h1 class="text-center">{{ 'title.admin'|trans }}</h1>
  </div>
  <div class="col-md-3 sticky-top">
    {% include 'Admin/_tabs.html.twig' with {'active': 'update_availabilities'} %}
  </div>
  <div class="col-md-9">
    {% include 'Admin/_update_availabilities.html.twig' %}
  </div>
</div>
{% endblock %}
```

- [ ] **Step 4: Rewrite the container block in `templates/Admin/calculate_data.html.twig`**

Replace:

```twig
{% block container %}
<div id="admin" class="row">
  <div class="col-12">
    <h1 class="text-center">{{ 'title.admin'|trans }}</h1>
    {% include 'Admin/_tabs.html.twig' with {'active': 'calculate_data'} %}
    {% include 'Admin/_calculate_data.html.twig' %}
  </div>
</div>
{% endblock %}
```

with:

```twig
{% block container %}
<div id="admin" class="row">
  <div class="col-12">
    <h1 class="text-center">{{ 'title.admin'|trans }}</h1>
  </div>
  <div class="col-md-3 sticky-top">
    {% include 'Admin/_tabs.html.twig' with {'active': 'calculate_data'} %}
  </div>
  <div class="col-md-9">
    {% include 'Admin/_calculate_data.html.twig' %}
  </div>
</div>
{% endblock %}
```

- [ ] **Step 5: Rewrite the container block in `templates/Admin/invalidate_data.html.twig`**

Replace:

```twig
{% block container %}
<div id="admin" class="row">
  <div class="col-12">
    <h1 class="text-center">{{ 'title.admin'|trans }}</h1>
    {% include 'Admin/_tabs.html.twig' with {'active': 'invalidate_data'} %}
    {% include 'Admin/_invalidate_data.html.twig' %}
  </div>
</div>
{% endblock %}
```

with:

```twig
{% block container %}
<div id="admin" class="row">
  <div class="col-12">
    <h1 class="text-center">{{ 'title.admin'|trans }}</h1>
  </div>
  <div class="col-md-3 sticky-top">
    {% include 'Admin/_tabs.html.twig' with {'active': 'invalidate_data'} %}
  </div>
  <div class="col-md-9">
    {% include 'Admin/_invalidate_data.html.twig' %}
  </div>
</div>
{% endblock %}
```

- [ ] **Step 6: Rewrite the container block in `templates/Admin/trigger_pipeline.html.twig`**

Replace:

```twig
{% block container %}
<div id="admin" class="row">
  <div class="col-12">
    <h1 class="text-center">{{ 'title.admin'|trans }}</h1>
    {% include 'Admin/_tabs.html.twig' with {'active': 'trigger_pipeline'} %}
    {% include 'Admin/_trigger_pipeline.html.twig' %}
  </div>
</div>
{% endblock %}
```

with:

```twig
{% block container %}
<div id="admin" class="row">
  <div class="col-12">
    <h1 class="text-center">{{ 'title.admin'|trans }}</h1>
  </div>
  <div class="col-md-3 sticky-top">
    {% include 'Admin/_tabs.html.twig' with {'active': 'trigger_pipeline'} %}
  </div>
  <div class="col-md-9">
    {% include 'Admin/_trigger_pipeline.html.twig' %}
  </div>
</div>
{% endblock %}
```

- [ ] **Step 7: Rewrite the container block in `templates/Admin/reports.html.twig`**

Replace:

```twig
{% block container %}
<div id="admin" class="row">
  <div class="col-12">
    <h1 class="text-center">{{ 'title.admin'|trans }}</h1>
    {% include 'Admin/_tabs.html.twig' with {'page': 'reports', 'active': 'reports'} %}
    {% include 'Admin/_reports.html.twig' %}
  </div>
</div>
{% endblock %}
```

with:

```twig
{% block container %}
<div id="admin" class="row">
  <div class="col-12">
    <h1 class="text-center">{{ 'title.admin'|trans }}</h1>
  </div>
  <div class="col-md-3 sticky-top">
    {% include 'Admin/_tabs.html.twig' with {'page': 'reports', 'active': 'reports'} %}
  </div>
  <div class="col-md-9">
    {% include 'Admin/_reports.html.twig' %}
  </div>
</div>
{% endblock %}
```

- [ ] **Step 8: Rewrite the container block in `templates/Admin/versions.html.twig`**

Replace:

```twig
{% block container %}
<div id="admin" class="row">
  <div class="col-12 row justify-content-center">
    <h1 class="text-center">{{ 'title.admin'|trans }}</h1>
    {% include 'Admin/_tabs.html.twig' with {'page': 'versions', 'active': 'versions'} %}
    {% include 'Admin/_versions.html.twig' %}
  </div>
</div>
{% endblock %}
```

with:

```twig
{% block container %}
<div id="admin" class="row">
  <div class="col-12">
    <h1 class="text-center">{{ 'title.admin'|trans }}</h1>
  </div>
  <div class="col-md-3 sticky-top">
    {% include 'Admin/_tabs.html.twig' with {'page': 'versions', 'active': 'versions'} %}
  </div>
  <div class="col-md-9 row justify-content-center">
    {% include 'Admin/_versions.html.twig' %}
  </div>
</div>
{% endblock %}
```

Note the `row justify-content-center` kept on this page's content column only: `Admin/_versions.html.twig`'s root element is `<div class="list-group col-4" id="versions-list">` — a grid column class on its own root, needing a `.row` context to size/center against. Every other Admin content partial already opens its own internal `.row` and doesn't need this.

- [ ] **Step 9: Run the Admin integration suite**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Admin`
Expected: PASS, no changes needed — these tests assert on `#admin-actions-tab` ids/classes/hrefs/positions, all preserved by Steps 1-8.

- [ ] **Step 10: Visual verification (two Admin pages, two widths each)**

Same throwaway-Panther-test technique as Task 1 Step 6 (write under `tests/src/Browser/`, run, read screenshots, delete file and screenshots afterward — nothing committed). Cover `/fr/istration/update_data` (representative plain action page) and `/fr/istration/versions` (the special centered-content case):

```php
<?php

declare(strict_types=1);

namespace App\Tests\Browser;

use App\Tests\Utils\GetUserToken;
use Facebook\WebDriver\WebDriverDimension;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 */
#[CoversNothing]
final class _ManualAdminSidebarCheckTest extends AbstractBrowserTestCase
{
    #[Test]
    public function adminSidebarAtTwoWidths(): void
    {
        $client = self::getNewClient();
        $user = GetUserToken::getFakeUserToken();
        $user->addAdminRole();
        $this->loginUser($client, $user);

        foreach (['update_data', 'versions'] as $page) {
            $client->manage()->window()->setSize(new WebDriverDimension(1280, 900));
            $client->request('GET', '/fr/istration/'.$page);
            $client->takeScreenshot('/app/var/screenshots/manual_sidebar_admin_'.$page.'_wide.png');

            $client->manage()->window()->setSize(new WebDriverDimension(375, 900));
            $client->request('GET', '/fr/istration/'.$page);
            $client->takeScreenshot('/app/var/screenshots/manual_sidebar_admin_'.$page.'_narrow.png');
        }

        self::assertTrue(true);
    }
}
```

Run: `docker compose exec -e PANTHER_SELENIUM_HOST=http://chrome:4444/wd/hub -e PANTHER_BROWSER_NAME=chrome php php vendor/bin/phpunit --filter adminSidebarAtTwoWidths tests/src/Browser/_ManualAdminSidebarCheckTest.php`

Read all four screenshots. Confirm:
- At 1280px on `update_data`: the 7-item menu sits as a narrow column to the left, action cards to the right, no horizontal overflow.
- At 375px on `update_data`: the menu stacks full-width above the action cards.
- At 1280px on `versions`: the version list stays centered within its content column (not flush-left, not overflowing) — this is the one page with the `row justify-content-center` special case from Step 8.
- At 375px on `versions`: same stacking as `update_data`.

Delete the throwaway test file and all four screenshots afterward.

- [ ] **Step 11: Commit**

```bash
git add templates/Admin/_tabs.html.twig templates/Admin/update_data.html.twig templates/Admin/update_availabilities.html.twig templates/Admin/calculate_data.html.twig templates/Admin/invalidate_data.html.twig templates/Admin/trigger_pipeline.html.twig templates/Admin/reports.html.twig templates/Admin/versions.html.twig
git commit -m "feat(admin): replace section tabs with a sidebar menu"
```

---

### Task 3: Quality gates and final visual sweep

**Files:** none created; this task only runs checks and, if needed, adds a targeted CSS fix to `public/css/base.css` or `public/css/admin.css`.

**Interfaces:**
- Consumes: the finished markup from Tasks 1 and 2.
- Produces: nothing (terminal task).

- [ ] **Step 1: Run the full integration suite**

Run: `make tests-integration`
Expected: PASS, no regressions anywhere in the codebase — no other test file references `_tabs.html.twig`'s markup beyond what Tasks 1/2 already verified.

- [ ] **Step 2: Run code quality checks**

Run: `make code-quality`
Expected: phpcsfixer/phpmd/psalm/phpstan/deptrac all exit 0 (pure Twig/nothing-PHP change, so these should be unaffected, but confirm). Per Global Constraints, don't treat the `w3c` sub-check's result as meaningful signal either way — it doesn't validate these authenticated pages correctly (pre-existing, unrelated defect, already documented in the prior branch's final review).

- [ ] **Step 3: Manual visual sweep of the remaining pages**

Tasks 1 and 2 only screenshot-checked one Trainer page and two Admin pages. Using the same throwaway-Panther-test technique (write, run, read screenshots, delete test file and screenshots — nothing committed), spot-check the remaining pages at a single narrow width (375px) to confirm they all stack correctly, since they're the most likely to reveal a page-specific layout quirk the earlier checks didn't catch:

- `/fr/trainer/links`
- `/fr/trainer/personnal_data`
- `/fr/istration/update_availabilities`
- `/fr/istration/calculate_data`
- `/fr/istration/invalidate_data`
- `/fr/istration/trigger_pipeline`
- `/fr/istration/reports`

For each, confirm the sidebar stacks above the content with no horizontal overflow and no visually broken card grid. If any page shows a real layout problem, add the minimal CSS fix to `public/css/base.css` (Trainer pages) or `public/css/admin.css` (Admin pages) — do not add CSS speculatively for pages that already look correct.

- [ ] **Step 4: Commit (only if Step 3 required a CSS change)**

```bash
git add public/css/base.css public/css/admin.css
git commit -m "style(sidebar): fix layout issue found in visual sweep"
```
