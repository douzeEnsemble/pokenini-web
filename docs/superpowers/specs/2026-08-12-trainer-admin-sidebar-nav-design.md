# Replace Trainer/Admin section tabs with a sidebar menu

## Context

`templates/Trainer/_tabs.html.twig` and `templates/Admin/_tabs.html.twig` each
render a horizontal Bootstrap tab bar (`<ul class="nav nav-tabs nav-fill"
role="tablist" id="...">`) listing the pages of their section (3 trainer
pages, 7 admin pages — same `{key, label, route}` lists documented in
[2026-08-12-navbar-subpage-dropdowns-design.md](2026-08-12-navbar-subpage-dropdowns-design.md)).
Every one of the 10 pages that includes one of these partials
(`Trainer/index.html.twig`, `Trainer/links.html.twig`,
`Trainer/personnal_data.html.twig`; `Admin/update_data.html.twig`,
`Admin/update_availabilities.html.twig`, `Admin/calculate_data.html.twig`,
`Admin/invalidate_data.html.twig`, `Admin/trigger_pipeline.html.twig`,
`Admin/reports.html.twig`, `Admin/versions.html.twig`) follows the same
shape: a full-width `<h1>` row, then a full-width row containing the tabs
include, then a full-width row containing the page's content include.

The goal is to turn this into a sidebar layout: the section's pages listed
in a vertical menu to the left, page content to the right.

## Goal

Replace the horizontal tab bar with an always-visible vertical sidebar menu
on both the Trainer and Admin sections. On screens narrower than the `md`
breakpoint (768px) the sidebar stacks above the content instead of sitting
beside it — no toggle button, no offcanvas, no JS. This was confirmed with
the user: the sidebar is a pure layout change, always visible.

## Design

### `_tabs.html.twig`: tabs markup → vertical pills

Both partials change identically. `Trainer/_tabs.html.twig`:

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

`Admin/_tabs.html.twig` gets the same treatment (its own `adminPages` array
and `id="admin-actions-tab"` untouched).

Changes from the current markup, and why each one is safe:
- `nav nav-tabs nav-fill` → `nav flex-column nav-pills`: this is Bootstrap's
  own documented conversion from horizontal tabs to a vertical sidebar nav.
  `nav-fill` (equal-width horizontal tabs) has no meaning once the list is
  vertical, so it's dropped.
- `mb-4` → `mb-4 mb-md-0`: keeps a gap below the menu when it's stacked
  above content on narrow screens, removes it once the menu sits beside
  content as its own column at `md`+ (a `col-md-9` sibling already provides
  the visual separation there).
- `role="tablist"` / `role="presentation"` on each `<li>` are dropped. These
  pages are separate routes, not panels of a single-page tab widget — the
  ARIA tabs pattern was already a mismatch for full-page navigation before
  this change; removing it here is a small correction that falls out
  naturally from rewriting this markup, not a separate cleanup pass.
- `id="trainer-section-tab"` / `id="admin-actions-tab"` and every
  `.nav-item` / `.nav-link` / `.active` class are unchanged. Every existing
  test selector (`#trainer-section-tab .nav-link`, `#admin-actions-tab >
  .nav-item > .nav-link`, `.nav-link.active`, etc. — see Tests below) keeps
  matching without modification.
- Sidebar becomes `sticky-top` (Bootstrap utility, no JS) so it stays
  visible while scrolling long pages like Admin's "MAJ des données", which
  stacks several action cards vertically. Applied on the wrapping column
  (`<div class="col-md-3">...`) in each page template, not on the `<ul>`
  itself, so it has no effect once the column isn't beside content (below
  `md`, a stacked full-width column has nothing to "stick" against that
  isn't already at the top).

### Page templates: one row → two columns

Every page's `{% block container %}` moves from three stacked full-width
rows (title / tabs / content) to: one full-width row for the `<h1>`, then
one row split into a `col-md-3` (sidebar) and a `col-md-9` (content). Below
`md` both columns are full width and stack (`col-md-*` only takes effect at
`md`+; Bootstrap's default column behavior below that is 100% width,
already stacked — this is exactly the "always visible, stacked" behavior
the user chose, achieved with zero extra classes).

