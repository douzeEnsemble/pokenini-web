# Dex-list Progress Bar Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the plain pill badge on album and election dex-list cards with the segmented progress bar that already exists on the single-dex report page (`Album/_report.html.twig`), sharing the same Twig markup instead of duplicating it.

**Architecture:** Extract the existing per-catch-state progress bar into a reusable Twig macro (`catchStateBar`) plus a reusable CSS partial, used unchanged by the single-dex page and newly by the album dex-list cards. Add a second, simpler macro (`twoToneBar`) for election dex-list cards, since `ElectionReport` has no per-catch-state color data.

**Tech Stack:** Symfony 8 / Twig, PHPUnit integration tests against Moco HTTP fixtures.

## Global Constraints

- Docker-only toolchain: every command below runs via `docker compose exec php ...` from `/home/renaud/projects/pokenini-web` (no host PHP/Composer).
- The single-dex report page (`/album/{dexSlug}`) must render byte-for-byte the same progress bar markup after the refactor — this is a pure extraction, not a redesign.
- `declare(strict_types=1)` stays at the top of any modified PHP file (already present).
- Do not modify translation files — both new macros reuse existing translation keys (`election_dex.dex.report_badge_suffixe`) or use none.
- Run the specified PHPUnit file(s) after each task and confirm PASS before moving to the next task.

---

### Task 1: Extract the catch-state progress bar into a shared macro + CSS partial

**Files:**
- Create: `templates/common/_progress_bar_macros.html.twig`
- Create: `templates/common/_catch_state_progress_bar_styles.html.twig`
- Modify: `templates/Album/_report.html.twig:1-26`
- Modify: `templates/Album/index.html.twig:13-38`
- Test: `tests/src/Integration/Controller/Album/Display/CommonTest.php` (existing, unmodified — used as a regression check)

**Interfaces:**
- Produces: Twig macro `catchStateBar(report, locale)` in `templates/common/_progress_bar_macros.html.twig`, callable via `{% import "common/_progress_bar_macros.html.twig" as progressBar %}` then `{{ progressBar.catchStateBar(someReport, locale) }}`. `report` must expose `.detail` (iterable of objects with `.slug`, `.frenchName`, `.name`, `.count`), `.total`, `.totalUncaught` — i.e. an `App\ResponseObject\Album\Report`.
- Produces: Twig include `common/_catch_state_progress_bar_styles.html.twig`, which reads a `catchStates` variable (iterable of objects with `.slug`, `.color`) from the including template's context and emits CSS rules — must be included inside a `<style>...</style>` block by the caller.

- [ ] **Step 1: Create the shared macro file**

Create `templates/common/_progress_bar_macros.html.twig`:

```twig
{% macro catchStateBar(report, locale) %}
<div class="progress mb-2">
    {% for line in report.detail %}
        {% set count = line.slug is same as('no') ? report.totalUncaught : line.count %}
        {% set completion = report.total ? (100 * count / report.total)|round(2) : 0 %}
        {% set label = (locale is same as('fr') ? line.frenchName : line.name) ~ ': ' ~ completion ~ '%' %}
        <div class="progress-bar catch-state-{{ line.slug }}"
            role="progressbar"
            aria-label="{{ label }}"
            style="width: {{ completion }}%;"
            aria-valuenow="{{ completion }}"
            aria-valuemin="0"
            aria-valuemax="100"
            title="{{ label }}"
            data-bs-toggle="tooltip"
            data-bs-custom-class="tooltip-{{ line.slug }}"
        >
            {{ line.slug is same as('no') or line.slug is same as('yes') ? completion~'%' : '' }}
        </div>
    {% endfor %}
</div>
{% endmacro %}
```

- [ ] **Step 2: Create the shared CSS partial**

Create `templates/common/_catch_state_progress_bar_styles.html.twig`:

```twig
{% for catchState in catchStates %}
.progress-bar.catch-state-{{ catchState.slug }} {
    background-color: {{ catchState.color }};
}
.tooltip-{{ catchState.slug }} {
    --bs-tooltip-bg: {{ catchState.color }};
}
{% endfor %}
```

- [ ] **Step 3: Rewire `Album/index.html.twig` to include the shared CSS partial**

