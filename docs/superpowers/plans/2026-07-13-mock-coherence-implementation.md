# Mock Data Coherence (home ↔ pokedex) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Every dex tile shown on the home page (`/album/dex`) must link to a pokedex page that actually exists (no 404s), and the progress numbers shown on the home tile must match the real data behind the pokedex page it links to.

**Architecture:** Moco fixtures under `tests/resources/moco/Back/responses/` model the backend. Four dex slugs are flagged "on home" in one or more `dex/*.json` files but have no `/album/{slug}` moco route at all (404 on click). Two more (`swordshield`, `home` in `dex/trainer.json`) do have a route, but the home tile's hand-typed `report` numbers don't match the real `pokemons[]` list behind that route. This plan (a) adds the four missing routes/fixtures, sized to reproduce the `report` numbers already promised by the existing dex-list fixtures (so those numbers become true instead of needing to change), and (b) recomputes the two genuinely-wrong `report` blocks (and their `admin.json` counterparts, which share the same underlying album fixtures) from the real data.

**Tech Stack:** PHPUnit (Symfony WebTestCase) integration tests, Moco JSON fixtures, a throwaway Python script (host-side, no Docker needed — pure JSON file generation).

## Global Constraints

- Fixture edits are plain JSON file edits — no PHP/Docker needed to write them. Only running the PHPUnit tests requires the container: `docker compose exec php php vendor/bin/phpunit <path>`.
- After **any** change to `tests/resources/moco/Back/moco.json`, run `make restart-mocks` before running tests — Moco loads its config at container start and does not hot-reload.
- The report-recompute formula (from the approved design doc `docs/superpowers/specs/2026-07-13-mock-coherence-design.md`): given a `pokemons[]` list, `total_caught` = count where `catch_state.slug == "yes"`; `total_uncaught` = count where `catch_state` is `null` or `slug == "no"`; `detail[]` lists only the catch states with a non-zero count, always including a `"no"` bucket when `total_uncaught > 0` and a `"yes"` bucket when `total_caught > 0` (this is what lets the twig progress bar render the uncaught mass — see `templates/common/_progress_bar_macros.html.twig:11` which substitutes `report.totalUncaught` for the literal `"no"` count); `total` = sum of all bucket counts.
- Catch-state label metadata (`name`/`french_name`/`color`) in any `detail[]` entry must match the canonical values in `tests/resources/moco/Back/responses/labels.json` (`yes`/`Oui`/`#66bb6a`, `totrade`/`À échanger`/`#ff9100`, `totransfer`/`à transférer`/`#ffd54f`, `tobreed`/`af. reproduire`/`#4fc3f7`, `toevolve`/`af. évoluer`/`#9575cd`, `no`/`Non`/`#e57373`).
- The scratchpad directory for throwaway scripts is `/tmp/claude-1000/-home-renaud-projects-pokenini-web/14bf70fc-ff13-44a4-b590-09a064608f41/scratchpad`. Nothing there is committed.

---

### Task 1: Write regression tests that prove the dead links

**Files:**
- Modify: `tests/src/Integration/Controller/Album/Dex/AlbumDexListTest.php`

**Interfaces:**
- Consumes: `App\Tests\Utils\GetUserToken::getFakeUserToken(string $identifier = '789465465489', string $providerName = 'TestProvider')`, `TestNavTrait::assertCountFilter`.
- Produces: nothing new consumed by later tasks — this is the regression proof that Task 2 and Task 3 make pass.

- [ ] **Step 1: Add a new test method that follows every home-tile link for the default trainer**

Add this method to `AlbumDexListTest`, right after `testAlbumDexList()` (after line 79):

```php
    public function testAlbumDexListTilesAreAllClickable(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken();
        $user->addTrainerRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/album/dex');

        $this->assertResponseIsSuccessful();

        $hrefs = array_unique($crawler->filter('.dex-item a')->extract(['href']));

        self::assertNotEmpty($hrefs);

        foreach ($hrefs as $href) {
            $client->request('GET', $href);
            self::assertTrue(
                $client->getResponse()->isSuccessful(),
                sprintf('Expected %s to be reachable, got status %d', $href, $client->getResponse()->getStatusCode())
            );
        }
    }
```

- [ ] **Step 2: Add a clickability assertion to the custom-slug test**

In `testAlbumDexListCustomDexLinksToUniqueSettingsSlug()` (currently ending at line 290), add two lines right before the closing `}`:

```php
        $this->assertEquals('/fr/album/home-shiny-custom', $album->filter('a')->attr('href'));
        $this->assertEquals('https://icon.pokenini.fr/banner/homeshiny.png', $album->filter('img')->attr('src'));

        $client->request('GET', '/fr/album/home-shiny-custom');
        self::assertTrue($client->getResponse()->isSuccessful());
    }
```

(This replaces the existing two `assertEquals` lines + closing brace with the same two lines plus the new clickability check.)

- [ ] **Step 3: Run the new tests and confirm they fail**

Run: `docker compose exec php php vendor/bin/phpunit --filter 'testAlbumDexListTilesAreAllClickable|testAlbumDexListCustomDexLinksToUniqueSettingsSlug' tests/src/Integration/Controller/Album/Dex/AlbumDexListTest.php`

