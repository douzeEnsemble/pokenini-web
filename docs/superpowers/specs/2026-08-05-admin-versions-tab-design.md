# Admin "Versions" tab

## Context

The admin page (`/istration`) already has two tabs — Actions and Reports —
each with its own route/controller/template, sharing `Admin/_tabs.html.twig`
for the tab bar. There is no way today to see, at a glance, which version of
each Pokénini brick is currently deployed.

The Pokénini stack is four independent repos, each with its own release
process:

- `pokenini-web` (this repo) — already has `AppVersionService`, which reads
  a plain-text `resources/metadata/version` file (written by CI at build
  time), caches it by file mtime in the `cache.app_version` pool, and
  exposes it to Twig as the `version()` function. Currently only used in the
  footer.
- `pokenini-back` — has the same `resources/metadata/version` file, written
  the same way by its own CI, but nothing reads it at runtime and no
  endpoint exposes it.
- `pokenini-api` — same situation: file exists, nothing exposes it.
- `pokenini-resources` — a static-assets repo served by plain Apache
  (`httpd:2.4-alpine`) with the whole repo as document root. Its
  `resources/metadata/version` file is therefore **already reachable over
  plain HTTP** with no code change on that side.

`pokenini-web` already talks directly to the `pokenini-resources` host for
images (`POKEMON_IMAGE_URL` env var), and is architecturally forbidden from
calling `pokenini-api` directly — it must always go through `pokenini-back`.

## Goal

A new "Versions" admin tab in `pokenini-web` showing the current version of
all four bricks (Web, Back, Api, Resources). If a brick is unreachable, its
row shows "unavailable" and the rest of the page still renders normally.

## Design

### Data flow

```
Browser → GET /{locale}/istration/versions   (pokenini-web, ROLE_ADMIN)
              ├─ web version       : existing AppVersionService (local file, unchanged)
              ├─ back + api version: GET {BACK_URL}/istration/version
              │                          └─ back internally calls GET {API_URL}/version
              └─ resources version : GET {RESOURCES_VERSION_URL}  (direct call, like images)
```

Each of the three remote calls is independently try/caught. No single
unreachable brick fails the page.

### pokenini-api (new)

- A small version-reading service (mirrors the *behaviour* of
  `AppVersionService` but without its cache layer — this endpoint is
  low-traffic admin tooling, so a Symfony cache pool is unnecessary
  overhead): reads `resources/metadata/version` via
  `Symfony\Component\Filesystem\Filesystem`, trims it, returns the string
  (or a clear fallback if the file is missing).
- New controller: `GET /version` → `{"version": "1.2.12"}`. No new security
  config needed — the existing global `access_control` (`roles: ROLE_API`,
  HTTP Basic) already covers `^/`.

### pokenini-back (new)

- Same version-reading service as api, for back's own
  `resources/metadata/version`.
- New `Service/Api/GetVersionApiService extends AbstractApiService`: calls
  `GET /version` on the api. Catches HTTP/transport exceptions and returns
  `null` (logs the failure) rather than propagating.
- New `Controller/Admin/AdminVersionController`: `GET /istration/version` →
  `{"back": "1.2.12", "api": "1.2.12"}` (api's value is `null` when
  unreachable). Already under `ROLE_ADMIN` via the existing
  `^/istration` access-control prefix — no security change needed. This
  endpoint itself never fails: a failing api call degrades to `api: null`,
  not an error response.

### pokenini-web (new)

- `ResponseObject\Versions`: `back: ?string`, `api: ?string` — deserialized
  from the back's JSON response via the Symfony Serializer, same pattern as
  `ResponseObject\ImagePipelineStatus`.
- `Service/Back/GetVersionsService extends AbstractBackService`: calls
  `GET /istration/version`, deserializes into `Versions`. Catches
  transport/client exceptions from the HTTP call itself (i.e. back totally
  unreachable) and returns a `Versions` with both fields `null`, rather than
  throwing.
