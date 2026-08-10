# Dex-link picker: filters, self-link guard, view button, sticky direction

## Context

The Album offcanvas's "Liens" section (`templates/Album/_offcanvas.html.twig:105-185`,
JS in `public/js/album-links.js`, CSS in `public/css/album.css:114-238`) lets a
trainer create a link between the dex they're currently viewing and one of
their other dexes. It renders a grid of "picker cards" (`#dex-picker-grid`),
one per other dex, plus a direction selector (`Vers lui`/`Depuis lui`/`Les
deux`) and a "Créer le lien" button above the grid. Filtering the grid
(search + shiny/premium/custom selects) is 100% client-side JS
(`watchDexPickerFilters()`/`applyDexPickerFilters()` in `album-links.js`),
since the picker never reloads the page.

This is unrelated to, but visually similar to, the Trainer ("Dresseur") page
(`templates/Trainer/index.html.twig`), which has its own 5-filter form
(`templates/Trainer/Section/_dex_filters.html.twig`: `privacy`, `homepaged`,
`released`, `shiny`, `premium`, each select gated by `is_granted()` per
filter — `released` is `ROLE_ADMIN`-only) driving a server-rendered,
query-param-based filter on `templates/Trainer/Section/_dex.html.twig`.

Six issues were reported against the picker, all scoped to this one
component:

1. The picker's filters (search + shiny/premium/custom) don't do anything.
2. Need a "lien actif"/"lien non actif" filter — clarified: this means
   "already has a link to the current dex" vs. not, not a new status field
   on the link itself (the link model has no such concept and none is being
   added).
3. The picker's filters must be exactly the Trainer page's 5 filters
   (`privacy`, `homepaged`, `released`, `shiny`, `premium`, same icons,
   labels, and role-gating) — and the text search must be removed.
4. The current dex is selectable as a link target for itself. The code
   already has explicit guards against this (CSS opacity + `cursor:
   not-allowed` on `.dex-pick-card-current`, no click/keydown listener
   attached to that card, no "voir" link rendered for it) — confirmed as a
   real bug (it still happens) rather than a request to change intended
   behaviour.
5. The picker's per-card "voir le dex" link (`.dex-pick-view`, icon-only,
   hidden until hover/focus, `bi-eye`) must match the always-visible,
   labelled button used on the Trainer page
   (`templates/Trainer/Section/_dex.html.twig:49-52`: `btn btn-light
   btn-sm`, `bi-eye-fill` + `trainer.dex.see`|trans` = "Voir").
6. The direction selector + "Créer le lien" button sit above the grid; on a
   long grid the user has to scroll back up to check/change the direction
   before creating a link.

## Goal

Rework the picker's filter row and a few smaller pieces of the same
component so that: filtering actually works; the filter set is exactly the
Trainer page's 5 attribute filters plus one picker-specific "already linked"
filter (6 selects total, no text search); the current dex can never be
selected as its own link target, enforced both client- and server-side;
the "voir le dex" link matches the Trainer page's button; and the direction
selector stays visible while scrolling the grid.

## Design

### 1. Shared filter macro (Trainer page ⇄ picker)

`templates/Trainer/Section/_dex_filters.html.twig` is restructured around a
new `attributeSelect(item, idPrefix, name, selectedValue)` macro, containing
the existing `filtersIcons`/`filtersRole` maps (translation keys stay
`trainer.filters.attributes.*` — already generic, not Trainer-specific
wording) and the same `<div class="form-floating"><select>...` markup as
today. The file keeps its current top-level `<form id="dexFilters">` output
for the Trainer page (unchanged behaviour: `name="{{ filtersName[item] }}"`
short codes, pre-selected from the `filters` object, submitted via
`trainer_filters.js`'s `watchFilters()`), just calling the macro per item
instead of inlining the `<select>`.

The offcanvas (`_offcanvas.html.twig`) imports this macro and calls it for
the same 5 items (`privacy`, `homepaged`, `released`, `shiny`, `premium`),
with `idPrefix = 'dex-picker-filter'`, no `name` (not form-submitted), and
no pre-selected value (always resets to "all" each time the offcanvas
opens, matching today's shiny/premium/custom behaviour). This makes "exactly
the same filters" structurally guaranteed — icons, labels, and role-gates
can't drift between the two pages again, since both read from the one macro.

Rendered IDs in the picker: `dex-picker-filter-privacy`,
`dex-picker-filter-homepaged`, `dex-picker-filter-released`,
`dex-picker-filter-shiny`, `dex-picker-filter-premium` — the last two match
today's IDs, so no JS ID churn for those two.

### 2. Picker card data attributes

Each `.dex-pick-card` (`_offcanvas.html.twig:161-182`) gains one
`data-filter-*` attribute per shared item, sourced from the same `item`
object already used for `data-is-shiny`/`data-is-premium` today (it already
exposes `isPrivate()`, `isOnHome()`, `isReleased()` via
`ResponseObject\Album\DexFlags`, alongside the existing `isShiny()`/
`isPremium()`):

| Filter item | Card attribute | Source |
|---|---|---|
| `privacy` | `data-filter-privacy` | `item.flags.isPrivate` |
| `homepaged` | `data-filter-homepaged` | `item.flags.isOnHome` |
| `released` | `data-filter-released` | `item.flags.isReleased` |
| `shiny` | `data-filter-shiny` | `item.flags.isShiny` |
| `premium` | `data-filter-premium` | `item.flags.isPremium` |

Values are `'1'`/`'0'`, matching the shared macro's select option values —
same convention as today's `data-is-shiny`/`data-is-premium`. `data-name`
(used only by the removed search box) and `data-is-custom` (the removed
`custom` filter isn't part of the shared 5) are dropped.

### 3. New "already linked" filter (not part of the shared macro)

A 6th select, `dex-picker-filter-linked` (icon `bi-link-45deg`, new
translation keys `album.offcanvas.links.filters.attributes.linked.{label,
all,on,off}`), rendered after the 5 shared ones, always visible (no role
gate — every trainer who can open this picker can see existing links).
Unlike the other 5, its value isn't a static server-rendered data attribute:
whether a card is "linked" is only known once `loadLinks()`'s fetch
resolves and `renderPickerGrid()` toggles the `linked` CSS class
(`album-links.js:201`, already existing). So `applyDexPickerFilters()`
reads `card.classList.contains('linked')` at filter-evaluation time for
this one filter, instead of a `card.dataset[...]` lookup like the other 5.

### 4. Filter wiring rewrite (fixes #1 and removes search)

`watchDexPickerFilters()` drops its `search` element entirely (and the
`if (!search) return` early return, which today skips binding *every*
filter — search included — whenever the search box is absent; removing
search removes this whole failure class). It now binds `change` on all
`[id^="dex-picker-filter-"]` elements (already the existing selector,
`album-links.js:63`, which will pick up the 6th "linked" select for free).
`applyDexPickerFilters()` is rewritten to loop the 5 `data-filter-*`
attributes plus the one classList-based `linked` check, dropping the
`search`/`data-name` branch. A new browser test opens the offcanvas, sets a
filter, and asserts the grid updates (`dex-pick-hidden` class present/absent
as expected) — this is the regression test for #1, since the exact prior
runtime cause (a `#dex-picker-search`-shaped guard silently no-op'ing every
filter) is being removed by construction rather than root-caused in
isolation.