Expected: FAIL — `testAlbumDexListTilesAreAllClickable` fails on the first broken slug (`/fr/album/homeshiny`, "got status 404"); `testAlbumDexListCustomDexLinksToUniqueSettingsSlug` fails on the new clickability assertion for `/fr/album/home-shiny-custom` ("got status 404").

- [ ] **Step 4: Commit**

```bash
git add tests/src/Integration/Controller/Album/Dex/AlbumDexListTest.php
git commit -m "test: prove home-tile links can 404 (homeshiny/homepogo/alpha/home-shiny-custom)"
```

---

### Task 2: Add the four missing album fixtures and moco routes

**Files:**
- Create: `tests/resources/moco/Back/responses/album/default/homeshiny.json`
- Create: `tests/resources/moco/Back/responses/album/default/homepogo.json`
- Create: `tests/resources/moco/Back/responses/album/default/alpha.json`
- Create: `tests/resources/moco/Back/responses/album/77de68daecd823babbb58edb1c8e14d7106e83bb/home-shiny-custom.json`
- Modify: `tests/resources/moco/Back/moco.json`
- Modify: `tests/resources/moco/Back/responses/dex/77de68daecd823babbb58edb1c8e14d7106e83bb.json`

**Interfaces:**
- Consumes: `tests/resources/moco/Back/responses/album/default/home.json`'s real `pokedex.pokemons[]` list (1477 entries) as a source of valid `Pokemon` objects to clone.
- Produces: three catch-all `/album/{slug}` moco routes (`homeshiny`, `homepogo`, `alpha`) and one bearer-scoped one (`home-shiny-custom`, only for trainer `77de68daecd823babbb58edb1c8e14d7106e83bb`), each resolving to a fixture whose `pokedex.report` exactly reproduces the numbers already promised in `dex/trainer.json` (and, for `home-shiny-custom`, in `dex/77de68…json`) — so Task 1's test passes and no other existing assertion needs to change.

- [ ] **Step 1: Write the fixture-generation script**

Create `/tmp/claude-1000/-home-renaud-projects-pokenini-web/14bf70fc-ff13-44a4-b590-09a064608f41/scratchpad/build_mock_fixtures.py`:

```python
import copy
import json

REPO = "/home/renaud/projects/pokenini-web"

CATCH_STATES = {
    "yes": {"name": "Yes", "french_name": "Oui", "slug": "yes", "color": "#66bb6a"},
    "totrade": {"name": "To trade", "french_name": "À échanger", "slug": "totrade", "color": "#ff9100"},
    "totransfer": {"name": "To transfer", "french_name": "à transférer", "slug": "totransfer", "color": "#ffd54f"},
    "tobreed": {"name": "To breed", "french_name": "af. reproduire", "slug": "tobreed", "color": "#4fc3f7"},
    "toevolve": {"name": "To evolve", "french_name": "af. évoluer", "slug": "toevolve", "color": "#9575cd"},
    "no": {"name": "No", "french_name": "Non", "slug": "no", "color": "#e57373"},
}
ORDER = ["yes", "totrade", "totransfer", "tobreed", "toevolve", "no"]

with open(f"{REPO}/tests/resources/moco/Back/responses/album/default/home.json", encoding="utf-8") as f:
    pool = json.load(f)["pokedex"]["pokemons"]


def take(n, offset):
    chosen = copy.deepcopy(pool[offset:offset + n])
    for p in chosen:
        p["catch_state"] = None
    return chosen


def build_report(pokemons):
    counts = {}
    for p in pokemons:
        cs = p["catch_state"]
        slug = cs["slug"] if cs else "no"
        counts[slug] = counts.get(slug, 0) + 1
    detail = [{"catch_state": CATCH_STATES[s], "count": counts[s]} for s in ORDER if counts.get(s, 0) > 0]
    return {
        "total": sum(counts.values()),
        "total_caught": counts.get("yes", 0),
        "total_uncaught": counts.get("no", 0),
        "detail": detail,
    }


def write_fixture(path, pokemons, dex_meta):
    report = build_report(pokemons)
    payload = {
        "pokedex": {
            "dex": dex_meta,
            "pokemons": pokemons,
            "report": report,
            "filtered_report": report,
        },
        "filters": [],
    }
    with open(f"{REPO}/{path}", "w", encoding="utf-8") as f:
        json.dump(payload, f, indent=2, ensure_ascii=False)
        f.write("\n")
    print(path, "->", report)


# homeshiny: 151 total, 1 yes, 150 uncaught (matches dex/trainer.json's existing homeshiny report)
homeshiny_pk = take(151, 0)
homeshiny_pk[0]["catch_state"] = CATCH_STATES["yes"]
write_fixture(
    "tests/resources/moco/Back/responses/album/default/homeshiny.json",
    homeshiny_pk,
    {
        "slug": "homeshiny", "original_slug": "homeshiny", "name": "Home Shiny", "french_name": "Home Chromatique",
        "flags": {"is_shiny": True, "is_private": False, "is_on_home": True, "is_display_form": True, "is_released": True, "is_premium": False, "is_custom": False},
        "display_template": "box", "region": None,
        "selection_rule": "p.isShiny",
        "description": "Shiny variants of every Pokémon transferable to Pokémon Home.",
        "french_description": "Variantes chromatiques de tous les Pokémon transférables sur Pokémon Home.",
        "version": "20230421.090014",
    },
)

# homepogo: 60 total, all caught (matches dex/trainer.json's existing homepogo report)
homepogo_pk = take(60, 151)
for p in homepogo_pk:
    p["catch_state"] = CATCH_STATES["yes"]
write_fixture(
    "tests/resources/moco/Back/responses/album/default/homepogo.json",
    homepogo_pk,
    {
        "slug": "homepogo", "original_slug": "homepogo", "name": "Home Pokemon Go", "french_name": "Home Pokemon Go",
        "flags": {"is_shiny": False, "is_private": False, "is_on_home": True, "is_display_form": False, "is_released": True, "is_premium": False, "is_custom": True},
        "display_template": "list-7", "region": None,
        "selection_rule": "p.custom.homepogo",
        "description": "Custom list of Pokémon transferred through Pokémon Go to Pokémon Home.",
        "french_description": "Liste personnalisée des Pokémon transférés depuis Pokémon Go vers Pokémon Home.",
        "version": "20230421.090014",
    },
)

# alpha: 50 total, 25 yes, 15 toevolve, 10 uncaught (matches dex/trainer.json's existing alpha report)
alpha_pk = take(50, 211)
for i, p in enumerate(alpha_pk):
    if i < 25:
        p["catch_state"] = CATCH_STATES["yes"]
    elif i < 40:
        p["catch_state"] = CATCH_STATES["toevolve"]
write_fixture(
    "tests/resources/moco/Back/responses/album/default/alpha.json",
    alpha_pk,
    {
        "slug": "alpha", "original_slug": "alpha", "name": "Alpha", "french_name": "Baron",
        "flags": {"is_shiny": False, "is_private": False, "is_on_home": True, "is_display_form": True, "is_released": True, "is_premium": True, "is_custom": False},
        "display_template": "list-3", "region": None,
        "selection_rule": "p.isAlpha",
        "description": "Alpha Pokémon forms.",
        "french_description": "Formes Alpha des Pokémon.",
        "version": "20230421.090014",
    },
)

# home-shiny-custom (trainer "3"): 4 total, 1 yes, 3 uncaught (matches dex/77de68...json's existing report)
custom_pk = take(4, 261)
custom_pk[0]["catch_state"] = CATCH_STATES["yes"]
write_fixture(
    "tests/resources/moco/Back/responses/album/77de68daecd823babbb58edb1c8e14d7106e83bb/home-shiny-custom.json",
    custom_pk,
    {
        "slug": "homeshiny", "original_slug": "homeshiny", "name": "Home Shiny Custom", "french_name": "Home Chromatique Perso",
        "flags": {"is_shiny": True, "is_private": False, "is_on_home": True, "is_display_form": True, "is_released": True, "is_premium": False, "is_custom": True},
        "display_template": "box", "region": None,
        "selection_rule": "p.isShiny and p.custom.trainer3",
        "description": "Trainer 3's custom shiny Home selection.",
        "french_description": "Sélection chromatique personnalisée du dresseur 3 pour Home.",
        "version": "20230421.090014",
    },
)
```

