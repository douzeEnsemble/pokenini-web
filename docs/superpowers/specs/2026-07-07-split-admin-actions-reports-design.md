# Split Admin "Actions" and "Reports" into separate pages

## Context

The `/istration` admin page currently combines two unrelated concerns on a
single route (`AdminController::index()`):

- **Actions**: buttons that trigger update/calculate/invalidate operations
  on the Back API, plus a history of past runs (`GetActionLogsService`).
- **Reports**: Chart.js dashboards on catch-state usage, dex usage, and
  catch-state counts by trainer (`GetReportsService`), which requires a
  heavier aggregation query on the Back and pulls in Chart.js +
  patternomaly + chartjs-plugin-datalabels from a CDN.

Both services are called unconditionally on every load of `/istration`,
even when the admin only wants to trigger an action and never looks at the
reports, and vice versa. The two blocks are already separate Twig partials
(`Admin/_actions.html.twig`, `Admin/_reports.html.twig`) included from the
same `Admin/index.html.twig`, so the split is natural.

## Goal

Serve Actions and Reports from separate routes so that each page only
loads the data (and script includes) it actually needs.

## Design

### Routing

- `GET /istration/actions` (route `app_admin_actions`) — replaces
  `app_admin_index`. Loads only `GetActionLogsService::get()`.
- `GET /istration/reports` (route `app_admin_reports`) — new. Loads only
  `GetReportsService::get()`.
- `GET /istration` — redirects (302) to `app_admin_actions`, so the
  existing bookmarked URL keeps working.
- Both routes stay under the `^/(en|fr)/istration` prefix already covered
  by the `ROLE_ADMIN` access-control rule in `security.yaml` — no security
  config change needed.

### Controllers

- `AdminController`: becomes the Actions-page controller. `index()` is
  renamed `actions()`, route path/name updated to `/istration/actions` /
  `app_admin_actions`. Only injects `GetActionLogsService`. Keeps the
  session-flash handling for `AdminAction` (used to show the result banner
  after a POST).
- New `AdminReportsController`: mirrors `AdminController`'s shape, injects
  only `GetReportsService`, route `/istration/reports` / `app_admin_reports`.
  Handles the flash-driven banner for the `invalidate_reports` action
  (the only action button that lives on the Reports page).
- The route name `app_admin_index` is kept (only its controller action and
  path change) and repurposed as a pure redirect to `app_admin_actions`,
  so nothing else needs a new route name invented.
- `AdminActionController::execute()` currently always redirects to
  `app_admin_index`. It changes to redirect to `app_admin_reports` when
  `$name === 'reports'` (the report-cache invalidation action — its button
  lives on the Reports page), and to `app_admin_actions` for every other
  action name (update/calculate/invalidate of labels/dex/albums/actions —
  their buttons live on the Actions page). The `_fragment` anchor behavior
  is preserved in both branches.

### Templates

- `Admin/index.html.twig`'s shared shell (title, `admin.css`, the
  flash-parsing `{% set %}` block for `updatedItem`/`updatedAction`/
  `updatedState`) is split into two thin page templates:
  `Admin/actions.html.twig` and `Admin/reports.html.twig`. Each includes
  its existing partial (`_actions.html.twig` / `_reports.html.twig`
  unchanged) plus the scripts it needs:
  - `actions.html.twig`: `admin.js` (`watchActionLogToggles()`).
  - `reports.html.twig`: Chart.js/patternomaly/chartjs-plugin-datalabels
    CDN scripts + `_reports_scripts.html.twig`.
- Both page templates add a small Bootstrap tab bar (`nav nav-tabs`) above
  the content, linking to `app_admin_actions` / `app_admin_reports`, with
  the current route marked active — following the same "active route"
  pattern already used for the nav-bar admin link in `_nav.html.twig`.
  These are plain links (full page navigation), not JS-driven tabs, so
  switching tabs never fetches the other page's data unless you actually
  navigate there.
- `_nav.html.twig`'s single "Admin" entry keeps pointing at
  `app_admin_actions` (the entry point); no second top-level nav item is
  added, since the in-page tabs handle the Actions/Reports sub-navigation.
  Its "active" check (`_nav.html.twig:76`, currently
  `'app_admin_index' == currentRoute`) is updated to match either
  `app_admin_actions` or `app_admin_reports`, so the nav-bar link stays
  highlighted regardless of which admin tab is open.

### Tests

- `AdminPageTest`: repoint requests to `/fr/istration/actions`; drop the
  `table.report-table` / canvas assertions (already duplicated in
  `AdminReportsTest`).
- `AdminReportsTest`: repoint requests to `/fr/istration/reports`.
- New redirect test: `GET /fr/istration` → 302 to `/fr/istration/actions`.
- `ActionCalculateTest` / `ActionInvalidateTest` / `ActionUpdateTest`:
  update expected redirect target route (`app_admin_actions` vs
  `app_admin_reports` depending on the invalidated `name`).
- `AdminActionControllerTest` (unit): update expected redirect route/name
  assertions.
- Browser tests `RedirectActionsTest`, `ToggleActionsTest`: update the
  URLs they visit.
- No test commands are run as part of this work; the user runs `make
  tests` themselves.

## Out of scope

- No changes to `GetReportsService` / `GetActionLogsService` internals.
- No changes to the Back API.
- No changes to `security.yaml` (existing prefix rule already covers both
  new routes).
