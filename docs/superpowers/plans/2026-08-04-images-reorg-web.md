# Images Reorg — pokenini-web URL Updates Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Update `pokenini-web`'s image URL env vars and the two integration tests that hardcode URL fragments, to match `pokenini-icon`'s reorganized `images/` layout (`banner/large/`, `pokemon/big/`, `pokemon/small/`).

**Architecture:** `pokenini-web` never talks to `pokenini-icon` directly — it renders `<img>` URLs from three env-configured `sprintf`-style templates (`DEX_BANNER_URL`, `POKEMON_ICON_URL`, `POKEMON_IMAGE_URL`), consumed as opaque strings by Twig. No template or PHP code changes are needed — only the env-var values and the two tests that assert on the resulting URL strings.

**Tech Stack:** Symfony 8 env files (`.env*`), PHPUnit integration tests.

## Global Constraints

- This repo is independent from `pokenini-icon` — coordinate deploy ordering yourself: production points at `icon.pokenini.fr` (served by `pokenini-resources`), so these env-var changes should not reach production before `pokenini-resources` has re-synced from the reorganized `pokenini-icon` `images/`. See `docs/superpowers/specs/2026-08-04-images-reorg-design.md` in `pokenini-icon` for the full cross-repo design.
- No template or controller code changes — the URL templates are consumed as opaque configured strings (`config/packages/twig.yaml` wires `POKEMON_ICON_URL`/`POKEMON_IMAGE_URL`/`DEX_BANNER_URL` to Twig globals `pokemonIconUrl`/`pokemonImageUrl`/`dexBannerUrl`).
- Docker-only toolchain — run tests via `docker compose exec php ...` / `make`, per this repo's `CLAUDE.md`.

---

### Task 1: Update all `.env*` files' image URL templates

**Files:**
- Modify: `.env`, `.env.dev`, `.env.dev.local`, `.env.test`, `.env.test.local`, `.env.int`, `.env.prod`, `.env.ci`

**Interfaces:**
- Produces: `DEX_BANNER_URL` with `/banner/large/%1$s...`, `POKEMON_ICON_URL` with `/pokemon/small/%1$s/%2$s...`, `POKEMON_IMAGE_URL` with `/pokemon/big/%1$s/%2$s...` — consumed by Task 2's tests and by Twig templates (unchanged).

- [ ] **Step 1: Update `.env` (line 27-29)**

Change:
```
DEX_BANNER_URL='http://localhost:8083/banner/%1$s.png'
POKEMON_ICON_URL='http://localhost:8083/small/%1$s/%2$s.png'
POKEMON_IMAGE_URL='http://localhost:8083/big/%1$s/%2$s.png'
```
to:
```
DEX_BANNER_URL='http://localhost:8083/banner/large/%1$s.png'
POKEMON_ICON_URL='http://localhost:8083/pokemon/small/%1$s/%2$s.png'
POKEMON_IMAGE_URL='http://localhost:8083/pokemon/big/%1$s/%2$s.png'
```

- [ ] **Step 2: Update `.env.dev` (line 30-32)**

Change:
```
DEX_BANNER_URL='http://localhost:8083/banner/%1$s.png'
POKEMON_ICON_URL='http://localhost:8083/small/%1$s/%2$s.png'
POKEMON_IMAGE_URL='http://localhost:8083/big/%1$s/%2$s.png'
```
to:
```
DEX_BANNER_URL='http://localhost:8083/banner/large/%1$s.png'
POKEMON_ICON_URL='http://localhost:8083/pokemon/small/%1$s/%2$s.png'
POKEMON_IMAGE_URL='http://localhost:8083/pokemon/big/%1$s/%2$s.png'
```

- [ ] **Step 3: Update `.env.dev.local` (line 36-38)**