- [ ] **Step 2: Run the script**

Run: `mkdir -p "/home/renaud/projects/pokenini-web/tests/resources/moco/Back/responses/album/77de68daecd823babbb58edb1c8e14d7106e83bb" && python3 "/tmp/claude-1000/-home-renaud-projects-pokenini-web/14bf70fc-ff13-44a4-b590-09a064608f41/scratchpad/build_mock_fixtures.py"`

Expected output (four lines, one per fixture):
```
tests/resources/moco/Back/responses/album/default/homeshiny.json -> {'total': 151, 'total_caught': 1, 'total_uncaught': 150, 'detail': [{'catch_state': {'name': 'Yes', 'french_name': 'Oui', 'slug': 'yes', 'color': '#66bb6a'}, 'count': 1}, {'catch_state': {'name': 'No', 'french_name': 'Non', 'slug': 'no', 'color': '#e57373'}, 'count': 150}]}
tests/resources/moco/Back/responses/album/default/homepogo.json -> {'total': 60, 'total_caught': 60, 'total_uncaught': 0, 'detail': [{'catch_state': {'name': 'Yes', 'french_name': 'Oui', 'slug': 'yes', 'color': '#66bb6a'}, 'count': 60}]}
tests/resources/moco/Back/responses/album/default/alpha.json -> {'total': 50, 'total_caught': 25, 'total_uncaught': 10, 'detail': [{'catch_state': {'name': 'Yes', 'french_name': 'Oui', 'slug': 'yes', 'color': '#66bb6a'}, 'count': 25}, {'catch_state': {'name': 'To evolve', 'french_name': 'af. évoluer', 'slug': 'toevolve', 'color': '#9575cd'}, 'count': 15}, {'catch_state': {'name': 'No', 'french_name': 'Non', 'slug': 'no', 'color': '#e57373'}, 'count': 10}]}
tests/resources/moco/Back/responses/album/77de68daecd823babbb58edb1c8e14d7106e83bb/home-shiny-custom.json -> {'total': 4, 'total_caught': 1, 'total_uncaught': 3, 'detail': [{'catch_state': {'name': 'Yes', 'french_name': 'Oui', 'slug': 'yes', 'color': '#66bb6a'}, 'count': 1}, {'catch_state': {'name': 'No', 'french_name': 'Non', 'slug': 'no', 'color': '#e57373'}, 'count': 3}]}
```