Trainer example (`Trainer/index.html.twig`, the other two Trainer pages
follow the identical shape with their own `active` key and content
include):

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
{% endblock %}
```

The content include's own internal grid (e.g. `Trainer/Section/_dex.html.twig`
already opens its own `<div class="row">` for its item cards) is untouched
— it nests correctly inside the new `col-md-9` exactly as it nested inside
the old `col-md-12`. The vestigial extra `row` class on the old
`col-md-12 mx-auto row` content wrapper is dropped as part of this rewrite
(it did nothing: nothing inside `_dex.html.twig`, `_links.html.twig`, or
`_personnal_data.html.twig` is a direct `col-*` child of that specific div).

Admin example (`Admin/update_data.html.twig`, the other five plain Admin
action pages — `update_availabilities`, `calculate_data`,
`invalidate_data`, `trigger_pipeline`, `reports` — follow the identical
shape):

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

`reports.html.twig` keeps passing its (already-unused — pre-existing dead
parameter, out of scope to remove here) `'page': 'reports'` include
argument alongside `'active': 'reports'`.

**`Admin/versions.html.twig` is a special case.** `Admin/_versions.html.twig`
(the content partial) opens with `<div class="list-group col-4"
id="versions-list">` — it puts a grid column class directly on its own root
element and relies on being inside a `row justify-content-center` wrapper
to center that narrow list on the page (this is why today's
`versions.html.twig` wraps everything in `<div class="col-12 row
justify-content-center">`). Moving the include straight into a bare
`col-md-9` would lose that centering. Keep the centering wrapper, scoped
to just the content column:

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

### No changes to

- Any controller, route, or translation.
- The content partials themselves (`Trainer/Section/_*.html.twig`,
  `Admin/_update_data.html.twig` and siblings, `Admin/_reports.html.twig`,
  `Admin/_versions.html.twig`) — all already open their own internal
  `.row` where they need one, so they drop into a narrower parent column
  unmodified.
- `public/css/base.css` / `public/css/admin.css` — no new rules expected;
  only add something if visual QA in a real browser shows a real problem
  (same policy as the earlier navbar-dropdown work), not speculatively.

### Expected visual side effect (not a defect)

Bootstrap grid breakpoints size against the viewport, not the parent
column. Card grids inside the new narrower `col-md-9` content area (Admin's
`col-lg-4 col-md-6` action cards, the Trainer dex's `col-lg-3 col-md-4
col-sm-6` items) will pack more tightly per row than they do today at the
same viewport width, because they now share the row with a sidebar instead
of spanning the full page. This is the standard trade-off of any
sidebar-plus-content layout and is not compensated for in this design.

## Tests

Every existing test assertion targeting these partials keeps passing
unmodified, because ids, `.nav-item`/`.nav-link`/`.active` classes, hrefs,
and item counts/order are all untouched — only wrapping/layout classes
change:
- `TrainerPageTest`, `TrainerLinksPageTest`, `TrainerPersonnalDataPageTest`:
  `#trainer-section-tab .nav-link` count and `.nav-link.active` href.
- `AdminUpdateDataTest`: `#admin-actions-tab > .nav-item > .nav-link` count,
  `.nav-link.active` count, and specific `a.nav-link` hrefs by position.
- `AdminReportsTest`, and the dropdown-active-state assertions added to it
  and to `TrainerLinksPageTest` by the earlier navbar-dropdown branch
  (`.navbar-nav .trainer-link .dropdown-item.active` /
  `.navbar-nav .admin-link .dropdown-item.active`) — those target the
  *bottom navbar*, an entirely different partial, untouched by this design.

No new PHPUnit assertions are strictly required for a pure layout/CSS
change, but the one behavior that could silently break — the stack-below-
`md` responsive switch — has no automated coverage today and won't get any
from a DOM-structure assertion (it's a CSS media-query effect, not a markup
difference). Verify it with a real-browser screenshot check (same
throwaway-Panther-test technique used for the navbar-dropdown branch): one
screenshot at a `md`+ width showing sidebar-beside-content, one at a narrow
width showing sidebar-stacked-above-content, for at least one Trainer page
and one Admin page (`versions.html.twig`, given its special centering
case, is worth including).

## Out of scope

- No changes to the bottom navbar or its "jump directly to a sub-page"
  dropdown added in the prior branch — that's global cross-section
  navigation from anywhere in the site; this sidebar is local, in-page
  navigation within the Trainer or Admin section. The two overlap in
  purpose but are not merged into one component.
- No offcanvas/collapsible sidebar for narrow screens — confirmed with the
  user, always-visible stacked layout only.
- No removal of the pre-existing unused `'page'` include parameter on
  `Admin/reports.html.twig` / `Admin/versions.html.twig`.
- No compensating changes to the card-grid breakpoints inside content
  partials (see "Expected visual side effect" above).