Change:
```
DEX_BANNER_URL='http://resources.pokenini.local:8083/banner/%1$s.png'
POKEMON_ICON_URL='http://resources.pokenini.local:8083/small/%1$s/%2$s.png'
POKEMON_IMAGE_URL='http://resources.pokenini.local:8083/big/%1$s/%2$s.png'
```
to:
```
DEX_BANNER_URL='http://resources.pokenini.local:8083/banner/large/%1$s.png'
POKEMON_ICON_URL='http://resources.pokenini.local:8083/pokemon/small/%1$s/%2$s.png'
POKEMON_IMAGE_URL='http://resources.pokenini.local:8083/pokemon/big/%1$s/%2$s.png'
```

- [ ] **Step 4: Update `.env.test` (line 27-29)**

Change:
```
DEX_BANNER_URL='https://icon.pokenini.fr/banner/%1$s.png'
POKEMON_ICON_URL='https://icon.pokenini.fr/small/%1$s/%2$s.png'
POKEMON_IMAGE_URL='https://icon.pokenini.fr/big/%1$s/%2$s.png'
```
to:
```
DEX_BANNER_URL='https://icon.pokenini.fr/banner/large/%1$s.png'
POKEMON_ICON_URL='https://icon.pokenini.fr/pokemon/small/%1$s/%2$s.png'
POKEMON_IMAGE_URL='https://icon.pokenini.fr/pokemon/big/%1$s/%2$s.png'
```

- [ ] **Step 5: Update `.env.test.local` (line 27-29)**

Change:
```
DEX_BANNER_URL='https://icon.pokenini.fr/banner/%1$s.png'
POKEMON_ICON_URL='https://icon.pokenini.fr/small/%1$s/%2$s.png'
POKEMON_IMAGE_URL='https://icon.pokenini.fr/big/%1$s/%2$s.png'
```
to:
```
DEX_BANNER_URL='https://icon.pokenini.fr/banner/large/%1$s.png'
POKEMON_ICON_URL='https://icon.pokenini.fr/pokemon/small/%1$s/%2$s.png'
POKEMON_IMAGE_URL='https://icon.pokenini.fr/pokemon/big/%1$s/%2$s.png'
```

- [ ] **Step 6: Update `.env.int` (line 27-29)**

Change:
```
DEX_BANNER_URL='https://icon.pokenini.fr/banner/%1$s.png'
POKEMON_ICON_URL='https://icon.pokenini.fr/small/%1$s/%2$s.png'
POKEMON_IMAGE_URL='https://icon.pokenini.fr/big/%1$s/%2$s.png'
```
to:
```
DEX_BANNER_URL='https://icon.pokenini.fr/banner/large/%1$s.png'
POKEMON_ICON_URL='https://icon.pokenini.fr/pokemon/small/%1$s/%2$s.png'
POKEMON_IMAGE_URL='https://icon.pokenini.fr/pokemon/big/%1$s/%2$s.png'
```

- [ ] **Step 7: Update `.env.prod` (line 27-29)**

Change:
```
DEX_BANNER_URL='http://localhost:8082/banner/%1$s.webp'
POKEMON_ICON_URL='http://localhost:8082/small/%1$s/%2$s.webp'
POKEMON_IMAGE_URL='http://localhost:8082/big/%1$s/%2$s.webp'
```
to:
```
DEX_BANNER_URL='http://localhost:8082/banner/large/%1$s.webp'
POKEMON_ICON_URL='http://localhost:8082/pokemon/small/%1$s/%2$s.webp'
POKEMON_IMAGE_URL='http://localhost:8082/pokemon/big/%1$s/%2$s.webp'
```

- [ ] **Step 8: Update `.env.ci` (line 27-29)**

Change:
```
DEX_BANNER_URL='http://localhost:8082/banner/%1$s.webp'
POKEMON_ICON_URL='http://localhost:8082/small/%1$s/%2$s.webp'
POKEMON_IMAGE_URL='http://localhost:8082/big/%1$s/%2$s.webp'
```
to:
```
DEX_BANNER_URL='http://localhost:8082/banner/large/%1$s.webp'
POKEMON_ICON_URL='http://localhost:8082/pokemon/small/%1$s/%2$s.webp'
POKEMON_IMAGE_URL='http://localhost:8082/pokemon/big/%1$s/%2$s.webp'
```

