# CI Tests Split + Docker Image Archive Cache Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Split `pokenini-web`'s single-job `ci_tests.yml` into 6 parallel jobs (unit, integration, browser×2, measures, security) and add a Docker image archive cache to the shared composite action.

**Architecture:** Two independent, additive changes to GitHub Actions YAML — no application code touched. Task 1 replaces the one `allin` job in `ci_tests.yml` with 6 jobs: unit/integration/measures/security are a like-for-like split of the current steps (byte-identical `run:` commands except path narrowing), while `browser-tests-chrome`/`browser-tests-firefox` are new — the current single job runs `tests/src/Browser` implicitly (via the full `tests/src/` run) without selecting a browser; these two jobs explicitly run it against each Selenium container, mirroring the Makefile's `tests-browser-chrome`/`tests-browser-firefox` targets. Task 2 adds the Docker image archive cache to `./.github/actions/docker-compose/action.yml`, copying the design already implemented, reviewed, and bug-fixed in `pokenini-back` (see that repo's `docs/superpowers/plans/2026-08-10-ci-tests-split.md` Task 2) and already ported once to `pokenini-api` — save runs after `docker compose up` (not before), split `actions/cache/restore@v5`/`actions/cache/save@v5` steps, archive scoped to the 3 built services.

**Tech Stack:** GitHub Actions YAML, Docker Compose, `actions/cache@v5`, Symfony Panther, Selenium.

## Global Constraints

- No change to trigger conditions: `push` to `main`, `pull_request: ~`, `workflow_call` with `DOCKERHUB_USERNAME`/`DOCKERHUB_TOKEN` secrets — copied verbatim across all 6 new jobs.
- `PHP_CS_FIXER_IGNORE_ENV: 1` env var stays at workflow level.
- Neither `time-testing` nor `browser-testing` PHPUnit groups exist in `tests/src` today (confirmed via repo-wide grep during design — only `api-mocked-testing` is used) — keep both `--exclude-group` flags exactly as they are; do not remove them.
- The `php_dev` Docker Compose build target does not `COPY` application source or `composer.lock` — only `.docker/php/conf.d/*.ini` files and a pinned `symfony-cli` binary. `vendor/` is bind-mounted and installed fresh per job; it is untouched by this plan.
- `browser-tests-chrome` and `browser-tests-firefox` are the one deliberate scope change in this plan (confirmed with the user) — every other job must be behavior-preserving.
- Pushing the branch and watching the real Actions run is the final verification step, but must not be done without checking with the user first.

---

### Task 1: Split `ci_tests.yml` into 6 parallel jobs

**Files:**
- Modify: `.github/workflows/ci_tests.yml` (full rewrite of the `jobs:` section — `on:` and top-level `env:` blocks are unchanged)

**Interfaces:**
- Consumes: `./.github/actions/docker-compose` composite action (unchanged in this task).
- Produces: 6 job names (`unit-tests`, `integration-tests`, `browser-tests-chrome`, `browser-tests-firefox`, `measures`, `security`) that Task 2's composite-action change is exercised by.

- [ ] **Step 1: Read the current file to confirm no drift**

Run: `cat .github/workflows/ci_tests.yml`