Confirm each printed `report` matches the corresponding existing `dex/*.json` entry exactly (this is what guarantees no other test assertion needs to change).

- [ ] **Step 3: Add the three catch-all moco routes**

In `tests/resources/moco/Back/moco.json`, find this block (the `/album/mega` catch-all route):

```json
  {
    "request": {
      "uri": {
        "match": "/album/mega"
      },
      "headers": {
        "X-Provider": {
          "match": ".*"
        },
        "authorization": {
          "match": "Bearer .*"
        }
      }
    },
    "response": {
      "file": "/var/moco/responses/album/default/mega.json"
    }
  },
```

Replace it with the same block followed by three new ones:

```json
  {
    "request": {
      "uri": {
        "match": "/album/mega"
      },
      "headers": {
        "X-Provider": {
          "match": ".*"
        },
        "authorization": {
          "match": "Bearer .*"
        }
      }
    },
    "response": {
      "file": "/var/moco/responses/album/default/mega.json"
    }
  },
  {
    "request": {
      "uri": {
        "match": "/album/homeshiny"
      },
      "headers": {
        "X-Provider": {
          "match": ".*"
        },
        "authorization": {
          "match": "Bearer .*"
        }
      }
    },
    "response": {
      "file": "/var/moco/responses/album/default/homeshiny.json"
    }
  },
  {
    "request": {
      "uri": {
        "match": "/album/homepogo"
      },
      "headers": {
        "X-Provider": {
          "match": ".*"
        },
        "authorization": {
          "match": "Bearer .*"
        }
      }
    },
    "response": {
      "file": "/var/moco/responses/album/default/homepogo.json"
    }
  },
  {
    "request": {
      "uri": {
        "match": "/album/alpha"
      },
      "headers": {
        "X-Provider": {
          "match": ".*"
        },
        "authorization": {
          "match": "Bearer .*"
        }
      }
    },
    "response": {
      "file": "/var/moco/responses/album/default/alpha.json"
    }
  },
```

- [ ] **Step 4: Add the trainer-scoped moco route for home-shiny-custom**

