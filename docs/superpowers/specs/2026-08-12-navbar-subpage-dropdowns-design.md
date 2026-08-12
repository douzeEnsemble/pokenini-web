# Direct links + dropdown for Dresseurs/Admin navbar items

## Context

`templates/_nav.html.twig` renders a `fixed-bottom` navbar. The "Dresseurs"
(`trainer-link`) and "Admin" (`admin-link`) items are each a single
`<a class="nav-link">` pointing at the first sub-page of their section:

- Trainer (`is_granted("ROLE_TRAINER")`): links to `app_trainerindex_index`.
  The section actually has three pages, already tabbed in-page by
  `templates/Trainer/_tabs.html.twig`: `app_trainerindex_index` (dex
  customization), `app_trainerlinks_index`, `app_trainerpersonnaldata_index`.
- Admin (`is_granted("ROLE_ADMIN")`): links to `app_admin_update_data`. The
  section has seven pages, already tabbed in-page by
  `templates/Admin/_tabs.html.twig`: `app_admin_update_data`,
  `app_admin_update_availabilities`, `app_admin_calculate_data`,
  `app_admin_invalidate_data`, `app_admin_trigger_pipeline`,
  `app_admin_reports`, `app_admin_versions`.

Both `_tabs.html.twig` partials already define a local `{key, label, route}`
list to render their in-page tab bar; `_nav.html.twig` currently only knows
about the first route of each list, hardcoded, plus a separate flat list of
routes to compute the `onTrainer`/`onAdmin` "is this section active" flag.

Reaching any sub-page other than the first currently requires first landing
on the section's default page, then clicking its in-page tab. The goal is
to let the navbar jump straight to any sub-page.

## Goal

From the bottom navbar, without leaving it: keep the current one-click
behavior to the default (first) sub-page, and add a dropdown to jump
directly to any other sub-page of that section — for both Dresseurs and
Admin.

## Design

### Markup: split dropdown per section, `dropup` because the navbar is fixed-bottom

Bootstrap 5.3.8's JS bundle is already loaded globally (`base.html.twig`),
so the native dropdown component (`data-bs-toggle="dropdown"`) needs no
custom JS.

For each of the two sections, `_nav.html.twig` changes the `<li class="nav-item ...">`
into a split-button-style dropdown, adapting Bootstrap's split-button
pattern (normally `btn-group` + `btn`) to a nav item:

- `<li class="nav-item trainer-link dropdown dropup">` (`admin-link` for
  the other one). `dropdown` establishes the positioning context; `dropup`
  flips the menu to open upward (there is no room below a fixed-bottom
  navbar) — pure CSS, Bootstrap reads the class at dropdown-open time.
- Inside, a `<div class="d-flex align-items-center">` wrapping two links:
  - `<a class="nav-link ...">` — unchanged `href` to the first route
    (`app_trainerindex_index` / `app_admin_update_data`), unchanged icon
    and label, unchanged `active`/`aria-current` logic. This preserves the
    current one-click behavior exactly.
  - `<a class="nav-link dropdown-toggle dropdown-toggle-split" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">`
    — the only new clickable element, showing just Bootstrap's default
    caret (flipped upward by `.dropup`) plus a `visually-hidden` label for
    screen readers (new translation key `nav.toggle_submenu`, fr/en).
- `<ul class="dropdown-menu">` listing every page of the section (including
  the first — consistent with how `_tabs.html.twig` always shows all tabs,
  and lets the current page be highlighted even when it's the first one):
  one `<li><a class="dropdown-item{{ active ? ' active' : '' }}" href="{{ path(page.route) }}">{{ page.label|trans }}</a></li>`
  per entry.

### Data: local list per section, matching existing duplication style

`_nav.html.twig` gets its own `trainerPages` / `adminPages` Twig `{% set %}`
lists (same `{key, label, route}` shape and same translation keys already
used by the two `_tabs.html.twig` partials — no new translation strings
for labels). This duplicates the list that already exists in each
`_tabs.html.twig`; the project has no shared-data pattern between these
partials today (each `_tabs.html.twig` already hardcodes its own list
independently), so this follows existing convention rather than
introducing a new abstraction. If a third consumer of these lists shows up
later, that's the point to extract a shared source (e.g. a Twig
extension), not before.

The existing `onTrainer` / `onAdmin` flags (used for the main link's
`active`/`aria-current`) are simplified to `currentRoute in trainerPages|map(p => p.route)`
(resp. `adminPages`), instead of a separately hand-maintained flat list of
routes — same result, one fewer place to keep in sync when a section
grows.

### No changes to

- Any controller or route.
- `Trainer/_tabs.html.twig` / `Admin/_tabs.html.twig` (in-page tabs stay
  exactly as they are).
- `TestNavTrait` — no test asserts an exact count of `<a>` elements inside
  `.trainer-link`/`.admin-link`, only presence of the `li` and a `href`
  substring on the first matched `a`, both of which keep working since the
  main link stays the first `<a>` in the item.

### CSS

`.dropdown-toggle-split` is Bootstrap's generic split-button class (not
scoped to `.btn`), so it applies its padding tweaks to the `nav-link`
caret without extra CSS. No new rules expected in `base.css`; only add
something there if visual QA in a real browser shows misalignment.

## Tests

- Extend integration tests that already render the navbar (wherever
  `assertConnectedNavBar`/`assertTrainerAlbumNavBar`/`assertAdminAlbumNavBar`
  from `TestNavTrait` are used) or add a small dedicated nav test: assert
  the dropdown-menu for each section lists all of its routes, and that the
  entry matching the current route carries `.active`.
- Browser test (optional, `tests/src/Browser/`): click the caret, assert
  the menu becomes visible and a sub-page link navigates correctly. Given
  the existing browser-test suite doesn't cover the navbar today, add this
  only if it fits naturally; not a hard requirement of this design.
- `make tests-unit`/`make tests-integration` must stay green; no unit-level
  logic is introduced (pure Twig template change), so no new unit tests.

## Out of scope

- No visual redesign of the navbar beyond this dropdown addition (icons,
  colors, spacing stay as-is besides the new caret).
- No change to which pages exist in each section or their order.
- No "remember last visited sub-page" behavior — the main link always goes
  to the first page, as today.
- Mobile (`navbar-toggler` collapsed) view: no special-casing: Bootstrap's
  dropdown component works the same inside a collapsed `navbar-collapse`,
  so no separate design needed for small screens.
