# Design: split `ci_tests.yml` into parallel jobs + Docker image archive cache (pokenini-web)

## Purpose

Same motivation and mechanism as the already-implemented designs in `pokenini-back`
(PR #154) and `pokenini-api`: `ci_tests.yml` currently runs everything in one
sequential job (`allin`) on one runner. Split it into parallel jobs so failures are
isolated and the pipeline runs faster, and add a Docker image archive cache to the
shared composite action so the now-multiplied image build isn't repeated per job.

This repo's split differs from the other two in one structural way, confirmed by
reading the actual files (not assumed from precedent): `pokenini-web` has a third test
category, `tests/src/Browser/` (Panther browser tests against real Selenium
`chrome`/`firefox` containers defined in `docker-compose.yaml`, each with a
healthcheck; the `php` service `depends_on` both being healthy). `pokenini-back` and
`pokenini-api` have no equivalent.

Per this repo's `Makefile`, browser tests already run against both browsers in
parallel locally (`make tests-browser` → `tests-browser-chrome` +
`tests-browser-firefox`, each setting `PANTHER_SELENIUM_HOST` /
`PANTHER_BROWSER_NAME` before invoking PHPUnit on `tests/src/Browser`). The current CI
job runs the whole `tests/src/` tree in one shot with neither env var set, so it
exercises Browser tests today only under Panther's library default resolution — not
explicitly against both browsers. Per explicit decision for this change: CI gains
**two** browser jobs (`browser-tests-chrome`, `browser-tests-firefox`), matching the
Makefile's dual-browser local behavior — a deliberate coverage increase, not a
behavior-preserving split, unlike every other job in this design.

## Current state (baseline)

Single job `allin` in `.github/workflows/ci_tests.yml`:
1. `phpunit tests/src/ --exclude-group="time-testing"` (full suite: Unit + Integration
  + Browser, run together — no `PANTHER_SELENIUM_HOST`/`PANTHER_BROWSER_NAME` set)
2. Coverage run (full suite again, `--exclude-group="browser-testing"`, Xdebug —
  despite the flag's name this does not actually exclude Browser tests; see below)
3. Infection composer install + run (consumes step 2's coverage output)
4. `symfony security:check`

Repo-wide grep for PHPUnit `#[Group(...)]` attributes found only `api-mocked-testing`
(54 test classes, spanning both `tests/src/Integration/**` and `tests/src/Browser/**`)
— confirmed during research. Neither `time-testing` nor `browser-testing` is used
anywhere; both `--exclude-group` flags in `ci_tests.yml` are dead/no-op, exactly as in
the other two repos. Kept as-is, not removed.

Pre-existing discrepancy (not introduced by this change, not fixed by it — noted so
it isn't mistaken for a regression): the Makefile's own coverage targets
(`coverage-generate`, `coverage-html`) use `--exclude-testsuite="Browser Test Suite"`
(a real, effective exclusion, via the two testsuites `phpunit.xml.dist` defines) to
keep Browser tests out of the coverage/mutation numbers locally, while
`ci_tests.yml`'s coverage step uses the inert `--exclude-group="browser-testing"` and
therefore *does* fold Browser tests into CI's coverage and Infection runs today. This
plan preserves that existing CI behavior verbatim in the `measures` job (per the
"split without changing behavior" principle used for every job except the two new
browser jobs) rather than silently fixing the mismatch — that's a separate, optional
follow-up if wanted later.

`phpunit.xml.dist` defines two testsuites: "Project Test Suite" (`tests`, excluding
`tests/src/Browser`) and "Browser Test Suite" (`tests/src/Browser` only), with the
Panther `ServerExtension` bootstrap. The Makefile already exposes `tests-unit`,
`tests-integration`, `tests-browser-chrome`, `tests-browser-firefox` as separate
targets, plus `measures` (coverage + infection).

The shared composite action `.github/actions/docker-compose/action.yml` is
structurally identical to `pokenini-back`'s (no DB, no extra steps beyond the
standard build/pull/up/composer-install sequence).

## Job split

`ci_tests.yml` becomes 6 independent jobs, each using
`./.github/actions/docker-compose` (unchanged trigger conditions):

| Job | Runs |
|---|---|
| `unit-tests` | `phpunit tests/src/Unit --exclude-group="time-testing"` |
| `integration-tests` | `phpunit tests/src/Integration --exclude-group="time-testing"` |
| `browser-tests-chrome` | `docker compose exec -e PANTHER_SELENIUM_HOST=http://chrome:4444/wd/hub -e PANTHER_BROWSER_NAME=chrome -T php php vendor/bin/phpunit tests/src/Browser --exclude-group="time-testing"` — mirrors the Makefile's `tests-browser-chrome` target |
| `browser-tests-firefox` | Same, with `PANTHER_SELENIUM_HOST=http://firefox:4444/wd/hub` / `PANTHER_BROWSER_NAME=firefox` — mirrors `tests-browser-firefox` |
| `measures` | Coverage step then Infection, in that order, in the same job (unchanged from current steps 2-3, verbatim — still includes Browser tests in the coverage/Infection run, per the pre-existing-behavior note above) |
| `security` | `symfony security:check` (unchanged) |

Every job still spins up the full `docker-compose.yaml` stack via the shared composite
action — including `chrome`/`firefox` (with their healthchecks) even for jobs that
don't need them (`unit-tests`, `security`), same call as the other two repos.

## Docker image archive cache

Same mechanism as `pokenini-back`'s corrected design (reused directly, not the
original buggy first draft) and `pokenini-api`'s port of it:

- **Cache key**: `hashFiles('.docker/php/Dockerfile', '.docker/php/conf.d/**',
  '.docker/moco/Dockerfile', 'docker-compose.yaml')` plus host `UID`/`GID`. Confirmed
  by reading `.docker/php/Dockerfile`: the `php_dev` target does not `COPY`
  application source or `composer.lock` — only `.docker/php/conf.d/*.ini` files, a
  pinned `symfony-cli` binary, and Panther-related `ENV` declarations (no file copies
  tied to those). `vendor/` is bind-mounted and installed fresh per job via the
  composite action's `Composer install` step — untouched by this cache.
- **Archived images**: only the 3 *built* services — `php`, `moco.back`,
  `moco.matomo.gbl` (`docker compose config --images php moco.back moco.matomo.gbl`).
  Excluded: `redis`, `chrome`, `selenium/standalone-chromium`, `chrome`/`firefox`
  (both `selenium/standalone-*` pulled images), and `web` (nginx) — all pulled, not
  built.
- **Flow**, identical to the corrected `pokenini-back`/`pokenini-api` versions:
  - `actions/cache/restore@v5` (not the unified `actions/cache@v5`), `id:
    docker-images-cache`.
  - On hit: `docker load` the archive; skip Docker Hub login, Buildx setup, bake, and
    pull entirely.
  - On miss: existing login/buildx/bake/pull steps run unchanged, then `Start
    services` (`docker compose up --wait`, `--build` dropped — this is what actually
    builds and tags the 3 services under compose-generated names, since bake alone
    does not), then an explicit `actions/cache/save@v5` step (keyed off
    `steps.docker-images-cache.outputs.cache-primary-key`) saves the tar, scoped to
    the 3 service names.
  - `Composer install` runs unconditionally after, unchanged.
- `chrome`/`firefox` healthchecks and `php`'s `depends_on` on them are unaffected by
  this cache — those services are pulled, not built, so the cache-hit/miss branching
  never touches them; `Start services` (`docker compose up --wait`) still waits on
  their healthchecks the same way regardless of which cache path was taken.

### Non-goals

- No caching of `vendor/` (bind-mounted, installed fresh per job today — untouched).
- No dedicated `build-images` job with `needs:` fan-out — same best-effort
  shared-cache approach as the other two repos.
- No fix for the pre-existing CI/Makefile coverage-exclusion discrepancy described
  above.
- No change to which browser Panther defaults to when neither env var is set — that
  code path no longer runs in CI once `browser-tests-chrome`/`browser-tests-firefox`
  replace the old undifferentiated Browser-test execution.

## Testing / validation

YAML syntax check. Push the branch and confirm in the Actions UI: 6 jobs run in
parallel, each finishes with the same pass/fail result the current single job produces
for the corresponding step (except the two browser jobs, which are new coverage, not a
like-for-like split), and the second CI run on the same branch (no Dockerfile change)
shows a cache hit with no build steps executed on any of the 6 jobs.