- [ ] **Step 9: Commit**

```bash
git add .env .env.dev .env.dev.local .env.test .env.test.local .env.int .env.prod .env.ci
git commit -m "$(cat <<'EOF'
Update image URL env vars for pokenini-icon's images/ reorg

EOF
)"
```

---

### Task 2: Update the two integration tests' hardcoded URL fragments

**Files:**
- Modify: `tests/src/Integration/Controller/Credits/CreditsTest.php`
- Modify: `tests/src/Integration/Controller/Album/Display/CommonTest.php`

**Interfaces:**
- Consumes: `.env.ci`'s `POKEMON_ICON_URL`/`POKEMON_IMAGE_URL` from Task 1 (integration tests run against `.env.ci`/`.env.test`, both updated in Task 1).

- [ ] **Step 1: Update `CreditsTest.php`'s four URL-fragment assertions**

In `tests/src/Integration/Controller/Credits/CreditsTest.php`, change:

```php
        $this->assertStringContainsString(
            '/small/regular/bulbasaur.png',
            (string) $bulbaSmallRegular->filter('img.credit-tile-image')->attr('src'),
        );
```
to:
```php
        $this->assertStringContainsString(
            '/pokemon/small/regular/bulbasaur.png',
            (string) $bulbaSmallRegular->filter('img.credit-tile-image')->attr('src'),
        );
```

Change:
```php
        $this->assertStringContainsString(
            '/small/shiny/bulbasaur.png',
            (string) $bulbaSmallShiny->filter('img.credit-tile-image')->attr('src'),
        );
```
to:
```php
        $this->assertStringContainsString(
            '/pokemon/small/shiny/bulbasaur.png',
            (string) $bulbaSmallShiny->filter('img.credit-tile-image')->attr('src'),
        );
```

Change:
```php
        $this->assertStringContainsString(
            '/big/regular/bulbasaur.png',
            (string) $bulbaBigRegular->filter('img.credit-tile-image')->attr('src'),
        );
```
to:
```php
        $this->assertStringContainsString(
            '/pokemon/big/regular/bulbasaur.png',
            (string) $bulbaBigRegular->filter('img.credit-tile-image')->attr('src'),
        );
```

Change:
```php
        $this->assertStringContainsString(
            '/big/shiny/bulbasaur.png',
            (string) $bulbaBigShiny->filter('img.credit-tile-image')->attr('src'),
        );
```
to:
```php
        $this->assertStringContainsString(
            '/pokemon/big/shiny/bulbasaur.png',
            (string) $bulbaBigShiny->filter('img.credit-tile-image')->attr('src'),
        );
```

- [ ] **Step 2: Update `CommonTest.php`'s URL assertion**

In `tests/src/Integration/Controller/Album/Display/CommonTest.php`, change:

```php
        $this->assertEquals(
            'https://icon.pokenini.fr/small/regular/bulbasaur.png',
            $crawler->filter('#bulbasaur .album-case-image img')->attr('src')
        );
```
to:
```php
        $this->assertEquals(
            'https://icon.pokenini.fr/pokemon/small/regular/bulbasaur.png',
            $crawler->filter('#bulbasaur .album-case-image img')->attr('src')
        );
```

- [ ] **Step 3: Run both tests**

Run:
```bash
docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Credits/CreditsTest.php
docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Album/Display/CommonTest.php
```
Expected: both green (`OK`), no failures.

- [ ] **Step 4: Commit**

```bash
git add tests/src/Integration/Controller/Credits/CreditsTest.php tests/src/Integration/Controller/Album/Display/CommonTest.php
git commit -m "$(cat <<'EOF'
Update credit/album tests' image URL assertions for pokemon/ reorg

EOF
)"
```
