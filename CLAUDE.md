# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

**Pokénini Web** — Symfony 8.0 frontend (PHP 8.4) for a Pokémon living/alternate/gender extended dex tracker. It communicates with a separate backend API (`pokenini-api`) over HTTP. No database — all data comes from that API.

## Commands

All commands run inside the Docker PHP container via `make`. The host has no PHP toolchain.

```bash
make start          # build images, install vendors, start containers, clear cache
make stop           # tear down containers
make bash           # open a shell in the PHP container
make composer c="require foo/bar"
```

### Tests

```bash
make tests          # all tests (unit + integration + browser)
make tests-unit     # Unit only
make tests-integration  # Integration only
make tb             # Browser (Panther) tests
make tests-api-mocked   # only tests in group api-mocked-testing
```

To run a **single test file or method** directly inside the container:

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Album/Display/CommonTest.php
docker compose exec php php vendor/bin/phpunit --filter testListEdit tests/src/Integration/Controller/Album/Display/CommonTest.php
```

Save HTML output for debugging: `file_put_contents('tests/last.html', $client->getCrawler()->html());`

### Quality (run all before pushing)

```bash
make quality        # infra-quality + code-quality
make code-quality   # editorconfig + jsonlint + phpcsfixer + phpmd + psalm + phpstan + deptrac + w3c
make phpcsfixer-fix # auto-fix code style
make security       # composer audit + local-php-security-checker
```

### Measures (coverage + mutation)

```bash
make measures       # coverage (100% required) + infection (100% MSI required)
```

### Baseline updates

```bash
# Psalm
docker compose exec php php tools/psalm/vendor/bin/psalm --show-info=false --no-cache --find-unused-psalm-suppress --no-suggestions --taint-analysis --set-baseline --update-baseline
# PHPStan
docker compose exec php php tools/phpstan/vendor/bin/phpstan --generate-baseline --memory-limit=-1
# PHPMD
docker compose exec php php tools/phpmd/vendor/bin/phpmd --update-baseline --generate-baseline src,tests text phpmd.ruleset.xml
```

## Architecture

### Request flow

```
Browser → Nginx → Symfony (PHP-FPM)
                      ↓
              Controller
                ↓         ↓
            Service    AlbumFilters (query param parsing)
                ↓
          Service\Back (HTTP client to pokenini-api)
                ↓
          ResponseObject (deserialized from JSON)
```

### Source layers (`src/`)

| Layer             | Role                                                                                                                                                                                                                         |
| ----------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `Controller/`     | One controller per feature page. Thin: delegates to Services, renders Twig.                                                                                                                                                  |
| `Service/`        | Orchestration layer. Composes Back services, handles null/exception from HTTP.                                                                                                                                               |
| `Service/Back/`   | All HTTP calls to the backend API. Extend `AbstractBackService` which handles auth headers (Bearer token + X-Provider), cafile, and logging.                                                                                 |
| `ResponseObject/` | Plain PHP objects populated by the Symfony Serializer from API JSON. No logic.                                                                                                                                               |
| `DTO/`            | Input/output data containers between Controller and Service, not tied to API shape.                                                                                                                                          |
| `AlbumFilters/`   | `FromRequest` parses query params into an `AlbumFilterBag` value object. `AlbumFilterBag` provides `toApiParams()` (maps short keys to long API keys, normalises to `string[][]`) and `toRouteParams()` (returns the mixed array for Twig/redirectToRoute). |
| `Security/`       | OAuth2 authenticators (Discord, Google, Fake for dev). `User` holds roles and `AccessToken`. `UserTokenService` exposes `getLoggedUserId()` which is `sha1($userIdentifier)` — this is the trainer ID used in URLs as `?t=`. |
| `Twig/`           | Twig extensions for app-specific helpers.                                                                                                                                                                                    |
| `Validator/`      | Custom Symfony constraints (e.g. `CatchStates`).                                                                                                                                                                             |

Layer dependencies are enforced by **Deptrac** (`deptrac.yaml`). Controllers must not reach into `Service\Back` directly.

### Routing

All routes are prefixed `/{_locale}` (`en` or `fr`). Routes are defined via PHP attributes on controllers (`#[Route(...)]`). The root `/` redirects to `app_home_index`.

### Authentication

- **Prod/staging**: Discord or Google OAuth2 (`knpu/oauth2-client-bundle`).
- **Dev only**: Fake authenticator. Use:
    - `http://localhost/fr/connect/f/c?t=admin` → admin session
    - `http://localhost/fr/connect/f/c?t=collector` → collector session
    - `http://localhost/fr/connect/f/c?t=trainer` → trainer session

### User roles

`ROLE_USER` → `ROLE_TRAINER` → `ROLE_COLLECTOR` → `ROLE_ADMIN`. Access control is in `config/packages/security.yaml`.

### HTTP mock server (Moco)

Integration and browser tests run against a **Moco** mock server (`moco.back` container) that replays fixtures from `tests/resources/moco/Back/`. Tests in group `api-mocked-testing` depend on it. Never mock the HTTP client in integration tests — use Moco fixtures.

### Infrastructure

| Service           | Purpose                                    |
| ----------------- | ------------------------------------------ |
| `php`             | PHP 8.4 FPM (dev image)                    |
| `web`             | Nginx, port 80/443                         |
| `redis`           | APCu-compatible cache adapter              |
| `moco.back`       | Moco mock for `pokenini-api`               |
| `moco.matomo.gbl` | Moco mock for Matomo analytics (port 8888) |

### Tools (separate Composer installs in `tools/`)

PHPStan, Psalm, PHP CS Fixer, PHPMD, Deptrac, Infection, PHPInsights, jsonlint, cachetool. Each has its own `vendor/` under `tools/<name>/`. Run via `make <toolname>` or directly via `docker compose exec php php tools/<name>/vendor/bin/<name>`.

### Test structure

```
tests/src/
    Unit/          # Pure PHPUnit mocks, no HTTP, no container
    Integration/   # WebTestCase with Moco HTTP mocks, group api-mocked-testing
    Browser/       # Symfony Panther (real browser), group api-mocked-testing
    Common/Traits/ # Shared assertion helpers (TestNavTrait, ResponseObjectTrait)
tests/Utils/     # GetUserToken helper for creating fake authenticated users
tests/resources/
    moco/Back/     # Moco JSON fixture files for backend API
    moco/Matomo/   # Moco JSON fixture files for Matomo
```

Every test class is `final`, `@internal`, uses `#[CoversClass(...)]`, and extends either `TestCase` (unit) or `WebTestCase` (integration).
