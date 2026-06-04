# Design — Firefox browser tests

**Date:** 2026-06-03
**Branch:** feature/parallel_make

## Context

The project already runs browser tests via Panther + Selenium Chrome (`selenium/standalone-chromium:4`). The goal is to run the same 11 test scenarios against Firefox, in parallel with Chrome, with no changes to the individual test files.

## Chosen approach

Env var parameterization + two parallel PHPUnit processes (one per browser). No Selenium Grid, no test file changes.

## Changes

### 1. `docker-compose.yaml`

Add a `firefox` service mirroring the existing `chrome` service:

```yaml
firefox:
  image: selenium/standalone-firefox:4
  shm_size: '2g'
  healthcheck:
    test: ["CMD-SHELL", "curl -f http://localhost:4444/status || exit 1"]
    interval: 5s
    timeout: 3s
    retries: 10
    start_period: 15s
```

Add `firefox` as a healthy dependency of the `php` service (alongside the existing `chrome` dependency).

### 2. `tests/src/Browser/AbstractBrowserTestCase.php`

Parameterize `getNewClient()` via two env vars:

| Variable | Chrome value | Firefox value |
|---|---|---|
| `PANTHER_SELENIUM_HOST` | `http://chrome:4444/wd/hub` | `http://firefox:4444/wd/hub` |
| `PANTHER_BROWSER_NAME` | `chrome` | `firefox` |

Both default to Chrome so existing invocations without env vars are unaffected.

```php
protected static function getNewClient(): Client
{
    $host = getenv('PANTHER_SELENIUM_HOST') ?: 'http://chrome:4444/wd/hub';
    $browserName = getenv('PANTHER_BROWSER_NAME') ?: 'chrome';

    $capabilities = $browserName === 'firefox'
        ? DesiredCapabilities::firefox()
        : DesiredCapabilities::chrome();
    $capabilities->setCapability('acceptInsecureCerts', true);

    return static::createPantherClient(
        ['browser' => static::SELENIUM],
        [],
        [
            'host'         => $host,
            'capabilities' => $capabilities,
        ],
    );
}
```

The 11 existing test files are unchanged.

### 3. `Makefile`

Replace the single `tests-browser` recipe with three targets:

```makefile
.PHONY: tests-browser-chrome
tests-browser-chrome: ## Execute browser tests against Chrome
    PANTHER_SELENIUM_HOST=http://chrome:4444/wd/hub PANTHER_BROWSER_NAME=chrome $(PHPUNIT) tests/src/Browser

.PHONY: tests-browser-firefox
tests-browser-firefox: ## Execute browser tests against Firefox
    PANTHER_SELENIUM_HOST=http://firefox:4444/wd/hub PANTHER_BROWSER_NAME=firefox $(PHPUNIT) tests/src/Browser

.PHONY: tests-browser
tests-browser: ## Execute browser tests (Chrome + Firefox in parallel)
tests-browser:
    $(call parallel_runner,tests-browser-chrome tests-browser-firefox,browser test suites)
```

`tb` alias and `make tests` are unchanged — `tests-browser` now runs both browsers.

## Data flow

```
make tests
  └─ parallel_runner: tests-unit | tests-integration | tests-browser
                                                          └─ parallel_runner: tests-browser-chrome | tests-browser-firefox
                                                              ├─ PHPUnit (PANTHER_SELENIUM_HOST=chrome) → selenium/standalone-chromium:4
                                                              └─ PHPUnit (PANTHER_SELENIUM_HOST=firefox) → selenium/standalone-firefox:4
```

## Files to create/modify

| File | Action |
|---|---|
| `docker-compose.yaml` | Add `firefox` service + dependency in `php` |
| `tests/src/Browser/AbstractBrowserTestCase.php` | Parameterize `getNewClient()` |
| `Makefile` | Split `tests-browser` into 3 targets |

## Out of scope

- No aliases `tbc` / `tbf` (not requested)
- No coverage changes (browser tests already excluded from coverage)
- No new fixtures or Moco changes
