# Admin "Versions" tab

## Context

**Status: the base feature described in most of this document is already
merged** (`pokenini-web` PR #398, `pokenini-back` PR #146, `pokenini-api`
PR #338, all squash-merged to `main`). It added a "Versions" admin tab
showing a plain `<table>` with one row per brick (Web / Back / Api /
Resources) and its version string, falling back to an "Unavailable" badge
per row. This document now also covers a follow-up: replacing that table
with a nicer enriched-list display, and adding the date-time each brick's
version file was last modified. Sections below are written against the
current, merged code — not a hypothetical starting point.

The admin page (`/istration`) already has two tabs — Actions and Reports —
each with its own route/controller/template, sharing `Admin/_tabs.html.twig`
for the tab bar.

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

A new "Versions" admin tab in `pokenini-web` showing, for all four bricks
(Web, Back, Api, Resources): the current version string, and the date-time
the version file was last modified. If a brick is unreachable, its row shows
an "Unavailable" badge (version and date both absent) and the rest of the
page still renders normally.

## Design

### Shared shape: `BrickVersion`

Every brick is represented, end to end, as a `{version, updatedAt}` pair
rather than a bare string:

- `version: ?string`
- `updatedAt: ?\DateTimeImmutable`

For a brick reached over the network (Back and Api, as seen from `pokenini-web`;
Api, as seen from `pokenini-back`) the two fields travel together — an
unreachable brick has both fields `null`. For a brick's *own* local file
(Web reading its own file, Back reading its own file, Api reading its own
file), the existing per-repo fallback for a missing file is unchanged —
`VersionService`/`LocalVersionService` return the literal string `'unknown'`,
`AppVersionService` returns `'0.0.toto'` — but `updatedAt` is `null` in that
same case, since there's no meaningful fallback timestamp. This shape is used
in `pokenini-api`'s `/version` response, in `pokenini-back`'s
`/istration/version` response (for both the `back` and `api` entries), and
in `pokenini-web`'s internal `VersionsOverview`.

**Note (pokenini-web only):** the already-merged code has an established
precedent for this exact situation — `ResponseObject\ImagePipelineStatus` is
deserialized from the back's JSON, then passed straight through
`AdminController` into the template with no separate DTO twin. `BrickVersion`
follows the same precedent: **one** class, `App\ResponseObject\BrickVersion`,
used both for JSON deserialization (inside `ResponseObject\Versions`) and as
the field type on `DTO\VersionsOverview` — not a `ResponseObject`/`DTO` pair.
It's manually constructed (not deserialized) for the `web` and `resources`
entries, which is fine: `#[SerializedName]` attributes are inert on a plain
`new BrickVersion(...)` call.

### Data flow

```
Browser → GET /{locale}/istration/versions   (pokenini-web, ROLE_ADMIN)
              ├─ web version+date       : existing AppVersionService (local file, unchanged) + its filemtime
              ├─ back + api version+date: GET {BACK_URL}/istration/version
              │                              └─ back internally calls GET {API_URL}/version
              └─ resources version+date : GET {RESOURCES_VERSION_URL}  (direct call, like images)
```

Each of the three remote calls is independently try/caught. No single
unreachable brick fails the page.

### Source of the date, per brick

- **Web**: `filemtime()` of the local `resources/metadata/version` file.
  `AppVersionService` already reads this mtime today (to build its cache
  key) — it just needs to also expose it, not only use it internally.
- **Back / Api**: same `filemtime()` pattern, added to each repo's own
  local version-reading service, and serialized as `updated_at` (ISO 8601,
  e.g. `2026-08-05T09:12:00+00:00`) alongside `version` in that repo's JSON
  response.
- **Resources**: this repo is a bare Apache static host (`httpd:2.4-alpine`)
  with no application code — adding a version-reading endpoint there is out
  of scope, so no new code is added to that repo. Instead, `pokenini-web`
  reads the `Last-Modified` HTTP response header off the same GET request
  that already fetches the version file's body (Apache sends this header
  for every static file by default). If the header is absent for any
  reason, `updatedAt` is `null` for that brick even though `version` may
  still be present — the two fields are independently nullable for this one
  brick only, unlike the other three where they're always null/non-null
  together.

### pokenini-api (already merged, PR #338 — extend)