In the same file, find this block (trainer `"3"`'s `/album/dex` route):

```json
  {
    "request": {
      "uri": "/album/dex",
      "headers": {
        "accept": "application/json",
        "X-Provider": {
          "match": ".*"
        },
        "authorization": {
          "match": "Bearer 77de68daecd823babbb58edb1c8e14d7106e83bb"
        }
      }
    },
    "response": {
      "file": "/var/moco/responses/dex/77de68daecd823babbb58edb1c8e14d7106e83bb.json"
    }
  },
```

Replace it with the same block followed by a new one:

```json
  {
    "request": {
      "uri": "/album/dex",
      "headers": {
        "accept": "application/json",
        "X-Provider": {
          "match": ".*"
        },
        "authorization": {
          "match": "Bearer 77de68daecd823babbb58edb1c8e14d7106e83bb"
        }
      }
    },
    "response": {
      "file": "/var/moco/responses/dex/77de68daecd823babbb58edb1c8e14d7106e83bb.json"
    }
  },
  {
    "request": {
      "uri": {
        "match": "/album/home-shiny-custom"
      },
      "headers": {
        "X-Provider": {
          "match": ".*"
        },
        "authorization": {
          "match": "Bearer 77de68daecd823babbb58edb1c8e14d7106e83bb"
        }
      }
    },
    "response": {
      "file": "/var/moco/responses/album/77de68daecd823babbb58edb1c8e14d7106e83bb/home-shiny-custom.json"
    }
  },
```

- [ ] **Step 5: Fix the missing "no" bucket in trainer "3"'s existing report**

In `tests/resources/moco/Back/responses/dex/77de68daecd823babbb58edb1c8e14d7106e83bb.json`, the `report.detail` array currently only has a `"yes"` entry even though `total_uncaught` is `3` — without a `"no"` bucket, the progress bar can't render that uncaught mass (see the Global Constraints note on `_progress_bar_macros.html.twig:11`). Replace:

```json
    "report": {
      "total": 4,
      "total_caught": 1,
      "total_uncaught": 3,
      "detail": [
        {
          "catch_state": { "name": "Yes", "french_name": "Oui", "slug": "yes", "color": "#66bb6a" },
          "count": 1
        }
      ]
    }
```

with:

```json
    "report": {
      "total": 4,
      "total_caught": 1,
      "total_uncaught": 3,
      "detail": [
        {
          "catch_state": { "name": "Yes", "french_name": "Oui", "slug": "yes", "color": "#66bb6a" },
          "count": 1
        },
        {
          "catch_state": { "name": "No", "french_name": "Non", "slug": "no", "color": "#e57373" },
          "count": 3
        }
      ]
    }
```

- [ ] **Step 6: Validate moco wiring and restart mocks**

Run: `tools/check-moco-refs/check_moco_refs.sh tests/resources/moco/Back/moco.json tests/resources/moco/Back`
Expected: no errors (every route's `file` exists, no orphaned fixture files newly introduced).

Run: `cd "/home/renaud/projects/pokenini-web" && docker compose restart moco.back`
Expected: `moco.back` restarts successfully.

- [ ] **Step 7: Rerun Task 1's tests and confirm they now pass**

Run: `docker compose exec php php vendor/bin/phpunit --filter 'testAlbumDexListTilesAreAllClickable|testAlbumDexListCustomDexLinksToUniqueSettingsSlug' tests/src/Integration/Controller/Album/Dex/AlbumDexListTest.php`

Expected: PASS (2 tests, 0 failures).

- [ ] **Step 8: Run the full AlbumDexListTest file to confirm no other assertion broke**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Album/Dex/AlbumDexListTest.php`

Expected: PASS (all methods green — `testAlbumDexList`, `testAlbumDexListFrench`, `testAlbumDexListEnglish` and the others should be unaffected since the new fixtures were built to reproduce the exact `report` numbers those tests already assert).

- [ ] **Step 9: Commit**

```bash
git add tests/resources/moco/Back/moco.json \
  tests/resources/moco/Back/responses/album/default/homeshiny.json \
  tests/resources/moco/Back/responses/album/default/homepogo.json \
  tests/resources/moco/Back/responses/album/default/alpha.json \
  tests/resources/moco/Back/responses/album/77de68daecd823babbb58edb1c8e14d7106e83bb/home-shiny-custom.json \
  tests/resources/moco/Back/responses/dex/77de68daecd823babbb58edb1c8e14d7106e83bb.json
git commit -m "fix: add missing album fixtures/routes for homeshiny, homepogo, alpha, home-shiny-custom"
```

---

### Task 3: Fix swordshield/home report numbers in dex/trainer.json (and the tests that hardcode them)

**Files:**
- Modify: `tests/resources/moco/Back/responses/dex/trainer.json`
- Modify: `tests/src/Integration/Controller/Album/Dex/AlbumDexListTest.php`

**Interfaces:**
- Consumes: real pokemon counts from `tests/resources/moco/Back/responses/album/default/swordshield.json` (935 entries, all `catch_state: null`) and `tests/resources/moco/Back/responses/album/default/home.json` (1477 entries, all `catch_state: null`) — already established as fact in the design doc's research.
- Produces: nothing consumed by later tasks.

- [ ] **Step 1: Recompute the swordshield report block**

In `tests/resources/moco/Back/responses/dex/trainer.json`, replace:

```json
    "report": {
      "total": 400,
      "total_caught": 50,
      "total_uncaught": 140,
      "detail": [
        { "catch_state": { "name": "Yes", "french_name": "Oui", "slug": "yes", "color": "#66bb6a" }, "count": 50 },
        { "catch_state": { "name": "To trade", "french_name": "À échanger", "slug": "totrade", "color": "#ff9100" }, "count": 30 },
        { "catch_state": { "name": "To transfer", "french_name": "à transférer", "slug": "totransfer", "color": "#ffd54f" }, "count": 50 },
        { "catch_state": { "name": "To breed", "french_name": "af. reproduire", "slug": "tobreed", "color": "#4fc3f7" }, "count": 60 },
        { "catch_state": { "name": "To evolve", "french_name": "af. évoluer", "slug": "toevolve", "color": "#9575cd" }, "count": 70 },
        { "catch_state": { "name": "No", "french_name": "Non", "slug": "no", "color": "#e57373" }, "count": 140 }
      ]
    }
```

with:

```json
    "report": {
      "total": 935,
      "total_caught": 0,
      "total_uncaught": 935,
      "detail": [
        { "catch_state": { "name": "No", "french_name": "Non", "slug": "no", "color": "#e57373" }, "count": 935 }
      ]
    }
```

- [ ] **Step 2: Recompute the home report block and fix its non-canonical catch-state labels**

In the same file, replace:

```json
    "report": {
      "total": 151,
      "total_caught": 88,
      "total_uncaught": 63,
      "detail": [
        { "catch_state": { "name": "Caught", "french_name": "Attrapé", "slug": "yes", "color": "#198754" }, "count": 88 },
        { "catch_state": { "name": "Not caught", "french_name": "Pas attrapé", "slug": "no", "color": "#dc3545" }, "count": 63 }
      ]
    }
```

with:

```json
    "report": {
      "total": 1477,
      "total_caught": 0,
      "total_uncaught": 1477,
      "detail": [
        { "catch_state": { "name": "No", "french_name": "Non", "slug": "no", "color": "#e57373" }, "count": 1477 }
      ]
    }
```

- [ ] **Step 3: Update the dependent test assertions in AlbumDexListTest.php**

In `testAlbumDexList()`:

Replace:
```php
        $firstAlbum = $crawler->filter('.dex-item')->first();
        $this->assertEquals('Épée, Bouclier 12.5% 35%', $firstAlbum->text());
        $this->assertEquals('/fr/album/swordshield', $firstAlbum->filter('a')->attr('href'));
        $this->assertEquals('https://icon.pokenini.fr/banner/swordshield.png', $firstAlbum->filter('img')->attr('src'));

        $secondAlbum = $crawler->filter('.dex-item')->eq(2);
        $this->assertEquals('Home Chromatique 0.66% 99.34%', $secondAlbum->text());
        $this->assertEquals('/fr/album/homeshiny', $secondAlbum->filter('a')->attr('href'));
        $this->assertEquals('https://icon.pokenini.fr/banner/homeshiny.png', $secondAlbum->filter('img')->attr('src'));

        $this->assertCountFilter($crawler, 5, '.dex-item .progress');
        $this->assertCountFilter($crawler, 14, '.dex-item .progress-bar');

        $homeAlbum = $crawler->filter('.dex-item')->eq(1);
        $this->assertEquals('41.72%', $homeAlbum->filter('.progress-bar.catch-state-no')->text());
        $this->assertEquals('58.28%', $homeAlbum->filter('.progress-bar.catch-state-yes')->text());
```

with:
```php
        $firstAlbum = $crawler->filter('.dex-item')->first();
        $this->assertEquals('Épée, Bouclier 100%', $firstAlbum->text());
        $this->assertEquals('/fr/album/swordshield', $firstAlbum->filter('a')->attr('href'));
        $this->assertEquals('https://icon.pokenini.fr/banner/swordshield.png', $firstAlbum->filter('img')->attr('src'));

        $secondAlbum = $crawler->filter('.dex-item')->eq(2);
        $this->assertEquals('Home Chromatique 0.66% 99.34%', $secondAlbum->text());
        $this->assertEquals('/fr/album/homeshiny', $secondAlbum->filter('a')->attr('href'));
        $this->assertEquals('https://icon.pokenini.fr/banner/homeshiny.png', $secondAlbum->filter('img')->attr('src'));

        $this->assertCountFilter($crawler, 5, '.dex-item .progress');
        $this->assertCountFilter($crawler, 8, '.dex-item .progress-bar');

        $homeAlbum = $crawler->filter('.dex-item')->eq(1);
        $this->assertEquals('100%', $homeAlbum->filter('.progress-bar.catch-state-no')->text());
```

In `testAlbumDexListFrench()`, replace:
```php
        $firstAlbum = $crawler->filter('.dex-item')->first();
        $this->assertEquals('Épée, Bouclier 12.5% 35%', $firstAlbum->text());
        $this->assertEquals('/fr/album/swordshield', $firstAlbum->filter('a')->attr('href'));
```
with:
```php
        $firstAlbum = $crawler->filter('.dex-item')->first();
        $this->assertEquals('Épée, Bouclier 100%', $firstAlbum->text());
        $this->assertEquals('/fr/album/swordshield', $firstAlbum->filter('a')->attr('href'));
```

In `testAlbumDexListEnglish()`, replace:
```php
        $firstAlbum = $crawler->filter('.dex-item')->first();
        $this->assertEquals('Sword, Shield 12.5% 35%', $firstAlbum->text());
        $this->assertEquals('/en/album/swordshield', $firstAlbum->filter('a')->attr('href'));
```
with:
```php
        $firstAlbum = $crawler->filter('.dex-item')->first();
        $this->assertEquals('Sword, Shield 100%', $firstAlbum->text());
        $this->assertEquals('/en/album/swordshield', $firstAlbum->filter('a')->attr('href'));
```

- [ ] **Step 4: Run the full test file**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Album/Dex/AlbumDexListTest.php`

Expected: PASS (all methods green).

- [ ] **Step 5: Commit**

```bash
git add tests/resources/moco/Back/responses/dex/trainer.json \
  tests/src/Integration/Controller/Album/Dex/AlbumDexListTest.php
git commit -m "fix: sync swordshield/home report numbers with their real album fixtures"
```

---

### Task 4: Fix admin.json's report numbers to match the same shared album fixtures

**Files:**
- Modify: `tests/resources/moco/Back/responses/dex/admin.json`

**Interfaces:**
- Consumes: real pokemon counts from `album/default/redgreenblueyellow.json` (151, all null), `album/default/goldsilvercrystal.json` (278, all null), `album/default/home.json` (1477, all null), `album/default/mega.json` (50, all null), and the two new fixtures from Task 2 (`homeshiny.json` → 151/1/150, `alpha.json` → 50/25/15/10).
- Produces: nothing consumed by later tasks. No existing test asserts admin.json's report numbers (verified: no integration or browser test references the `admin` fake-auth identity together with `.dex-item`/`.progress-bar` selectors), so this task carries no risk of breaking existing assertions.

`admin.json` shares the exact same catch-all `/album/{slug}` moco routes as `trainer.json` for `redgreenblueyellow`, `goldsilvercrystal`, `home`, `homeshiny`, `alpha`, and `mega` — so whatever `report` numbers it shows on its home tiles for those slugs must match the one real album fixture behind that slug, otherwise clicking through for the admin trainer will show different data than the tile promised (the exact bug class this whole plan exists to fix).

- [ ] **Step 1: Fix redgreenblueyellow (real total 151, all uncaught)**

Replace:
```json
    "report": {
      "total": 151,
      "total_caught": 90,
      "total_uncaught": 20,
      "detail": [
        { "catch_state": { "name": "Yes", "french_name": "Oui", "slug": "yes", "color": "#66bb6a" }, "count": 90 },
        { "catch_state": { "name": "To trade", "french_name": "À échanger", "slug": "totrade", "color": "#ff9100" }, "count": 8 },
        { "catch_state": { "name": "To transfer", "french_name": "à transférer", "slug": "totransfer", "color": "#ffd54f" }, "count": 8 },
        { "catch_state": { "name": "To breed", "french_name": "af. reproduire", "slug": "tobreed", "color": "#4fc3f7" }, "count": 10 },
        { "catch_state": { "name": "To evolve", "french_name": "af. évoluer", "slug": "toevolve", "color": "#9575cd" }, "count": 15 },
        { "catch_state": { "name": "No", "french_name": "Non", "slug": "no", "color": "#e57373" }, "count": 20 }
      ]
    }
```
with:
```json
    "report": {
      "total": 151,
      "total_caught": 0,
      "total_uncaught": 151,
      "detail": [
        { "catch_state": { "name": "No", "french_name": "Non", "slug": "no", "color": "#e57373" }, "count": 151 }
      ]
    }
```

- [ ] **Step 2: Fix goldsilvercrystal (real total 278, all uncaught)**

Replace:
```json
    "report": {
      "total": 251,
      "total_caught": 140,
      "total_uncaught": 40,
      "detail": [
        { "catch_state": { "name": "Yes", "french_name": "Oui", "slug": "yes", "color": "#66bb6a" }, "count": 140 },
        { "catch_state": { "name": "To trade", "french_name": "À échanger", "slug": "totrade", "color": "#ff9100" }, "count": 11 },
        { "catch_state": { "name": "To transfer", "french_name": "à transférer", "slug": "totransfer", "color": "#ffd54f" }, "count": 15 },
        { "catch_state": { "name": "To breed", "french_name": "af. reproduire", "slug": "tobreed", "color": "#4fc3f7" }, "count": 20 },
        { "catch_state": { "name": "To evolve", "french_name": "af. évoluer", "slug": "toevolve", "color": "#9575cd" }, "count": 25 },
        { "catch_state": { "name": "No", "french_name": "Non", "slug": "no", "color": "#e57373" }, "count": 40 }
      ]
    }
```
with:
```json
    "report": {
      "total": 278,
      "total_caught": 0,
      "total_uncaught": 278,
      "detail": [
        { "catch_state": { "name": "No", "french_name": "Non", "slug": "no", "color": "#e57373" }, "count": 278 }
      ]
    }
```

- [ ] **Step 3: Fix home (real total 1477, all uncaught — same shared fixture as trainer.json's home)**

Replace:
```json
    "report": {
      "total": 151,
      "total_caught": 68,
      "total_uncaught": 30,
      "detail": [
        { "catch_state": { "name": "Yes", "french_name": "Oui", "slug": "yes", "color": "#66bb6a" }, "count": 68 },
        { "catch_state": { "name": "To trade", "french_name": "À échanger", "slug": "totrade", "color": "#ff9100" }, "count": 8 },
        { "catch_state": { "name": "To transfer", "french_name": "à transférer", "slug": "totransfer", "color": "#ffd54f" }, "count": 10 },
        { "catch_state": { "name": "To breed", "french_name": "af. reproduire", "slug": "tobreed", "color": "#4fc3f7" }, "count": 15 },
        { "catch_state": { "name": "To evolve", "french_name": "af. évoluer", "slug": "toevolve", "color": "#9575cd" }, "count": 20 },
        { "catch_state": { "name": "No", "french_name": "Non", "slug": "no", "color": "#e57373" }, "count": 30 }
      ]
    }
```
with:
```json
    "report": {
      "total": 1477,
      "total_caught": 0,
      "total_uncaught": 1477,
      "detail": [
        { "catch_state": { "name": "No", "french_name": "Non", "slug": "no", "color": "#e57373" }, "count": 1477 }
      ]
    }
```

- [ ] **Step 4: Fix homeshiny (must match the new shared fixture from Task 2: 151/1/150)**

Replace:
```json
    "report": {
      "total": 151,
      "total_caught": 0,
      "total_uncaught": 140,
      "detail": [
        { "catch_state": { "name": "Yes", "french_name": "Oui", "slug": "yes", "color": "#66bb6a" }, "count": 0 },
        { "catch_state": { "name": "To trade", "french_name": "À échanger", "slug": "totrade", "color": "#ff9100" }, "count": 1 },
        { "catch_state": { "name": "To transfer", "french_name": "à transférer", "slug": "totransfer", "color": "#ffd54f" }, "count": 2 },
        { "catch_state": { "name": "To breed", "french_name": "af. reproduire", "slug": "tobreed", "color": "#4fc3f7" }, "count": 3 },
        { "catch_state": { "name": "To evolve", "french_name": "af. évoluer", "slug": "toevolve", "color": "#9575cd" }, "count": 5 },
        { "catch_state": { "name": "No", "french_name": "Non", "slug": "no", "color": "#e57373" }, "count": 140 }
      ]
    }
```
with:
```json
    "report": {
      "total": 151,
      "total_caught": 1,
      "total_uncaught": 150,
      "detail": [
        { "catch_state": { "name": "Yes", "french_name": "Oui", "slug": "yes", "color": "#66bb6a" }, "count": 1 },
        { "catch_state": { "name": "No", "french_name": "Non", "slug": "no", "color": "#e57373" }, "count": 150 }
      ]
    }
```

- [ ] **Step 5: Fix alpha (must match the new shared fixture from Task 2: 50/25/15/10)**

Replace:
```json
    "report": {
      "total": 50,
      "total_caught": 15,
      "total_uncaught": 10,
      "detail": [
        { "catch_state": { "name": "Yes", "french_name": "Oui", "slug": "yes", "color": "#66bb6a" }, "count": 15 },
        { "catch_state": { "name": "To trade", "french_name": "À échanger", "slug": "totrade", "color": "#ff9100" }, "count": 5 },
        { "catch_state": { "name": "To transfer", "french_name": "à transférer", "slug": "totransfer", "color": "#ffd54f" }, "count": 5 },
        { "catch_state": { "name": "To breed", "french_name": "af. reproduire", "slug": "tobreed", "color": "#4fc3f7" }, "count": 7 },
        { "catch_state": { "name": "To evolve", "french_name": "af. évoluer", "slug": "toevolve", "color": "#9575cd" }, "count": 8 },
        { "catch_state": { "name": "No", "french_name": "Non", "slug": "no", "color": "#e57373" }, "count": 10 }
      ]
    }
```
with:
```json
    "report": {
      "total": 50,
      "total_caught": 25,
      "total_uncaught": 10,
      "detail": [
        { "catch_state": { "name": "Yes", "french_name": "Oui", "slug": "yes", "color": "#66bb6a" }, "count": 25 },
        { "catch_state": { "name": "To evolve", "french_name": "af. évoluer", "slug": "toevolve", "color": "#9575cd" }, "count": 15 },
        { "catch_state": { "name": "No", "french_name": "Non", "slug": "no", "color": "#e57373" }, "count": 10 }
      ]
    }
```

- [ ] **Step 6: Fix mega (real total 50, all uncaught)**

Replace:
```json
    "report": {
      "total": 46,
      "total_caught": 21,
      "total_uncaught": 5,
      "detail": [
        { "catch_state": { "name": "Yes", "french_name": "Oui", "slug": "yes", "color": "#66bb6a" }, "count": 21 },
        { "catch_state": { "name": "To trade", "french_name": "À échanger", "slug": "totrade", "color": "#ff9100" }, "count": 5 },
        { "catch_state": { "name": "To transfer", "french_name": "à transférer", "slug": "totransfer", "color": "#ffd54f" }, "count": 5 },
        { "catch_state": { "name": "To breed", "french_name": "af. reproduire", "slug": "tobreed", "color": "#4fc3f7" }, "count": 5 },
        { "catch_state": { "name": "To evolve", "french_name": "af. évoluer", "slug": "toevolve", "color": "#9575cd" }, "count": 5 },
        { "catch_state": { "name": "No", "french_name": "Non", "slug": "no", "color": "#e57373" }, "count": 5 }
      ]
    }
```
with:
```json
    "report": {
      "total": 50,
      "total_caught": 0,
      "total_uncaught": 50,
      "detail": [
        { "catch_state": { "name": "No", "french_name": "Non", "slug": "no", "color": "#e57373" }, "count": 50 }
      ]
    }
```

- [ ] **Step 7: Validate JSON and moco wiring**

Run: `python3 -c "import json; json.load(open('tests/resources/moco/Back/responses/dex/admin.json'))" && echo OK`
Expected: `OK` (confirms the file is still valid JSON after six manual edits).

Run: `tools/check-moco-refs/check_moco_refs.sh tests/resources/moco/Back/moco.json tests/resources/moco/Back`
Expected: no errors.

- [ ] **Step 8: Commit**

```bash
git add tests/resources/moco/Back/responses/dex/admin.json
git commit -m "fix: sync admin.json report numbers with the shared album fixtures"
```

---

### Task 5: Full verification pass

**Files:** none (verification only).

**Interfaces:** none.

- [ ] **Step 1: Run the full Album controller test suite**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Album/`

Expected: PASS, 0 failures, 0 errors.

- [ ] **Step 2: Run jsonlint and the moco-refs check across the whole fixture tree**

Run: `docker compose exec php php tools/jsonlint/vendor/bin/jsonlint tests/resources/moco/Back/moco.json tests/resources/moco/Back/responses/dex/trainer.json tests/resources/moco/Back/responses/dex/admin.json tests/resources/moco/Back/responses/dex/77de68daecd823babbb58edb1c8e14d7106e83bb.json tests/resources/moco/Back/responses/album/default/homeshiny.json tests/resources/moco/Back/responses/album/default/homepogo.json tests/resources/moco/Back/responses/album/default/alpha.json tests/resources/moco/Back/responses/album/77de68daecd823babbb58edb1c8e14d7106e83bb/home-shiny-custom.json`

Expected: no syntax errors reported for any file.

Run: `tools/check-moco-refs/check_moco_refs.sh tests/resources/moco/Back/moco.json tests/resources/moco/Back`

Expected: no errors.

- [ ] **Step 3: Run the full integration suite to catch any unrelated fixture consumer**

Run: `docker compose exec php php vendor/bin/phpunit --group api-mocked-testing`

Expected: PASS. If anything outside `Album/` fails, it means another test reads one of the six edited `dex/*.json`/`admin.json` entries or the `trainer.json` swordshield/home blocks in a way not yet accounted for — investigate before considering the plan done.

- [ ] **Step 4: Manual sanity check in the browser (optional but recommended)**

Run: `make start` (if not already running), then visit `http://localhost/fr/connect/f/c?t=admin` followed by `http://localhost/fr/album/dex` — confirm all six admin home tiles are clickable and none 404. Repeat for the default dev session (`http://localhost/fr/album/dex` without the fake-auth redirect, or via `?t=` override) to see the `homeshiny`/`homepogo`/`alpha` tiles working.
