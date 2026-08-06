# Tabbed sections on the Admin Actions page

## Context

`Admin/_actions.html.twig` (included from `actions.html.twig`, route
`app_admin_actions`) currently stacks five sections vertically, one after
another, each a `<div class="row px-4 py-5">` with its own `<h2>` and a set
of action items rendered via the `admin.action()` macro:

1. `update_data` — Mise à jour des données (labels, games_collections_and_dex,
  pokemons, regional_dex_numbers)
2. `update_availabilities` — Disponibilités (games_availabilities,
  games_shinies_availabilities, collections_availabilities)
3. `calculate_data` — Calculs (game_bundles_availabilities,
  game_bundles_shinies_availabilities, dex_availabilities,
  pokemon_availabilities)
4. `invalidate_data` — Invalidation (labels, catch_states, types, dex, albums)
5. `trigger_pipeline` — Déclenchement pipeline (update_images), followed by
  `Admin/_pipeline_status.html.twig` (image pipeline stage tracker)

The page is already the single-purpose Actions page (Reports lives on its
own route, `app_admin_reports`, with its own top-level tab bar rendered by
`Admin/_tabs.html.twig`). The five sections above make the page long; the
goal is to turn them into in-page tabs so only one section is visible at a
time.

Each action button submits a form to `AdminActionController` (update /
calculate / invalidate / trigger), which redirects back to
`app_admin_actions` with `_fragment => "{action}_{name}"` (e.g.
`#update_labels`), the `id` of the corresponding `.admin-item` div rendered
by the `action` macro (`_macros.html.twig:77`). This is how the page
scrolls back to the item that was just triggered after a POST, and how the
"refresh" link (`_macros.html.twig:142`) works for pending actions. This
behavior must keep working once items live inside hidden tab panes.

## Goal

Replace the five stacked sections in `_actions.html.twig` with Bootstrap 5
tabs (one tab per section), without breaking the existing "jump to and
highlight the triggered item" redirect behavior, and without touching the
Reports page.

## Design

### Markup: Bootstrap nav + tab-content

Bootstrap 5.3.8's JS bundle is already loaded globally in `base.html.twig`
(`bootstrap.bundle.min.js`), so the native tab component
(`data-bs-toggle="tab"`) can be used with no custom JS needed for switching
panes.

`_actions.html.twig` changes from five sequential `row px-4 py-5` blocks to:

- A `<ul class="nav nav-pills mb-4" role="tablist">` with one `<li>` per
  section, `nav-link` buttons carrying `data-bs-toggle="tab"`,
  `data-bs-target="#tab-pane-{section}"`, `role="tab"`. `nav-pills` is used
  (rather than `nav-tabs`) so these sub-tabs are visually distinct from the
  page-level `nav-tabs` already rendered by `Admin/_tabs.html.twig` above
  them (Actions vs Reports).
- Labels reuse the existing translation keys already used for each
  section's `<h2>` (`admin.actions.<section>.title`) — no new translation
  strings needed.
- A `<div class="tab-content">` with one `<div class="tab-pane fade" id="tab-pane-{section}" role="tabpanel">`
  per section, containing exactly what that section's `row px-4 py-5` div
  contains today (unchanged macro calls).
- The first pane (`update_data`) and its nav button get `show active` by
  default; this is overridden client-side when the URL hash points into a
  different section (see below).
- `Admin/_pipeline_status.html.twig` moves inside the `trigger_pipeline`
  pane, right after the `update_images` action item (unchanged include,
  just relocated).

No changes to `_macros.html.twig` — the `action` macro's markup and ids are
untouched.

### Deep-link on load: activate the right tab, then scroll

Bootstrap hides inactive `tab-pane`s via CSS (`display: none` unless
`show`), so a `#update_labels` URL fragment landing on an inactive pane
won't scroll into view or even be reachable by the browser's native anchor
scroll. A small function is added to `public/js/admin.js`:

```js
function activateTabForHash() {
  const hash = window.location.hash;
  if (!hash) return;
  const target = document.querySelector(hash);
  if (!target) return;
  const pane = target.closest('.tab-pane');
  if (!pane || pane.classList.contains('active')) return;
  const trigger = document.querySelector(`[data-bs-target="#${pane.id}"]`);
  if (!trigger) return;
  new bootstrap.Tab(trigger).show();
  target.scrollIntoView();
}
```

Called once from `actions.html.twig`'s `foot_javascript` block alongside
the existing `watchActionLogToggles()` / `watchForceConfirm()` calls, after
DOM is ready (same IIFE). This covers both cases that set the fragment:

- The controller redirect after a POST (`AdminActionController::execute()`,
  `_fragment => "{action}_{name}"`).
- The "refresh" link for a pending action (`_macros.html.twig:142`, same
  fragment pattern), which reloads the page with the hash already in the
  URL — Bootstrap's `Tab.show()` call still runs on that fresh load since
  it's driven by JS reading `location.hash`, not by relying on native
  anchor jump.

`bootstrap.Tab` firing `show()` on an already-active tab is a no-op guarded
by the `pane.classList.contains('active')` check above, so no flicker on
normal (non-fragment) loads.

### No changes to

- `AdminActionController` (fragment format `{action}_{name}` is already
  correct — no server-side awareness of tabs needed).
- `_macros.html.twig` (item ids, toggle/refresh links unchanged).
- `admin.css` (tabs use Bootstrap's default styling; no new custom classes
  beyond what `nav-pills`/`tab-content`/`tab-pane` already provide).
- The Reports page / `Admin/_tabs.html.twig` (page-level Actions/Reports
  tabs stay as plain links, unrelated to this change).

## Tests

- `AdminPageTest` (integration, WebTestCase against Moco fixtures): assert
  the five section headers are still present in the response body (now as
  tab button labels + pane headings instead of plain `<h2>`s) and that all
  five `tab-pane` ids / nav targets exist. No behavioral change to what
  data is fetched or which actions are available, so existing action
  button/CSRF assertions stay as-is.
- Browser tests (`tests/src/Browser/`): existing `RedirectActionsTest`
  (redirect-and-scroll-to-item behavior) and `ToggleActionsTest` need to
  keep passing — they exercise the exact redirect-fragment flow this
  design must preserve. Add a browser-test assertion that after triggering
  an action in a non-default section (e.g. `invalidate_labels`, section 4),
  the corresponding tab becomes visibly active (not just present in DOM)
  and the item is scrolled into view.
- No test commands are run as part of this design; the user runs `make
  tests` / `make tests-browser` themselves.

## Out of scope

- Reports page (`app_admin_reports`) — stays as one continuous page, no
  sub-tabs.
- No change to which sections/actions exist, their icons, or their order.
- No change to `AdminActionController` routing or the CSRF/POST flow.
- No persistence of "last active tab" across page loads beyond what the
  redirect fragment already provides (e.g. no `localStorage`).