Confirm it still matches this baseline `jobs:` section (if it doesn't, stop and check with the user before continuing):

```yaml
jobs:
  allin:
    name: All tests
    runs-on: ubuntu-latest
    steps:
      - name: Checkout
        uses: actions/checkout@v6
      - name: Prepare
        uses: ./.github/actions/docker-compose
      - name: PHPUnit / Run
        run: |
          docker compose exec -T php php vendor/bin/phpunit tests/src/ --exclude-group="time-testing"
      - name: Measures / Run Coverage
        run: |
          docker compose exec \
            -e XDEBUG_MODE=coverage \
            -T php php vendor/bin/phpunit \
            --exclude-group="browser-testing" \
            --coverage-clover=coverage.xml \
            --coverage-xml=build/coverage/coverage-xml \
            --log-junit=build/coverage/junit.xml
      - name: Measures / Composer install Infection
        shell: bash
        run: docker compose exec -T php composer install --working-dir=tools/infection --prefer-dist --no-progress --no-interaction
      - name: Measures / Run Infection
        run: |
          docker compose exec -T php php tools/infection/vendor/bin/infection \
            --threads=4 --no-progress \
            --skip-initial-tests --coverage=build/coverage \
            --min-msi=100 --min-covered-msi=100 \
            --filter=src
      - name: Security / Symfony Security Check
        run: |
          docker compose exec -T php symfony security:check
```

- [ ] **Step 2: Replace the `jobs:` section**

Replace everything from `jobs:` to the end of the file with:

```yaml
jobs:
  unit-tests:
    name: Unit Tests
    runs-on: ubuntu-latest
    steps:
      - name: Checkout
        uses: actions/checkout@v6
      - name: Prepare
        uses: ./.github/actions/docker-compose
      - name: PHPUnit / Run
        run: |
          docker compose exec -T php php vendor/bin/phpunit tests/src/Unit --exclude-group="time-testing"

  integration-tests:
    name: Integration Tests
    runs-on: ubuntu-latest
    steps:
      - name: Checkout
        uses: actions/checkout@v6
      - name: Prepare
        uses: ./.github/actions/docker-compose
      - name: PHPUnit / Run
        run: |
          docker compose exec -T php php vendor/bin/phpunit tests/src/Integration --exclude-group="time-testing"

  browser-tests-chrome:
    name: Browser Tests (Chrome)
    runs-on: ubuntu-latest
    steps:
      - name: Checkout
        uses: actions/checkout@v6
      - name: Prepare
        uses: ./.github/actions/docker-compose
      - name: PHPUnit / Run
        run: |
          docker compose exec \
            -e PANTHER_SELENIUM_HOST=http://chrome:4444/wd/hub \
            -e PANTHER_BROWSER_NAME=chrome \
            -T php php vendor/bin/phpunit tests/src/Browser --exclude-group="time-testing"

  browser-tests-firefox:
    name: Browser Tests (Firefox)
    runs-on: ubuntu-latest
    steps:
      - name: Checkout
        uses: actions/checkout@v6
      - name: Prepare
        uses: ./.github/actions/docker-compose
      - name: PHPUnit / Run
        run: |
          docker compose exec \
            -e PANTHER_SELENIUM_HOST=http://firefox:4444/wd/hub \
            -e PANTHER_BROWSER_NAME=firefox \
            -T php php vendor/bin/phpunit tests/src/Browser --exclude-group="time-testing"

  measures:
    name: Measures (Coverage & Mutation Testing)
    runs-on: ubuntu-latest
    steps:
      - name: Checkout
        uses: actions/checkout@v6
      - name: Prepare
        uses: ./.github/actions/docker-compose
      - name: Measures / Run Coverage
        run: |
          docker compose exec \
            -e XDEBUG_MODE=coverage \
            -T php php vendor/bin/phpunit \
            --exclude-group="browser-testing" \
            --coverage-clover=coverage.xml \
            --coverage-xml=build/coverage/coverage-xml \
            --log-junit=build/coverage/junit.xml
      - name: Measures / Composer install Infection
        shell: bash
        run: docker compose exec -T php composer install --working-dir=tools/infection --prefer-dist --no-progress --no-interaction
      - name: Measures / Run Infection
        run: |
          docker compose exec -T php php tools/infection/vendor/bin/infection \
            --threads=4 --no-progress \
            --skip-initial-tests --coverage=build/coverage \
            --min-msi=100 --min-covered-msi=100 \
            --filter=src

  security:
    name: Security
    runs-on: ubuntu-latest
    steps:
      - name: Checkout
        uses: actions/checkout@v6
      - name: Prepare
        uses: ./.github/actions/docker-compose
      - name: Security / Symfony Security Check
        run: |
          docker compose exec -T php symfony security:check
```

Note: `unit-tests`/`integration-tests`/`measures`/`security` are a like-for-like split
— every `run:` command is byte-identical to the original except the two `tests/src/` →
`tests/src/Unit`/`tests/src/Integration` narrowings. `browser-tests-chrome` and
`browser-tests-firefox` are new — there was no equivalent standalone step in the
original file (Browser tests previously ran as part of the undifferentiated
`tests/src/` run with no `PANTHER_SELENIUM_HOST`/`PANTHER_BROWSER_NAME` set); these two
jobs are built from the Makefile's `tests-browser-chrome`/`tests-browser-firefox`
targets (`docker compose exec -e PANTHER_SELENIUM_HOST=... -e PANTHER_BROWSER_NAME=...
php php vendor/bin/phpunit ... tests/src/Browser`), adapted to CI's `docker compose
exec -T` invocation style and keeping the (inert but consistently-applied)
`--exclude-group="time-testing"` flag the other test jobs carry.

- [ ] **Step 3: Validate YAML syntax**

Run: `python3 -c "import yaml; yaml.safe_load(open('.github/workflows/ci_tests.yml'))" && echo OK`
Expected: `OK`

- [ ] **Step 4: Confirm every original step survived the split**

Run: `git diff .github/workflows/ci_tests.yml` and check by eye against the Step 1
baseline: `unit-tests`/`integration-tests`/`measures`/`security` must be byte-identical
to the original except the two path narrowings described above. `browser-tests-chrome`
and `browser-tests-firefox` are new additions — confirm each uses the exact
`PANTHER_SELENIUM_HOST`/`PANTHER_BROWSER_NAME` values shown above (`http://chrome:4444/wd/hub`
+ `chrome`, and `http://firefox:4444/wd/hub` + `firefox` respectively — these must
match the service hostnames in `docker-compose.yaml`, not be swapped).

