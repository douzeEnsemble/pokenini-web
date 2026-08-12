# Split the Trainer page into three pages, move logout to the nav bar

## Context

The Trainer space (`TrainerIndexController::index()`, route
`app_trainerindex_index`, `/trainer`) currently renders a single page with:

1. Album customization (`Trainer/Section/_dex.html.twig` +
    `_dex_filters.html.twig`) — always visible above the tabs, driven by
    `GetTrainerDexListService` + `DexFiltersRequest`.
2. A Bootstrap JS tab-switcher (`Trainer/_section.html.twig`,
    `trainer_tabs.js`) with three panes, none of them separate routes:
    - `personnal_data` (`_personnal_data.html.twig`) — id/provider table.
    - `links` (`_links.html.twig`) — the trainer's cross-dex link tree, from
      `GetTrainerDexLinksTreeService`.
    - `logout` (`_logout.html.twig`) — a single link to `app_connect_logout`.

`Album/_offcanvas.html.twig` deep-links into the `links` pane via
`app_trainerindex_index` + `_fragment: 'section-links'`, activated on load
by `trainer_tabs.js`'s `watchTrainerTabs()` (reads `location.hash`, shows
the matching Bootstrap tab).

The persistent bottom nav (`_nav.html.twig`, `navbar fixed-bottom
navbar-expand-sm`) has no logout entry today; logging out is only reachable
from inside the Trainer page (or from `OuterRoom/index.html.twig`'s own
separate inline link, out of scope here).

## Goal

Replace the in-page tab-switcher with three separate pages — album
customization, album links, personal data — following the same
"page-level `nav-tabs` as plain links" pattern already used for the Admin
Actions/Reports split (`2026-07-07-split-admin-actions-reports-design.md`).
Move the logout link out of the Trainer page entirely, into the bottom nav
bar, visible for any signed-in user.

## Design

### Routing & controllers

One controller per page, matching the existing "one controller per
feature page" convention:

- `TrainerIndexController` (existing, unchanged route/name
  `app_trainerindex_index`, path `/trainer`) becomes the **album
  customization** page. Drops the `GetTrainerDexLinksTreeService`
  dependency and the `linksTree` template variable — nothing else in its
  logic changes (dex list fetch + filtering stays as-is).
- New `TrainerLinksController`, class-level `#[Route('/trainer')]`,
  method `index()` with `#[Route('/links', methods: ['GET'])]` →
  auto-named `app_trainerlinks_index`, path `/trainer/links`. Injects
  `GetTrainerDexLinksTreeService` (moved from `TrainerIndexController`),
  renders `linksTree` only.
- New `TrainerPersonnalDataController` (spelling matches the existing
  `personnal_data` translation keys / partial name throughout the
  codebase), class-level `#[Route('/trainer')]`, method `index()` with
  `#[Route('/personnal_data', methods: ['GET'])]` → auto-named
  `app_trainerpersonnaldata_index`, path `/trainer/personnal_data`. No
  service dependency — the partial only reads `app.user.id` /
  `app.user.providerName`.
- All three keep `#[IsGranted('ROLE_TRAINER')]`. No `security.yaml`
  change needed: the existing `^/(en|fr)/trainer` prefix rule already
  covers both new paths.

### Shared tab navigation

New `Trainer/_tabs.html.twig`, modeled on `Admin/_tabs.html.twig`: a
`<ul class="nav nav-tabs nav-fill mb-4">` with one real `<a href="{{
path(...) }}">` per page (customization / links / personnal_data), the
current route marked `active`. Labels reuse the existing section titles
as translation keys (`trainer.dex.title`, `trainer.links.title`,
`trainer.personnal_data.title`) — no new translation keys for labels,
same as the Admin tabs reusing `admin.actions.*.title`.

Included at the top of each of the three page templates, right after the
`<h1>`.

### Templates

- `Trainer/index.html.twig` (unchanged name/route): drops the
  `{{ include('Trainer/_section.html.twig') }}` line and the `linksTree`
  reference, adds `{{ include('Trainer/_tabs.html.twig') }}` above
  `_dex.html.twig`. Keeps the `trainer.welcome` intro paragraph, but its
  copy is corrected (see Translations below) since it currently mentions
  logging out, which no longer happens on this page.
- New `Trainer/links.html.twig`: extends `base.html.twig`, same
  `<title>`/`<h1>` (`title.trainer`) as today, `_tabs.html.twig` then
  `Trainer/Section/_links.html.twig` (reused as-is, unchanged).
- New `Trainer/personnal_data.html.twig`: same shell, `_tabs.html.twig`
  then `Trainer/Section/_personnal_data.html.twig` (reused as-is).
- Deleted: `Trainer/_section.html.twig` (tab-switcher shell) and
  `Trainer/Section/_logout.html.twig` (superseded by the nav-bar link).