- `Service/GetResourcesVersionService`: plain HTTP GET (via
  `HttpClientInterface`, not through `AbstractBackService` since this isn't
  a back call) against the new `RESOURCES_VERSION_URL` env var. Returns the
  trimmed body, or `null` on any HTTP/transport failure.
- `DTO\VersionsOverview`: four `?string` fields — `web`, `back`, `api`,
  `resources`. Not tied to any single API response shape (per the existing
  DTO-layer convention).
- `Service\VersionsOverviewService::get(): VersionsOverview` — orchestrates
  the three calls above plus the existing `AppVersionService::getVersion()`
  for `web`, and assembles the DTO. This is the only class the controller
  talks to.
- `Controller\AdminVersionsController`: `GET /istration/versions` (route
  `app_admin_versions`), calls `VersionsOverviewService::get()`, renders
  `Admin/versions.html.twig`.
- `templates/Admin/versions.html.twig`: extends `base.html.twig`, includes
  `Admin/_tabs.html.twig` with `page: 'versions', active: 'versions'`, then
  a simple table with one row per brick (Web / Back / Api / Resources),
  showing the version string or an "unavailable" badge when the value is
  `null`.
- `Admin/_tabs.html.twig`: add one more `<li>` link to `app_admin_versions`,
  following exactly the existing Reports `<li>` (lines 32-36) — a plain
  link with `{{ 'versions' == active ? ' active' : '' }}`.
- New env var `RESOURCES_VERSION_URL`, added alongside `POKEMON_IMAGE_URL`
  in every `.env*` file, same host/port, path changed to
  `/resources/metadata/version`:

  | File | Value |
  |---|---|
  | `.env` | `http://localhost:8083/resources/metadata/version` |
  | `.env.dev` | `http://localhost:8083/resources/metadata/version` |
  | `.env.ci` | `http://localhost:8082/resources/metadata/version` |
  | `.env.prod` | `http://localhost:8082/resources/metadata/version` |
  | `.env.int` | `https://icon.pokenini.fr/resources/metadata/version` |

  Bound as a constructor argument the same way `BACK_URL` is bound today
  in `config/services.yaml`.

### Error handling

Every remote call (back, api-via-back, resources) is independently
try/caught at the service level, degrading to `null` for that field only.
The template renders "unavailable" per row. Nothing about this feature can
turn into a 500 page — worst case, all four rows read "unavailable" except
Web (which is always available, being a local file read).

### Testing

Each repo keeps its own 100%-coverage / 100%-MSI gate, so every new
class needs unit coverage, and every new endpoint needs an integration
test, following that repo's existing conventions:

- **pokenini-api**: unit test for the version-reading service; integration
  test for the new `GET /version` controller (happy path + missing-file
  fallback).
- **pokenini-back**: unit tests for the version-reading service and for
  `GetVersionApiService` (mocked HTTP client, success + failure cases);
  integration test for `AdminVersionController` (api reachable vs. api
  down → `api: null`).
- **pokenini-web**: unit tests for `GetResourcesVersionService`,
  `GetVersionsService`, `VersionsOverviewService` (mocked HTTP
  client/collaborators, success + failure branches); integration test for
  `AdminVersionsController` using new Moco fixtures under
  `tests/resources/moco/Back/` for `GET /istration/version` (nominal +
  `api: null` cases); a browser test is not required (static admin table,
  no interactive JS).

## Out of scope

- No caching for the three new remote calls (back, api-via-back,
  resources) — this is a low-traffic admin page; only `web`'s own version
  keeps using the existing file-mtime cache, unchanged.
- No proxying of the resources call through back — `pokenini-web` already
  talks to the resources host directly for images, so this follows the
  same established pattern.
- No changes to `AppVersionService`, `ImagePipelineStatus`, or any existing
  admin tab/service.
- No new translations beyond the tab label and an "unavailable" string —
  no rich formatting, no build-date/commit-hash display, just the raw
  version string already written by each repo's CI.