The merged `VersionService::getVersion(): string` reads `<metadataDir>/version`
via `is_readable()`/`file_get_contents()` (no `Filesystem` component
involved), falling back to the literal `'unknown'`. It gains a sibling
`getUpdatedAt(): ?\DateTimeImmutable`, same `is_readable()` guard, built from
`filemtime()`, `null` if the file is missing. `DTO\Response\VersionResponse`
gains a second constructor property,
`#[SerializedName('updated_at')] public readonly ?\DateTimeImmutable $updatedAt`,
and `VersionController::get()` passes both values through. Response becomes
`{"version": "1.2.12", "updated_at": "2026-08-05T09:12:00+00:00"}`. No
security-config change (existing global `access_control` already covers
`^/`).

### pokenini-back (already merged, PR #146 — extend)

The merged `LocalVersionService::getVersion(): string` mirrors the api's
service (`is_file()` guard, `'unknown'` fallback, no `Filesystem` component).
It gains the same sibling `getUpdatedAt(): ?\DateTimeImmutable`.
`Service/Api/GetVersionApiService::get()` currently returns `?string` decoded
via `JsonDecoder::decode()` (plain array decode, no Symfony Serializer — this
repo's established convention for `Service/Api/*` classes, see
`GetTypesApiService`). It changes to return
`array{version: ?string, updated_at: ?\DateTimeImmutable}`: still one
`JsonDecoder::decode()` call, reading both `version` and `updated_at` off the
decoded array and wrapping `updated_at` in `new \DateTimeImmutable($value)`
when not null; the existing `catch (ExceptionInterface|\JsonException)` still
returns `['version' => null, 'updated_at' => null]`.
`Controller/Admin/AdminVersionController::version()` (currently
`$this->json(['back' => ..., 'api' => ...])` with bare strings) becomes:

```php
return $this->json([
    'back' => [
        'version' => $this->localVersionService->getVersion(),
        'updated_at' => $this->localVersionService->getUpdatedAt(),
    ],
    'api' => $this->getVersionApiService->get(),
]);
```