### JS cleanup

- `public/js/trainer_tabs.js` is deleted (`watchTrainerTabs()`), along
  with its `<script>` include and call in `Trainer/index.html.twig`'s
  `foot_javascript` block — navigation between the three pages is now a
  real page load, no Bootstrap `Tab` JS or hash-activation needed.
- `trainer_dex.js` / `trainer_filters.js` stay, loaded only from
  `Trainer/index.html.twig` (they only apply to the customization page,
  which is already the only place their markup — `#dexFilters`,
  `.trainer-dex-item input[type=checkbox]` — exists).

### Logout in the bottom nav

- `_nav.html.twig`: remove nothing existing, add one new `<li
  class="nav-item logout-link ms-auto">` as the **last** item inside the
  existing `<ul class="navbar-nav">` (after the conditional admin-link
  block), wrapped in `{% if app.user %}`. Content: the same link
  currently in `_logout.html.twig` (`path('app_connect_logout')`,
  `'logout'|trans` — that top-level translation key is unchanged).
- `app.user` (not `is_granted('ROLE_TRAINER')`) is the gating condition,
  so the link also appears for signed-in-but-not-yet-trainer users on
  `OuterRoom/index.html.twig` (which already `{% use %}`s `_nav.html.twig`)
  — a harmless duplicate of that page's own inline logout link, left
  untouched since it's out of scope.
- `ms-auto` pushes the item to the right edge when the bar is expanded
  (≥576px, `navbar-expand-sm`). Below that breakpoint the whole
  `navbar-nav` list collapses into a normal vertical stack behind the
  hamburger toggle — `ms-auto` has no horizontal effect there, so logout
  just becomes the last stacked entry. Nothing is absolutely positioned,
  so it can't overlap or hide any other item at any width.

### Downstream fix

`Album/_offcanvas.html.twig`'s "see tree" link
(`album.offcanvas.links.see_tree`) currently points at
`path('app_trainerindex_index', { '_fragment': 'section-links' })`.
Changed to `path('app_trainerlinks_index')` — a direct link to the new
page, no fragment needed since there's no more hash-activated tab to
land on.

### Translations

- Remove `trainer.logout.title` (`fr`/`en`) — was only used as the old
  tab's label, now unused.
- Reword `trainer.welcome` (`fr`/`en`) to drop the "te déconnecter" /
  "log out" clause (no longer happens from this page) and mention the
  three pages instead of just customization + personal data:
  - fr: "Bonjour à toi jeune dresseur.\nCeci est ton espace.\n\nTu peux y
    personnaliser tes albums, consulter les liens entre tes dex et tes
    données personnelles.\nAmuse toi bien."
  - en: "Hello, young trainer.\nThis is your space.\n\nHere you can
    personalize your albums, and view the links between your dexes and
    your personal data.\nHave fun."
- No new translation keys.

## Tests

- `tests/src/Integration/Controller/Trainer/TrainerPageTest.php`: keep
  covering `TrainerIndexController` at `/fr/trainer`, but drop the
  `#section-logout` and (moved) links-tree assertions — it now only
  asserts the customization content (title/h1, filters, dex items). The
  three existing scenarios (trainer, collector, admin) stay.
- New `TrainerLinksPageTest.php` (`#[CoversClass(TrainerLinksController::class)]`):
  moves the links-tree assertions out of the old test, requests
  `/fr/trainer/links`.
- New `TrainerPersonnalDataPageTest.php`
  (`#[CoversClass(TrainerPersonnalDataController::class)]`): moves the
  id/provider table assertions out of the old test, requests
  `/fr/trainer/personnal_data`.
- `TrainerPageFiltersTest.php`: unaffected (already scoped to `/trainer`
  with query params).
- `TestNavTrait`: add `assertLogoutNavBar(Crawler $crawler): void`
  asserting exactly one `.navbar-nav .logout-link a[href*="/connect/logout"]`,
  used from the new/updated Trainer tests and from
  `OuterRoomTest`/`AlbumIndex`-style tests that already assert nav
  contents for a logged-in user.
- No test commands are run as part of this design; the user runs `make
  tests` themselves.

## Out of scope

- No change to `OuterRoom/index.html.twig`'s own inline logout link.
- No change to `TrainerUpsertController` / `TrainerDexLinkController`
  (the AJAX endpoints backing the customization checkboxes and the
  album-links CRUD used from `Album/_offcanvas.html.twig`).
- No change to `GetTrainerDexListService`, `GetTrainerDexLinksTreeService`
  internals, or the Back API.
- No change to `security.yaml` (existing `/trainer` prefix rule already
  covers the two new paths).
- No visual/CSS redesign beyond what Bootstrap's existing `nav-tabs` /
  `navbar-nav` classes already provide — no new custom CSS.