In `templates/Album/index.html.twig`, the `stylesheets` block currently has (lines 13-38):

```twig
    <style>
    {% for catchState in catchStates %}
    .album-case.catch-state-{{ catchState.slug }} .album-case-catch-state {
        background-color: {{ catchState.color }};
    }
    .album-case.catch-state-{{ catchState.slug }} .album-case-action {
        background-color: {{ catchState.color }};
    }
    tr.catch-state-{{ catchState.slug }} td,
    tr.catch-state-{{ catchState.slug }} th {
        background-color: {{ catchState.color }};
    }
    .progress-bar.catch-state-{{ catchState.slug }} {
        background-color: {{ catchState.color }};
    }
    .tooltip-{{ catchState.slug }} {
        --bs-tooltip-bg: {{ catchState.color }};
    }
    {% endfor %}

    {% for type in types %}
    .pokemon-type-{{ type.slug }} {
        background-color: {{ type.color }};
    }
    {% endfor %}
    </style>
```

Replace it with (removes the `.progress-bar`/`.tooltip-` rules from this loop, adds the shared include):

```twig
    <style>
    {% for catchState in catchStates %}
    .album-case.catch-state-{{ catchState.slug }} .album-case-catch-state {
        background-color: {{ catchState.color }};
    }
    .album-case.catch-state-{{ catchState.slug }} .album-case-action {
        background-color: {{ catchState.color }};
    }
    tr.catch-state-{{ catchState.slug }} td,
    tr.catch-state-{{ catchState.slug }} th {
        background-color: {{ catchState.color }};
    }
    {% endfor %}

    {% include 'common/_catch_state_progress_bar_styles.html.twig' %}

    {% for type in types %}
    .pokemon-type-{{ type.slug }} {
        background-color: {{ type.color }};
    }
    {% endfor %}
    </style>
```

- [ ] **Step 4: Rewire `Album/_report.html.twig` to call the shared macro**

Replace the full content of `templates/Album/_report.html.twig` (currently lines 1-26 hold the bar, lines 27+ hold the table — keep the table as-is) so the file starts with:

```twig
{% import "common/_progress_bar_macros.html.twig" as progressBar %}

<div class="report-container">
    <h2 id="stats">
        {{ 'title.report'|trans }}
    </h2>

    {% set progressBarReport = filteredReport.detail is not empty ? filteredReport : report %}
    {{ progressBar.catchStateBar(progressBarReport, locale) }}

    <table class="table table-hover table-bordered" id="report">
```

Everything from the existing `<tbody>` line onward (currently starting at line 28) stays exactly as it is today — only the `<div class="progress mb-2">...</div>` block (lines 6-26 in the original file) is deleted and replaced by the two lines above (`{% set progressBarReport = ... %}` and the macro call).

- [ ] **Step 5: Run the regression test**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Album/Display/CommonTest.php`
Expected: PASS (all methods, including `testListRead` which calls `assertStatistics()` — this asserts the exact same `.progress`/`.progress-bar` markup and percentages as before the refactor).

- [ ] **Step 6: Commit**

```bash
git add templates/common/_progress_bar_macros.html.twig templates/common/_catch_state_progress_bar_styles.html.twig templates/Album/_report.html.twig templates/Album/index.html.twig
git commit -m "refactor: extract catch-state progress bar into a shared macro"
```

---

### Task 2: Show the catch-state progress bar on album dex-list cards

**Files:**
- Modify: `src/Controller/AlbumDexListController.php`
- Modify: `templates/AlbumDexList/_macro.html.twig` (macro `item()`, lines 52-57)
- Modify: `templates/AlbumDexList/index.html.twig` (`stylesheets` block)
- Modify: `tests/resources/moco/Back/responses/dex/trainer.json` (the `"home"` entry)
- Modify: `tests/src/Integration/Controller/Album/Dex/AlbumDexListTest.php` (`testAlbumDexList`)

**Interfaces:**
- Consumes: `progressBar.catchStateBar(report, locale)` macro from Task 1 (`templates/common/_progress_bar_macros.html.twig`); `common/_catch_state_progress_bar_styles.html.twig` include from Task 1; `App\Service\GetLabelsService::getCatchStates(): array<int, App\ResponseObject\Label\CatchState>` (existing service, `src/Service/GetLabelsService.php`, already used the same way in `src/Controller/AlbumIndexController.php:60,75`).
- Produces: `catchStates` template variable on `AlbumDexList/index.html.twig`.

- [ ] **Step 1: Inject `GetLabelsService` into the controller**

Replace the full content of `src/Controller/AlbumDexListController.php` with:

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\Back\GetAlbumDexListService;
use App\Service\GetLabelsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/album')]
final class AlbumDexListController extends AbstractController
{
    public function __construct(
        private readonly GetLabelsService $getLabelsService,
    ) {}

    #[Route(
        '/dex',
        methods: ['GET']
    )]
    public function index(
        GetAlbumDexListService $service,
        Request $request,
    ): Response {
        $requestedTrainerId = $request->query->getAlnum('t', '');

        $dex = $service->get($requestedTrainerId);

        return $this->render(
            'AlbumDexList/index.html.twig',
            [
                'dex' => $dex,
                'catchStates' => $this->getLabelsService->getCatchStates(),
            ]
        );
    }
}
```

