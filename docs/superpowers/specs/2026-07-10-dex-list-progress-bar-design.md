# Dex-list progress bar design

## Problem

The album and election dex-list pages (`/album/dex`, `/election/dex`) show completion
as a plain Bootstrap pill badge on each card (`X / Y caught`). The single-dex report
page (`Album/index.html.twig` via `Album/_report.html.twig`) already has a richer,
multi-segment, per-catch-state-colored progress bar with hover tooltips. The user
wants the dex-list cards to use that same progress bar instead of the pill badge.

`ElectionReport` has no per-catch-state breakdown (`ElectionReportMetrics` only
exposes `completion.atMaxCount` / `completion.underMaxCount` / `dexTotalCount`), so
the election cards need a simpler two-tone variant rather than the exact same
per-catch-state bar.

## Scope

- Regular album dex-list cards (`AlbumDexList/_macro.html.twig`, macro `item()`):
  replace the pill badge with the full multi-segment catch-state progress bar,
  matching `Album/_report.html.twig` exactly (same colors, same tooltips).
- Election dex-list cards (`AlbumDexList/_macro.html.twig`, macro `itemElection()`):
  replace the report pill badge with a two-segment bar (green "at max" / gray
  "under max"). The separate `dexTotalCount` badge on election cards is untouched.
- The single-dex report page's visual output must not change — the bar is
  extracted, not altered.

## Components

### 1. `templates/common/_progress_bar_macros.html.twig` (new)

Two macros:

- `catchStateBar(report, locale)` — renders the `.progress` wrapper with one
  `.progress-bar.catch-state-{slug}` div per `report.detail` line, exactly the
  markup currently inlined in `Album/_report.html.twig` lines 6-26 (width/aria
  attributes, tooltip `data-bs-toggle`/`data-bs-custom-class`, "yes"/"no" percentage
  text). Takes the already-resolved `report` (caller decides filtered vs.
  unfiltered) and `locale` for label translation.
- `twoToneBar(atMaxCount, dexTotalCount)` — renders a `.progress` wrapper with two
  fixed-color segments: `.progress-bar.bg-success` sized to
  `atMaxCount / dexTotalCount` and `.progress-bar.bg-secondary` sized to the
  remainder. A single tooltip on the outer `.progress` shows the
  `atMaxCount / dexTotalCount` count (same information the old pill badge showed).
  No per-catch-state coloring needed since the data has none.

### 2. `templates/common/_catch_state_progress_bar_styles.html.twig` (new)

Extracts the `.progress-bar.catch-state-{{ slug }} { background-color }` and
`.tooltip-{{ slug }} { --bs-tooltip-bg }` CSS rules currently inlined in
`Album/index.html.twig` (lines 25-30), parameterized by a `catchStates` variable.
Included via `{% include %}` inside a `<style>` block on any page that renders
`catchStateBar()`. `Album/index.html.twig` switches its inline rules to this
include (other album-case/table rules in that file's `<style>` block are
unrelated and stay inline). `AlbumDexList/index.html.twig` adds it.

### 3. `AlbumDexListController::index()`

Inject `GetLabelsService` (already used the same way in `AlbumIndexController`,
backed by the Redis-cached `cache.labels` pool — cheap to call again) and add
`'catchStates' => $labels->getCatchStates()` to the render array.

### 4. `AlbumDexList/_macro.html.twig`

- `item()`: replace the `dex.report is not null` badge block (lines 52-57) with
  `{{ progressBar.catchStateBar(dex.report, locale) }}` under the same guard.
- `itemElection()`: replace the `dex.report is not null` badge block (lines
  121-126) with
  `{{ progressBar.twoToneBar(dex.report.metrics.completion.atMaxCount, dex.report.metrics.dexTotalCount) }}`
  under the same guard. The `dex.dexTotalCount` badge above it (lines 114-119)
  is untouched.
- Add `{% import "common/_progress_bar_macros.html.twig" as progressBar %}` at
  the top of the file.

### 5. `templates/Album/_report.html.twig`

Replace the inline bar block (lines 6-26) with a call to
`progressBar.catchStateBar(progressBarReport, locale)`, keeping the existing
`{% set progressBarReport = filteredReport.detail is not empty ? filteredReport : report %}`
line in this file since the filtered/unfiltered choice is specific to the
single-dex page (the dex-list page has no filters).

### 6. `templates/AlbumDexList/index.html.twig`

Add the macro import and include the new CSS partial in the `stylesheets` block,
passing the `catchStates` variable from the controller.

## Data flow

- Album cards: `dex.report` (from `GetAlbumDexListService`, already fetched
  today for the pill badge) → `Report::getDetail()` (per-catch-state counts +
  colors) → `catchStateBar()` renders segments.
- Election cards: `dex.report.metrics.completion` (`atMaxCount`/`underMaxCount`)
  + `dex.report.metrics.dexTotalCount` → `twoToneBar()` renders two segments.
- Catch-state colors: `GetLabelsService::getCatchStates()` (cached) →
  `_catch_state_progress_bar_styles.html.twig` → CSS rules picked up by the
  `catch-state-{slug}` classes emitted by `catchStateBar()`.

## Testing / verification

- Update assertions in the integration tests that currently check for the pill
  badge markup or `report_badge_suffixe` translation keys, since the DOM changes
  to `.progress`/`.progress-bar`: `AlbumDexListTest`, `ElectionDexListTest`,
  `HomeTest`, `TrainerPageTest`, `TrainerPageFiltersTest`.
- No new PHP business logic beyond the one-line controller addition — no new
  unit tests required.
- Run `make tests-integration` and `make quality` (the W3C validator in
  `code-quality` will catch any invalid markup from the extracted partials).
- Manually check both `/album/dex` and `/election/dex` render correctly and that
  `/album/{dexSlug}` (single-dex page) is visually unchanged.
