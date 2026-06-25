# Firefox Browser Tests Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [x]`) syntax for tracking.

**Goal:** Run the 11 existing Panther browser test scenarios against both Chrome and Firefox in parallel.

**Architecture:** Add a `selenium/standalone-firefox:4` service to Docker Compose, parameterize `AbstractBrowserTestCase::getNewClient()` via env vars (`PANTHER_SELENIUM_HOST`, `PANTHER_BROWSER_NAME`), and split the Makefile `tests-browser` target into per-browser targets that run in parallel via the existing `parallel_runner` macro.

**Tech Stack:** Docker Compose, Selenium standalone images, PHP/Panther (`symfony/panther`), `facebook/webdriver` (`DesiredCapabilities`), GNU Make.

---

## Files

| Action | File |
|---|---|
| Modify | `docker-compose.yaml` |
| Modify | `tests/src/Browser/AbstractBrowserTestCase.php` |
| Modify | `Makefile` |

---

### Task 1: Add Firefox service to Docker Compose

**Files:**
- Modify: `docker-compose.yaml` (lines 34–36 for php dependency, after line 53 for new service)

- [x] **Step 1: Add the `firefox` service after the `chrome` service**

In `docker-compose.yaml`, replace:

```yaml
  chrome:
    image: selenium/standalone-chromium:4
    shm_size: '2g'
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost:4444/status"]
      interval: 5s
      timeout: 3s
      retries: 10
      start_period: 15s
```

With:

```yaml
  chrome:
    image: selenium/standalone-chromium:4
    shm_size: '2g'
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost:4444/status"]
      interval: 5s
      timeout: 3s
      retries: 10
      start_period: 15s

  firefox:
    image: selenium/standalone-firefox:4
    shm_size: '2g'
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost:4444/status"]
      interval: 5s
      timeout: 3s
      retries: 10
      start_period: 15s
```

- [x] **Step 2: Add `firefox` as a healthy dependency of the `php` service**

In `docker-compose.yaml`, replace:

```yaml
    depends_on:
      chrome:
        condition: service_healthy
```

With:

```yaml
    depends_on:
      chrome:
        condition: service_healthy
      firefox:
        condition: service_healthy
```

- [x] **Step 3: Validate the Docker Compose file**

```bash
docker compose config --quiet
```

Expected: no output, exit code 0.

- [x] **Step 4: Start the stack and verify Firefox comes up healthy**

```bash
make start
docker compose ps firefox
```

Expected: `firefox` row shows `healthy` in the Status column.

- [x] **Step 5: Commit**

```bash
git add docker-compose.yaml
git commit -m "feat: add selenium/standalone-firefox service to Docker Compose"
```

---

### Task 2: Parameterize AbstractBrowserTestCase

**Files:**
- Modify: `tests/src/Browser/AbstractBrowserTestCase.php`

- [x] **Step 1: Update `getNewClient()` to read browser from env vars**

Replace the entire `getNewClient()` method:

```php
    protected static function getNewClient(): Client
    {
        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability('acceptInsecureCerts', true);

        return static::createPantherClient(
            ['browser' => static::SELENIUM],
            [],
            [
                'host' => 'http://chrome:4444/wd/hub',
                'capabilities' => $capabilities,
            ],
        );
    }
```

With:

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

- [x] **Step 2: Verify Chrome tests still pass (default env — no vars set)**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Browser
```

Expected: all browser tests pass (same as before — no env vars set → defaults to Chrome).

- [x] **Step 3: Verify Firefox tests pass (env vars set manually)**

```bash
docker compose exec -e PANTHER_SELENIUM_HOST=http://firefox:4444/wd/hub -e PANTHER_BROWSER_NAME=firefox php php vendor/bin/phpunit tests/src/Browser
```

Expected: all 11 browser tests pass against Firefox.

- [x] **Step 4: Commit**

```bash
git add tests/src/Browser/AbstractBrowserTestCase.php
git commit -m "feat: parameterize browser tests via PANTHER_SELENIUM_HOST and PANTHER_BROWSER_NAME"
```

---

### Task 3: Update Makefile targets

**Files:**
- Modify: `Makefile` (lines 260–266)

- [x] **Step 1: Replace `tests-browser` with three targets**

In `Makefile`, replace:

```makefile
.PHONY: tests-browser
tests-browser: ## Execute browser tests for Web module
  $(PHPUNIT) tests/src/Browser
```

With:

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

Note: the indent in the Makefile recipe lines must be a **tab**, not spaces.

- [x] **Step 2: Run `make tests-browser` and verify both browsers run in parallel**

```bash
make tests-browser
```

Expected: `parallel_runner` output showing both `tests-browser-chrome` and `tests-browser-firefox` launching, both completing successfully.

- [x] **Step 3: Run `make tests` to verify full test suite still works**

```bash
make tests
```

Expected: unit, integration, and browser (Chrome + Firefox) all pass.

- [x] **Step 4: Commit**

```bash
git add Makefile
git commit -m "feat: run browser tests against Chrome and Firefox in parallel"
```