- [ ] **Step 2: Include the shared CSS partial on the dex-list page**

In `templates/AlbumDexList/index.html.twig`, replace the `stylesheets` block:

```twig
{% block stylesheets %}
  {{ parent() }}

  <link rel="stylesheet" href="{{ asset('css/dex-list.css') }}">
{% endblock stylesheets %}
```

with:

```twig
{% block stylesheets %}
  {{ parent() }}

  <link rel="stylesheet" href="{{ asset('css/dex-list.css') }}">

  <style>
  {% include 'common/_catch_state_progress_bar_styles.html.twig' %}
  </style>
{% endblock stylesheets %}
```

- [ ] **Step 3: Replace the pill badge in the `item()` macro**

In `templates/AlbumDexList/_macro.html.twig`, add the macro import as the first line of the file:

```twig
{% import "common/_progress_bar_macros.html.twig" as progressBar %}
```

Then, inside macro `item()`, replace:

```twig
      {% if dex.report is not null %}
      <span class="badge rounded-pill bg-primary mb-3">
        {{ dex.report.totalCaught|number_format(0, '.', ' ') }} / {{ dex.report.total|number_format(0, '.', ' ') }}
        {{ 'album_dex_list.dex.report_badge_suffixe'|trans }}
      </span>
      {% endif %}
```

with:

```twig
      {% if dex.report is not null %}
      {{ progressBar.catchStateBar(dex.report, locale) }}
      {% endif %}
```

- [ ] **Step 4: Add report data to a Moco fixture entry**

In `tests/resources/moco/Back/responses/dex/trainer.json`, find the `"home"` entry (currently):

```json
  {
    "dex": { "slug": "home" },
    "settings": { "name": "Home", "french_name": "Home", "slug": "home", "display_template": "box" },
    "flags": { "is_shiny": false, "is_private": false, "is_on_home": true, "is_display_form": true, "is_released": true, "is_premium": false, "is_custom": false }
  },
```

Replace it with (adds a `report` key):

```json
  {
    "dex": { "slug": "home" },
    "settings": { "name": "Home", "french_name": "Home", "slug": "home", "display_template": "box" },
    "flags": { "is_shiny": false, "is_private": false, "is_on_home": true, "is_display_form": true, "is_released": true, "is_premium": false, "is_custom": false },
    "report": {
      "total": 151,
      "total_caught": 88,
      "total_uncaught": 63,
      "detail": [
        { "catch_state": { "name": "Not caught", "french_name": "Pas attrapé", "slug": "no", "color": "#dc3545" }, "count": 63 },
        { "catch_state": { "name": "Caught", "french_name": "Attrapé", "slug": "yes", "color": "#198754" }, "count": 88 }
      ]
    }
  },
```