### 5. Self-link guard (#4)

Client-side, two defensive checks are added (belt-and-suspenders on top of
the existing "don't attach listeners to the current card" approach, which
clearly isn't sufficient on its own):
- `selectCard(card)` (`album-links.js:17`) returns early if
  `card.classList.contains('dex-pick-card-current')`, mirroring the
  existing `linked` guard on the same line.
- `createLink()` (`album-links.js:102`) returns early if
  `selectedTargetDexSlug === dexSlug`.

Server-side, `pokenini-back`'s `TrainerDexLinkController::create()`
(`src/Controller/Album/TrainerDexLinkController.php:39-72`) gains one more
`400`-returning guard alongside its existing `targetDexSlug`/`bidirectional`
shape checks (same `if (...) { return new JsonResponse([],
Response::HTTP_BAD_REQUEST); }` pattern already used three times in that
method):
reject when `$content['targetDexSlug'] === $dexSlug`. This repo already
fully decodes and validates this payload in the controller (`$content =
json_decode($json, true)`), so this is a one-line addition to existing
logic, not new parsing. `pokenini-web`'s controller for the same route
(`src/Controller/TrainerDexLinkController.php`) stays untouched — it's a
raw-body pass-through today and would need to add JSON decoding just for
this check, whereas `pokenini-back` already has the decoded payload in
hand. `pokenini-api` is not touched — this is input validation, not a data
model change.

### 6. "Voir le dex" button parity (#5)

`_offcanvas.html.twig:170-174`'s `.dex-pick-view` link is replaced with the
same markup as `Trainer/Section/_dex.html.twig:49-52`: `<a class="btn
btn-light btn-sm" href="..." title="{{ 'trainer.dex.see'|trans }}"><i
class="bi bi-eye-fill"></i> {{ 'trainer.dex.see'|trans }}</a>` (reusing the
existing `trainer.dex.see` translation key rather than the picker's own
`album.offcanvas.links.view`, which becomes unused and is removed). It's no
longer opacity-0-until-hover (`album.css:213-235`'s hover/focus-reveal rules
for `.dex-pick-view` are removed) — it's positioned inline in the card
instead of absolutely, same as the Trainer page's card layout, so it's
always visible like the Trainer page's.

### 7. Sticky direction selector (#6)

The wrapping block containing the `btn-group` (direction radios,
`_offcanvas.html.twig:121-130`) and the "Créer le lien" button
(`:132-134`) gets `position: sticky; top: 0;` plus a solid background
(matching the offcanvas body's background, so grid cards don't show through
when scrolled underneath it) and a `z-index` above `.dex-pick-card`. Its
scrolling ancestor is `.offcanvas-body` (Bootstrap's default `overflow-y:
auto`, the only scrollable container in this offcanvas — there's no
separate `.offcanvas-header`), so it pins to the top of the offcanvas as
soon as the grid scrolls underneath it. No duplication of the control.

## Testing

Per this repo's 100% coverage/MSI gate:
- **pokenini-web**: update/extend the existing `Trainer/Section/_dex_filters`
  and offcanvas integration tests for the macro extraction (no behaviour
  change expected on the Trainer page itself); a new or extended browser
  test for the picker exercising: each of the 6 filters actually hides/shows
  cards, the current dex's card is never selectable (click + keyboard), and
  the "voir" button is visible without hovering.
- **pokenini-back**: unit/integration test for the new self-link 400 branch
  in `TrainerDexLinkController::create()`, following the existing pattern
  used for the `targetDexSlug`/`bidirectional` shape-validation tests in
  that controller's test class.

## Out of scope

- No change to `pokenini-api` — no new field, no data model change.
- No change to the Trainer page's own filtering behaviour (server-side,
  query-param-driven) — only where its filter markup comes from.
- No persistence of the picker's filter selections across offcanvas
  open/close (matches today's behaviour for shiny/premium/custom).
- No self-link guard added to `pokenini-web`'s controller — the check lives
  once, in `pokenini-back`, which is the authoritative validation boundary
  already doing full payload parsing for this endpoint.