- [ ] **Step 5: Commit**

```bash
git add .github/workflows/ci_tests.yml
git commit -m "ci: split ci_tests.yml into parallel unit/integration/browser/measures/security jobs"
```

---

### Task 2: Add Docker image archive cache to the shared composite action

**Files:**
- Modify: `.github/actions/docker-compose/action.yml`

**Interfaces:**
- Consumes: nothing new — same composite action interface (`uses: ./.github/actions/docker-compose`, no inputs), called identically by all 6 jobs from Task 1.
- Produces: no new outputs; behavior change only (skips build on cache hit).

- [ ] **Step 1: Read the current file to confirm no drift**

Run: `cat .github/actions/docker-compose/action.yml`

Confirm it still matches this baseline (stop and check with the user if it doesn't):

```yaml
name: Docker Compose Pull Up And Check
description: Use to docker compose pull and up then checks if services are correctly running
author: Renaud Douze
runs:
  using: "composite"
  steps:
    - name: Copy .env file
      shell: bash
      run: cp .env.ci .env

    - name: Export host UID/GID for Docker build
      shell: bash
      run: |
        echo "APP_UID=$(id -u)" >> "$GITHUB_ENV"
        echo "APP_GID=$(id -g)" >> "$GITHUB_ENV"

    - name: Login to Docker Hub
      uses: docker/login-action@v4
      with:
        username: ${{ env.DOCKERHUB_USERNAME }}
        password: ${{ env.DOCKERHUB_TOKEN }}

    - name: Setup Docker Buildx
      uses: docker/setup-buildx-action@v4

    - name: Docker Buildx Bake
      uses: docker/bake-action@v7
      with:
        load: true
        set: |
          *.cache-to=type=gha,mode=max                                                                                
          *.cache-from=type=gha 
      
    - name: Pull images
      shell: bash
      run: docker compose pull --ignore-pull-failures || true

    - name: Start services
      shell: bash
      run: docker compose --verbose up --build --wait

    - name: Composer install
      shell: bash
      run: docker compose exec -T php composer install --prefer-dist --no-progress --no-interaction
```

- [ ] **Step 2: Replace the file contents**

```yaml
name: Docker Compose Pull Up And Check
description: Use to docker compose pull and up then checks if services are correctly running
author: Renaud Douze
runs:
  using: "composite"
  steps:
    - name: Copy .env file
      shell: bash
      run: cp .env.ci .env

    - name: Export host UID/GID for Docker build
      shell: bash
      run: |
        echo "APP_UID=$(id -u)" >> "$GITHUB_ENV"
        echo "APP_GID=$(id -g)" >> "$GITHUB_ENV"

    - name: Restore Docker images archive cache
      id: docker-images-cache
      uses: actions/cache/restore@v5
      with:
        path: /tmp/docker-images-cache/images.tar
        key: docker-images-${{ runner.os }}-${{ hashFiles('.docker/php/Dockerfile', '.docker/php/conf.d/**', '.docker/moco/Dockerfile', 'docker-compose.yaml') }}-${{ env.APP_UID }}-${{ env.APP_GID }}

    - name: Load cached Docker images
      if: steps.docker-images-cache.outputs.cache-hit == 'true'
      shell: bash
      run: docker load -i /tmp/docker-images-cache/images.tar

    - name: Login to Docker Hub
      if: steps.docker-images-cache.outputs.cache-hit != 'true'
      uses: docker/login-action@v4
      with:
        username: ${{ env.DOCKERHUB_USERNAME }}
        password: ${{ env.DOCKERHUB_TOKEN }}

    - name: Setup Docker Buildx
      if: steps.docker-images-cache.outputs.cache-hit != 'true'
      uses: docker/setup-buildx-action@v4

    - name: Docker Buildx Bake
      if: steps.docker-images-cache.outputs.cache-hit != 'true'
      uses: docker/bake-action@v7
      with:
        load: true
        set: |
          *.cache-to=type=gha,mode=max                                                                                
          *.cache-from=type=gha 

    - name: Pull images
      if: steps.docker-images-cache.outputs.cache-hit != 'true'
      shell: bash
      run: docker compose pull --ignore-pull-failures || true

    - name: Start services
      shell: bash
      run: docker compose --verbose up --wait

    - name: Save Docker images archive cache
      if: steps.docker-images-cache.outputs.cache-hit != 'true'
      shell: bash
      run: |
        mkdir -p /tmp/docker-images-cache
        docker save $(docker compose config --images php moco.back moco.matomo.gbl) -o /tmp/docker-images-cache/images.tar

    - name: Save Docker images archive cache (upload)
      if: steps.docker-images-cache.outputs.cache-hit != 'true'
      uses: actions/cache/save@v5
      with:
        path: /tmp/docker-images-cache/images.tar
        key: ${{ steps.docker-images-cache.outputs.cache-primary-key }}

    - name: Composer install
      shell: bash
      run: docker compose exec -T php composer install --prefer-dist --no-progress --no-interaction
```

Note the changes, copied from the corrected `pokenini-back`/`pokenini-api` versions:

1. The archive-save step (`docker save ...`) runs AFTER `Start services`, not before.
  `docker compose up` (with `--build` dropped) is what actually builds and tags the 3
  built services under the compose-generated names — `docker buildx bake` alone does
  not tag them, since none of the 3 declare an explicit `image:` key in
  `docker-compose.yaml`. Saving before `up` would find those 3 image names missing
  and fail on every cache-miss run (this was a Critical bug caught in `pokenini-back`'s
  first draft of this change).
2. The cache uses split `actions/cache/restore@v5` / `actions/cache/save@v5` steps, so
  the save happens as an ordinary step right after the tar is written — not deferred
  to a `post-if: success()` hook that a later step failing would silently skip. The
  save step reuses `steps.docker-images-cache.outputs.cache-primary-key` rather than
  re-typing the key expression, so the two can never drift out of sync.
3. `docker save` is scoped to the 3 built services only
  (`docker compose config --images php moco.back moco.matomo.gbl`) — `redis`,
  `chrome`, `firefox`, and `web` are pulled, not built, so archiving them wastes
  cache space for no benefit. This repo's `chrome`/`firefox` Selenium containers in
  particular are large pulled images that must NOT be included.
4. `Composer install` is unconditional and unchanged, same as before this task.

- [ ] **Step 3: Validate YAML syntax**

Run: `python3 -c "import yaml; yaml.safe_load(open('.github/actions/docker-compose/action.yml'))" && echo OK`
Expected: `OK`

- [ ] **Step 4: Validate the cache key's `hashFiles` globs and the archived service names actually exist**

Run: `ls .docker/php/Dockerfile .docker/php/conf.d/ .docker/moco/Dockerfile docker-compose.yaml`
Expected: all 4 paths exist.

Run: `grep -E '^\s+(php|moco\.back|moco\.matomo\.gbl):' docker-compose.yaml`
Expected: 3 matches — confirms the 3 service names passed to `docker compose config
--images` in Step 2 are exactly the build-service names in `docker-compose.yaml`.

- [ ] **Step 5: Commit**

```bash
git add .github/actions/docker-compose/action.yml
git commit -m "ci: cache Docker Compose images as an archive to skip rebuilds across parallel jobs"
```

---

### Task 3: Push and verify on a real CI run

**Files:** none (verification only).

**Interfaces:** none.

- [ ] **Step 1: Check in with the user before pushing**

This pushes a branch (and likely opens a PR) — confirm with the user first. Ask which
branch name they want if they haven't already specified one.

- [ ] **Step 2: Push the branch**

```bash
git push -u origin HEAD
```

- [ ] **Step 3: Open a PR (if the user wants one) and watch the run**

```bash
gh pr create --title "ci: split tests into parallel jobs, cache Docker images" --fill
gh run watch
```

Expected: 6 separate check runs appear (`Unit Tests`, `Integration Tests`,
`Browser Tests (Chrome)`, `Browser Tests (Firefox)`,
`Measures (Coverage & Mutation Testing)`, `Security`) instead of the single `All tests`
check. The 4 non-browser jobs should pass with the same results the current `allin`
job would have produced; the 2 browser jobs are new — if either fails, that's real
signal to investigate (not necessarily a plan defect — it may reveal that the
previously-undifferentiated Browser-test run was implicitly using only one browser's
behavior, and the other browser has a genuine incompatibility).

- [ ] **Step 4: Push a second, no-op commit (e.g. `git commit --allow-empty -m "ci: retrigger"`) and watch again**

Expected: in the Actions log for the `Prepare` step of each of the 6 jobs, the
`Restore Docker images archive cache` step reports a cache hit, and the
`Login to Docker Hub` / `Setup Docker Buildx` / `Docker Buildx Bake` / `Pull images`
steps are skipped.