(`"home"` is deliberately chosen: it is the 2nd card — `.dex-item` index 1 — in `testAlbumDexList`'s rendered list, and no existing test asserts an exact `->text()` equality on that card, unlike the 1st (`swordshield`) and 3rd (`homeshiny`) cards — so this addition cannot break any existing assertion.)

- [ ] **Step 5: Add assertions to `AlbumDexListTest::testAlbumDexList`**

In `tests/src/Integration/Controller/Album/Dex/AlbumDexListTest.php`, in `testAlbumDexList()`, after the existing block:

```php
        $secondAlbum = $crawler->filter('.dex-item')->eq(2);
        $this->assertEquals('Home Chromatique', $secondAlbum->text());
        $this->assertEquals('/fr/album/homeshiny', $secondAlbum->filter('a')->attr('href'));
        $this->assertEquals('https://icon.pokenini.fr/banner/homeshiny.png', $secondAlbum->filter('img')->attr('src'));
```

add:

```php

        $this->assertCountFilter($crawler, 1, '.dex-item .progress');
        $this->assertCountFilter($crawler, 2, '.dex-item .progress-bar');

        $homeAlbum = $crawler->filter('.dex-item')->eq(1);
        $this->assertEquals('41.72%', $homeAlbum->filter('.progress-bar.catch-state-no')->text());
        $this->assertEquals('58.28%', $homeAlbum->filter('.progress-bar.catch-state-yes')->text());
```

- [ ] **Step 6: Run the test**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Album/Dex/AlbumDexListTest.php`
Expected: PASS (all 7 methods).

- [ ] **Step 7: Commit**

```bash
git add src/Controller/AlbumDexListController.php templates/AlbumDexList/_macro.html.twig templates/AlbumDexList/index.html.twig tests/resources/moco/Back/responses/dex/trainer.json tests/src/Integration/Controller/Album/Dex/AlbumDexListTest.php
git commit -m "feat: show the catch-state progress bar on album dex-list cards"
```

---

### Task 3: Show a two-tone progress bar on election dex-list cards

**Files:**
- Modify: `templates/common/_progress_bar_macros.html.twig` (add macro `twoToneBar`)
- Modify: `templates/AlbumDexList/_macro.html.twig` (macro `itemElection()`, lines 121-126)
- Modify: `tests/src/Integration/Controller/Election/ElectionDexListTest.php`

**Interfaces:**
- Consumes: nothing new from other tasks besides the existing `progressBar` import added in Task 2 to `templates/AlbumDexList/_macro.html.twig`.
- Produces: Twig macro `twoToneBar(atMaxCount, dexTotalCount)` in `templates/common/_progress_bar_macros.html.twig`, taking two integers.

- [ ] **Step 1: Add the `twoToneBar` macro**

Append to `templates/common/_progress_bar_macros.html.twig`:

```twig

{% macro twoToneBar(atMaxCount, dexTotalCount) %}
{% set completionAtMax = dexTotalCount ? (100 * atMaxCount / dexTotalCount)|round(2) : 0 %}
{% set completionUnderMax = dexTotalCount ? (100 - completionAtMax)|round(2) : 0 %}
{% set tooltip = atMaxCount|number_format(0, '.', ' ') ~ ' / ' ~ dexTotalCount|number_format(0, '.', ' ') ~ ' ' ~ 'election_dex.dex.report_badge_suffixe'|trans %}
<div class="progress mb-3"
    role="progressbar"
    aria-label="{{ tooltip }}"
    aria-valuenow="{{ completionAtMax }}"
    aria-valuemin="0"
    aria-valuemax="100"
    data-bs-toggle="tooltip"
    data-bs-title="{{ tooltip }}"
>
    <div class="progress-bar bg-success" style="width: {{ completionAtMax }}%;"></div>
    <div class="progress-bar bg-secondary" style="width: {{ completionUnderMax }}%;"></div>
</div>
{% endmacro %}
```

- [ ] **Step 2: Replace the pill badge in the `itemElection()` macro**

In `templates/AlbumDexList/_macro.html.twig`, inside macro `itemElection()`, replace:

```twig
      {% if dex.report is not null %}
      <span class="badge rounded-pill bg-primary mb-3">
        {{ dex.report.metrics.completion.atMaxCount|number_format(0, '.', ' ') }} / {{ dex.report.metrics.dexTotalCount|number_format(0, '.', ' ') }}
        {{ 'election_dex.dex.report_badge_suffixe'|trans }}
      </span>
      {% endif %}
```

with:

```twig
      {% if dex.report is not null %}
      {{ progressBar.twoToneBar(dex.report.metrics.completion.atMaxCount, dex.report.metrics.dexTotalCount) }}
      {% endif %}
```

(The `dex.dexTotalCount` badge just above this block, and the description paragraph below it, are untouched.)

- [ ] **Step 3: Update `ElectionDexListTest::testIndex`**

In `tests/src/Integration/Controller/Election/ElectionDexListTest.php`, replace:

```php
        $this->assertCountFilter($crawler, 21, '.dex-item');
        $this->assertCountFilter($crawler, 21, '.dex-item .card-title');
        $this->assertCountFilter($crawler, 21, '.dex-item .card-title a');
        $this->assertCountFilter($crawler, 45, '.dex-item .badge');
        $this->assertCountFilter($crawler, 21, '.dex-item p.small');

        $this->assertSame('71 Pokémons', $crawler->filter('.dex-item .badge')->eq(0)->text());
        $this->assertSame('0 / 71 notées', $crawler->filter('.dex-item .badge')->eq(1)->text());

        $this->assertSame('/fr/election/redgreenblueyellow', $crawler->filter('.dex-item .card-title a')->eq(0)->attr('href'));
        $this->assertSame('/fr/election/rubysapphireemerald', $crawler->filter('.dex-item .card-title a')->eq(2)->attr('href'));
```

with:

```php
        $this->assertCountFilter($crawler, 21, '.dex-item');
        $this->assertCountFilter($crawler, 21, '.dex-item .card-title');
        $this->assertCountFilter($crawler, 21, '.dex-item .card-title a');
        $this->assertCountFilter($crawler, 24, '.dex-item .badge');
        $this->assertCountFilter($crawler, 21, '.dex-item .progress');
        $this->assertCountFilter($crawler, 42, '.dex-item .progress-bar');
        $this->assertCountFilter($crawler, 21, '.dex-item p.small');

        $this->assertSame('71 Pokémons', $crawler->filter('.dex-item .badge')->eq(0)->text());

        $firstDex = $crawler->filter('.dex-item')->eq(0);
        $this->assertStringContainsString('width: 0%', (string) $firstDex->filter('.progress-bar.bg-success')->attr('style'));
        $this->assertStringContainsString('width: 100%', (string) $firstDex->filter('.progress-bar.bg-secondary')->attr('style'));
        $this->assertSame('0 / 71 notées', (string) $firstDex->filter('.progress')->attr('data-bs-title'));

        $this->assertSame('/fr/election/redgreenblueyellow', $crawler->filter('.dex-item .card-title a')->eq(0)->attr('href'));
        $this->assertSame('/fr/election/rubysapphireemerald', $crawler->filter('.dex-item .card-title a')->eq(2)->attr('href'));
```

(Badge count drops from 45 to 24 because all 21 cards had a report badge that's now a progress bar instead — 45 − 21 = 24. Progress bar count is 21 outer `.progress` × 2 inner `.progress-bar` segments = 42. The first card, `redgreenblueyellow`, has `completion.at_max_count: 0` and `metrics.dex_total_count: 71` in `tests/resources/moco/Back/responses/election/election_dex_list_admin.json`, hence 0%/100% segment widths.)

- [ ] **Step 4: Run the test**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Election/ElectionDexListTest.php`
Expected: PASS.

- [ ] **Step 5: Full regression pass**

Run: `docker compose exec php php vendor/bin/phpunit --group api-mocked-testing`
Expected: PASS (confirms no other integration test — `HomeTest`, `TrainerPageTest`, `TrainerPageFiltersTest` — broke from the shared-fixture or shared-partial changes).

Run: `make w3c`
Expected: no new markup errors on `/album/dex`, `/election/dex`, or `/album/{dexSlug}`.

- [ ] **Step 6: Commit**

```bash
git add templates/common/_progress_bar_macros.html.twig templates/AlbumDexList/_macro.html.twig tests/src/Integration/Controller/Election/ElectionDexListTest.php
git commit -m "feat: show a two-tone progress bar on election dex-list cards"
```