`$this->json()` uses the container's `serializer` service, which already
normalizes `\DateTimeImmutable` values to ISO 8601 strings automatically —
no extra configuration needed. Response becomes
`{"back": {"version": "1.2.12", "updated_at": "..."}, "api": {"version": "1.2.12", "updated_at": "..."}}`
(api's pair is `{null, null}` when unreachable). No security-config change.

### pokenini-web (new)

This repo already has the base feature merged (PR #398): `AppVersionService`
(unchanged local-file reader), `ResponseObject\Versions` (currently
`back: ?string`, `api: ?string`), `Service\Back\GetVersionsService`,
`Service\GetResourcesVersionService`, `DTO\VersionsOverview` (currently four
`?string` fields), `Service\VersionsOverviewService`,
`Controller\AdminVersionsController`, `templates/Admin/versions.html.twig` +
`_versions.html.twig` (plain table), and the `RESOURCES_VERSION_URL` env var.
This section describes the changes layered on top of that merged code, not a
from-scratch build.

- New `ResponseObject\BrickVersion`: `version: ?string`,
  `#[SerializedName('updated_at')] updatedAt: ?\DateTimeImmutable`. Per the
  note above, this single class is reused as the field type on
  `DTO\VersionsOverview` too — no separate DTO-layer twin.
- `ResponseObject\Versions` changes from `back: ?string`, `api: ?string` to
  `back: BrickVersion`, `api: BrickVersion` (deserialized from the back's
  nested JSON response).
- `Service/Back/GetVersionsService::get()`: unchanged control flow, but its
  failure branch now returns `new Versions(new BrickVersion(null, null), new BrickVersion(null, null))`
  instead of `new Versions(null, null)`.
- `Service/GetResourcesVersionService::get()`: return type changes from
  `?string` to `BrickVersion`. Reads the `Last-Modified` response header via
  `$response->getHeaders()['last-modified'][0] ?? null`, wrapped in the same
  try/catch as the body read (a transport/HTTP failure already returns
  `BrickVersion(null, null)`, logging exactly as it does today); when the
  header is present, `updatedAt` is `new \DateTimeImmutable($headerValue)` —
  PHP's `DateTimeImmutable` constructor parses the RFC 7231 `Last-Modified`
  format natively.
- `AppVersionService::getVersion()` gains a sibling method
  `getUpdatedAt(string $filename = 'version'): ?\DateTimeImmutable` that
  returns a `\DateTimeImmutable` built from the same `filemtime()` call the
  method already performs for its cache key (or `null` if the file doesn't
  exist) — the existing `getVersion()` signature and behaviour are
  otherwise unchanged.
- `DTO\VersionsOverview`: four fields change from `?string` to
  `BrickVersion` — `web`, `back`, `api`, `resources`.
- `Service\VersionsOverviewService::get()`: still the only class the
  controller talks to; now builds `web` as
  `new BrickVersion($this->appVersionService->getVersion(), $this->appVersionService->getUpdatedAt())`,
  passes `$versions->back` / `$versions->api` straight through (already the
  right type), and passes `$this->getResourcesVersionService->get()`
  straight through likewise.
- `Controller\AdminVersionsController` and `templates/Admin/versions.html.twig`
  (the page shell that includes `Admin/_tabs.html.twig` and
  `Admin/_versions.html.twig`) are unchanged — the controller already just
  calls `VersionsOverviewService::get()` and renders the shell, and the shell
  already includes `_versions.html.twig`, which is the only template that
  needs rewriting.
- `templates/Admin/_versions.html.twig` is rewritten from the current plain
  `<table>` to an **enriched list**: one styled row per brick (Web / Back /
  Api / Resources, same order and same `id="versions-row-{{ brick.key }}"`
  markers the existing integration test already asserts on), each row
  showing a small coloured icon/initial badge, the brick name, the version
  in bold, a status badge, and the date right-aligned. Rows are `<div>`-based
  (not `<table>`), reusing Bootstrap utility classes already available in
  this project (`d-flex`, `align-items-center`, `badge`, `text-bg-*`) rather
  than introducing new CSS. Per row:
  - if `brick.version` is not null: show the version in bold, plus (if
    `brick.updatedAt` is not null) the formatted date
    `{{ brick.updatedAt|date('d/m/Y \\à H:i', 'Europe/Paris') }}` —
    following the exact filter/timezone convention already used in
    `Admin/_macros.html.twig`, not the unused `intl-extra` filters. If
    `updatedAt` is null (only possible for Resources, per the
    independently-nullable note above) the date cell is simply blank.
  - if `brick.version` is null: show a `badge text-bg-secondary` reading
    "Unavailable"/"Indisponible" and no date — same neutral-gray treatment
    as the original plain-table design, not a red/danger badge, since an
    unreachable brick isn't necessarily an outage.
- `Admin/_tabs.html.twig` and the `RESOURCES_VERSION_URL` env var (in
  `.env.dev`, `.env.prod`, `.env.int`, `.env.ci`, `.env.test` — the 5
  git-tracked env files) are unchanged; both already exist from the merged
  base feature.

### Error handling

Every remote call (back, api-via-back, resources) is independently
try/caught at the service level, degrading to a null `BrickVersion` (or a
null `updatedAt` alone, for Resources) for that field only. The template
renders an "Unavailable" badge per row when `version` is null. Nothing
about this feature can turn into a 500 page — worst case, all four rows
read "Unavailable" except Web (which is always available, being a local
file read).

### Testing

Each repo keeps its own 100%-coverage / 100%-MSI gate, so every new
class needs unit coverage, and every new endpoint needs an integration
test, following that repo's existing conventions:

- **pokenini-api**: unit test for the version-reading service (version +
  updatedAt, including the missing-file fallback for both); integration
  test for the new `GET /version` controller (happy path + missing-file
  fallback), asserting `updated_at` against the fixture file's own
  `filemtime()` rather than a hardcoded timestamp.
- **pokenini-back**: unit tests for the version-reading service and for
  `GetVersionApiService` (mocked HTTP client, success + failure cases,
  covering both `version` and `updated_at`); integration test for
  `AdminVersionController` (api reachable vs. api down → `api: {null, null}`).
- **pokenini-web**: unit tests for `GetResourcesVersionService` (including
  the `Last-Modified`-header-present vs. absent branches),
  `GetVersionsService`, `VersionsOverviewService`, and
  `AppVersionService::getUpdatedAt()` (mocked HTTP client/collaborators,
  success + failure branches); integration test for `AdminVersionsController`
  using new Moco fixtures under `tests/resources/moco/Back/` for
  `GET /istration/version` (nominal + `api` unavailable cases), asserting
  both the version text and the rendered date string per row; a browser
  test is not required (static admin content, no interactive JS).

## Out of scope

- No caching for the three new remote calls (back, api-via-back,
  resources) — this is a low-traffic admin page; only `web`'s own version
  keeps using the existing file-mtime cache, unchanged.
- No proxying of the resources call through back — `pokenini-web` already
  talks to the resources host directly for images, so this follows the
  same established pattern.
- No changes to `ImagePipelineStatus` or any other existing admin
  tab/service. `AppVersionService` itself does change (new `getUpdatedAt()`
  method), but its existing `getVersion()` behaviour and the `version()`
  Twig function used in the footer are untouched.
- No relative-time display ("2 days ago") and no commit-hash display — just
  the version string plus one absolute, localized timestamp per brick.
