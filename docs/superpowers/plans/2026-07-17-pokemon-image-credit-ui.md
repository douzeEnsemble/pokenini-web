# Pokémon Image Credit System (pokenini-web) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Show a small info badge next to every Pokémon sprite (small/big × regular/shiny) linking to its image credit when one exists, and add a `/credits` page listing every distinct source, linked from the footer.

**Architecture:** Credit data already arrives embedded on every Pokémon payload from pokenini-back (`small_regular_credit`/`small_shiny_credit`/`big_regular_credit`/`big_shiny_credit`, each `null` or `{name, url}`). This plan threads those fields through the two existing ResponseObject hierarchies (`Common\Pokemon`/`PokemonData` used by Album/Election-candidates, and `Election\TopPokemon`/`TopPokemonInfo` used by the election-top display), renders a Bootstrap-tooltip badge from inside the shared `_image_macros.html.twig` macros (so every existing call site gets it for free), and migrates `Election/_top.html.twig` onto that shared macro (removing a pre-existing manual `<img>` duplication, since its data shape didn't previously support it). A new `/credits` page follows the existing `Controller → Service → Service\Back` pattern used by `GetLabelsService`.

**Tech Stack:** Symfony 8 / PHP ≥ 8.5, Twig, Symfony Serializer (attribute-driven deserialization), Symfony Cache, Bootstrap 5 (tooltips already wired in `base.html.twig`), PHPUnit + Panther.

**Related documents:** Design spec at `docs/superpowers/specs/2026-07-17-pokemon-image-credit-system-design.md`. This is the third and final repo-scoped plan (pokenini-api ✅ merged → pokenini-back ✅ merged → **pokenini-web**). Both upstream repos already ship the JSON shapes this plan consumes verbatim — see `pokenini-api/docs/superpowers/plans/2026-07-17-pokemon-image-credit-system.md` and `pokenini-back/docs/superpowers/plans/2026-07-17-pokemon-image-credit-endpoint.md`.

**Design decision confirmed with the user:** `Election/_top.html.twig` will be migrated onto the shared `_image_macros.html.twig` macro (Task 4) rather than having the credit badge bolted onto its existing manual `<img>` code — this was flagged as more work than originally scoped once the data-shape mismatch was discovered, and the user chose the full migration.

## Global Constraints

- `declare(strict_types=1)` in every PHP file.
- ResponseObjects: `final class`, constructor-promoted `#[SerializedName]` properties, **no default values** — every constructor call (test or production) must pass every argument explicitly, matching this repo's existing `PokemonData`/`TopPokemonInfo` convention.
- Controller: `final class`.
- Service (`App\Service\*`, caching layer) and `App\Service\Back\*` (HTTP layer): NOT `final` (PHPUnit mocking), following `GetLabelsService`'s existing pattern in both namespaces.
- Every test class: `/** @internal */` + `#[CoversClass(TargetClass::class)]`.
- Integration tests hitting pokenini-back: `#[Group('api-mocked-testing')]`, Moco fixtures under `tests/resources/moco/Back/responses/`, routing table `tests/resources/moco/Back/moco.json`.
- Translations live in `translations/messages+intl-icu.en.yaml` / `.fr.yaml` only (no other locale/domain files exist).
- 100% code coverage and 100% Mutation Score Index required (`make coverage`, `make infection` — see project `CLAUDE.md` for exact commands, e.g. `make tests`, `make code-quality`). PHPStan level 9, Psalm strict, Deptrac clean, PHP CS Fixer clean, W3C validation clean.
- All commands run via `make`/`docker compose exec php` inside the Docker container.

---

### Task 1: Thread credit data through `Common\Pokemon`/`PokemonData`

**Files:**
- Create: `src/ResponseObject/Common/PokemonCredit.php`
- Modify: `src/ResponseObject/Common/PokemonData.php`
- Modify: `src/ResponseObject/Common/Pokemon.php`
- Modify: `tests/src/Unit/ResponseObject/Common/PokemonDataTest.php`
- Modify: `tests/src/Integration/ResponseObject/Common/PokemonTest.php`
- Modify: `tests/src/Common/Traits/ResponseObjectTrait.php`

**Interfaces:**
- Produces: `App\ResponseObject\Common\PokemonCredit` (`getName(): string`, `getUrl(): string`).
- Produces: `PokemonData::getSmallRegularCredit()/getSmallShinyCredit()/getBigRegularCredit()/getBigShinyCredit(): ?PokemonCredit`.
- Produces: `Pokemon::getPokemonSmallRegularCredit()/getPokemonSmallShinyCredit()/getPokemonBigRegularCredit()/getPokemonBigShinyCredit(): ?PokemonCredit` — these are what Task 3's macro will call as `item.pokemonSmallRegularCredit` etc.

- [ ] **Step 1: Write the failing `PokemonCredit` unit test**

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Common;

use App\ResponseObject\Common\PokemonCredit;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(PokemonCredit::class)]
final class PokemonCreditTest extends TestCase
{
    public function testGetters(): void
    {
        $credit = new PokemonCredit(name: 'PokéSprite', url: 'https://github.com/msikma/pokesprite');

        $this->assertSame('PokéSprite', $credit->getName());
        $this->assertSame('https://github.com/msikma/pokesprite', $credit->getUrl());
    }
}
```

Save as `tests/src/Unit/ResponseObject/Common/PokemonCreditTest.php`.

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/ResponseObject/Common/PokemonCreditTest.php`
Expected: FAIL — class does not exist.

- [ ] **Step 2: Implement `PokemonCredit`**

```php
<?php

declare(strict_types=1);

namespace App\ResponseObject\Common;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class PokemonCredit
{
    public function __construct(
        #[SerializedName('name')]
        private readonly string $name,
        #[SerializedName('url')]
        private readonly string $url,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getUrl(): string
    {
        return $this->url;
    }
}
```

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/ResponseObject/Common/PokemonCreditTest.php`
Expected: PASS.

- [ ] **Step 3: Extend `PokemonData` — write failing test first**

Modify `tests/src/Unit/ResponseObject/Common/PokemonDataTest.php`: add `use App\ResponseObject\Common\PokemonCredit;` to the imports, then append 4 named arguments to **all 3** existing `new PokemonData(...)` calls (`testGetters()`, `testNullableFamilyLead()`, `testNullableLabels()`), right after `gameBundles: ...,`:

```php
            smallRegularCredit: null,
            smallShinyCredit: null,
            bigRegularCredit: null,
            bigShinyCredit: null,
        );
```

Add a new test method:

```php
    public function testCredits(): void
    {
        $smallRegular = new PokemonCredit(name: 'PokéSprite', url: 'https://github.com/msikma/pokesprite');
        $bigShiny = new PokemonCredit(name: 'PokemonDB', url: 'https://pokemondb.net/sprites/bulbasaur-shiny');

        $data = new PokemonData(
            slug: 'bulbasaur',
            labels: new PokemonLabels(
                name: 'Bulbasaur',
                frenchName: 'Bulbizarre',
                simplifiedName: 'Bulbasaur',
                simplifiedFrenchName: 'Bulbizarre',
                formsLabel: '',
                formsFrenchLabel: '',
            ),
            nationalDexNumber: 1,
            regionalDexNumber: null,
            icon: 'bulbasaur',
            familyOrder: 0,
            familyLead: null,
            originalGameBundle: null,
            orderNumber: '0001-0001-000',
            gameBundles: new GameBundlesGroup(normal: [], shiny: []),
            smallRegularCredit: $smallRegular,
            smallShinyCredit: null,
            bigRegularCredit: null,
            bigShinyCredit: $bigShiny,
        );

        $this->assertSame($smallRegular, $data->getSmallRegularCredit());
        $this->assertNull($data->getSmallShinyCredit());
        $this->assertNull($data->getBigRegularCredit());
        $this->assertSame($bigShiny, $data->getBigShinyCredit());
    }
```

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/ResponseObject/Common/PokemonDataTest.php`
Expected: FAIL — `Too few arguments to function PokemonData::__construct()`.

- [ ] **Step 4: Implement the `PokemonData` extension**

In `src/ResponseObject/Common/PokemonData.php`, add 4 new constructor parameters after `gameBundles`:

```php
        #[SerializedName('game_bundles')]
        private readonly GameBundlesGroup $gameBundles,
        #[SerializedName('small_regular_credit')]
        private readonly ?PokemonCredit $smallRegularCredit,
        #[SerializedName('small_shiny_credit')]
        private readonly ?PokemonCredit $smallShinyCredit,
        #[SerializedName('big_regular_credit')]
        private readonly ?PokemonCredit $bigRegularCredit,
        #[SerializedName('big_shiny_credit')]
        private readonly ?PokemonCredit $bigShinyCredit,
    ) {}
```

Add 4 new getters after `getGameBundlesShiny()`:

```php
    public function getSmallRegularCredit(): ?PokemonCredit
    {
        return $this->smallRegularCredit;
    }

    public function getSmallShinyCredit(): ?PokemonCredit
    {
        return $this->smallShinyCredit;
    }

    public function getBigRegularCredit(): ?PokemonCredit
    {
        return $this->bigRegularCredit;
    }

    public function getBigShinyCredit(): ?PokemonCredit
    {
        return $this->bigShinyCredit;
    }
```

(`PokemonCredit` is in the same `App\ResponseObject\Common` namespace — no new `use` needed.)

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/ResponseObject/Common/PokemonDataTest.php`
Expected: PASS.

- [ ] **Step 5: Fix the now-broken `ResponseObjectTrait` stub builder**

`tests/src/Common/Traits/ResponseObjectTrait.php`'s `getStubPokemon()` constructs `PokemonData` with named arguments too — add the same 4 named arguments after `gameBundles: new GameBundlesGroup(normal: [], shiny: []),`:

```php
                gameBundles: new GameBundlesGroup(normal: [], shiny: []),
                smallRegularCredit: null,
                smallShinyCredit: null,
                bigRegularCredit: null,
                bigShinyCredit: null,
            ),
```

- [ ] **Step 6: Write the failing `Pokemon` (Common) integration test**

Modify `tests/src/Integration/ResponseObject/Common/PokemonTest.php`: add 4 new JSON keys to the `pokemon` object in **all 3** existing inline JSON literals (`testDeserialize`, `testDeserializeWithNullValues`, `testDeserializeWithNullForms`), right after `"game_bundles": { ... },`:

```json
                    "small_regular_credit": null,
                    "small_shiny_credit": null,
                    "big_regular_credit": null,
                    "big_shiny_credit": null
```

(Adjust trailing commas as needed — this is the last set of keys before the closing `}` of the `pokemon` object in each literal, so no comma follows the last one.)

In `testDeserialize()` specifically, set two of the four to non-null values instead of `null`, to prove real deserialization:

```json
                    "small_regular_credit": { "name": "PokéSprite", "url": "https://github.com/msikma/pokesprite" },
                    "small_shiny_credit": null,
                    "big_regular_credit": null,
                    "big_shiny_credit": { "name": "PokemonDB", "url": "https://pokemondb.net/sprites/charizard-mega-y-shiny" }
```

And add matching assertions at the end of `testDeserialize()`:

```php
        $this->assertNotNull($object->getPokemonSmallRegularCredit());
        $this->assertSame('PokéSprite', $object->getPokemonSmallRegularCredit()->getName());
        $this->assertSame('https://github.com/msikma/pokesprite', $object->getPokemonSmallRegularCredit()->getUrl());
        $this->assertNull($object->getPokemonSmallShinyCredit());
        $this->assertNull($object->getPokemonBigRegularCredit());
        $this->assertNotNull($object->getPokemonBigShinyCredit());
        $this->assertSame('PokemonDB', $object->getPokemonBigShinyCredit()->getName());
```

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/ResponseObject/Common/PokemonTest.php`
Expected: FAIL — either a missing-constructor-argument denormalization error (before Step 7) or (if run after Step 4 alone) a call to an undefined method `getPokemonSmallRegularCredit()`.

- [ ] **Step 7: Implement the `Pokemon` (Common) extension**

In `src/ResponseObject/Common/Pokemon.php`, add 4 new delegating getters after `getPokemonIcon()`:

```php
    public function getPokemonSmallRegularCredit(): ?PokemonCredit
    {
        return $this->pokemon->getSmallRegularCredit();
    }

    public function getPokemonSmallShinyCredit(): ?PokemonCredit
    {
        return $this->pokemon->getSmallShinyCredit();
    }

    public function getPokemonBigRegularCredit(): ?PokemonCredit
    {
        return $this->pokemon->getBigRegularCredit();
    }

    public function getPokemonBigShinyCredit(): ?PokemonCredit
    {
        return $this->pokemon->getBigShinyCredit();
    }
```

(`PokemonCredit` is in the same namespace as `Pokemon` — no new `use` needed.)

- [ ] **Step 8: Run tests and verify they pass**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/ResponseObject/Common/PokemonTest.php tests/src/Unit/ResponseObject/Common/`
Expected: PASS.

Run: `make tests-unit` to confirm no other unrelated test broke from the `ResponseObjectTrait` change (many test files use `getStubPokemon()`).

- [ ] **Step 9: Commit**

```bash
git add src/ResponseObject/Common/PokemonCredit.php src/ResponseObject/Common/PokemonData.php src/ResponseObject/Common/Pokemon.php tests/src/Unit/ResponseObject/Common/PokemonCreditTest.php tests/src/Unit/ResponseObject/Common/PokemonDataTest.php tests/src/Integration/ResponseObject/Common/PokemonTest.php tests/src/Common/Traits/ResponseObjectTrait.php
git commit -m "Thread image credit fields through Common\\Pokemon/PokemonData"
```

---

### Task 2: Thread credit data through `Election\TopPokemon`/`TopPokemonInfo`

**Files:**
- Modify: `src/ResponseObject/Election/TopPokemonInfo.php`
- Modify: `src/ResponseObject/Election/TopPokemon.php`
- Modify: `tests/src/Unit/ResponseObject/Election/TopPokemonInfoTest.php`
- Modify: `tests/src/Common/Traits/ResponseObjectTrait.php`

**Interfaces:**
- Consumes: `App\ResponseObject\Common\PokemonCredit` from Task 1 (reused as-is — no separate `TopPokemonCredit` class needed, since the shape is identical and this class isn't part of the `TopPokemon*`-prefixed family's own serialization boundary in a way that requires a dedicated type).
- Produces: `TopPokemonInfo::getSmallRegularCredit()/getSmallShinyCredit()/getBigRegularCredit()/getBigShinyCredit(): ?PokemonCredit`.
- Produces: `TopPokemon::getPokemonIcon()/getPokemonName()/getPokemonFrenchName(): string` (**new** flattening getters, not previously needed since `_top.html.twig` used to access `item.pokemon.icon` directly) plus `getPokemonSmallRegularCredit()/getPokemonSmallShinyCredit()/getPokemonBigRegularCredit()/getPokemonBigShinyCredit(): ?PokemonCredit` — these are what Task 4's macro-based `_top.html.twig` will call.

`TopPokemonInfo`'s constructor uses **positional** arguments (not named) — both in `TopPokemonInfoTest.php` and in `ResponseObjectTrait::getStubTopPokemon()`. New parameters must be appended at the end.

- [ ] **Step 1: Write the failing `TopPokemonInfo` unit test**

Modify `tests/src/Unit/ResponseObject/Election/TopPokemonInfoTest.php`: append 4 trailing `null,` arguments to **all 3** existing `new TopPokemonInfo(...)` calls, right after `$gameBundles,`:

```php
            $gameBundles,
            null,
            null,
            null,
            null,
        );
```

Add a new test method:

```php
    public function testCredits(): void
    {
        $labels = new TopPokemonLabels('Bulbasaur', 'Bulbizarre', 'Bulbasaur', 'Bulbizarre', null, null);
        $gameBundles = new TopPokemonGameBundles([], []);
        $smallRegular = new PokemonCredit(name: 'PokéSprite', url: 'https://github.com/msikma/pokesprite');
        $bigShiny = new PokemonCredit(name: 'PokemonDB', url: 'https://pokemondb.net/sprites/bulbasaur-shiny');

        $object = new TopPokemonInfo(
            'bulbasaur',
            $labels,
            1,
            null,
            'bulbasaur',
            0,
            null,
            null,
            null,
            $gameBundles,
            $smallRegular,
            null,
            null,
            $bigShiny,
        );

        $this->assertSame($smallRegular, $object->getSmallRegularCredit());
        $this->assertNull($object->getSmallShinyCredit());
        $this->assertNull($object->getBigRegularCredit());
        $this->assertSame($bigShiny, $object->getBigShinyCredit());
    }
```

Add `use App\ResponseObject\Common\PokemonCredit;` to the file's imports.

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/ResponseObject/Election/TopPokemonInfoTest.php`
Expected: FAIL — too many/few arguments.

- [ ] **Step 2: Implement the `TopPokemonInfo` extension**

In `src/ResponseObject/Election/TopPokemonInfo.php`, add `use App\ResponseObject\Common\PokemonCredit;`, then 4 new constructor parameters after `gameBundles`:

```php
        #[SerializedName('game_bundles')]
        private readonly TopPokemonGameBundles $gameBundles,
        #[SerializedName('small_regular_credit')]
        private readonly ?PokemonCredit $smallRegularCredit,
        #[SerializedName('small_shiny_credit')]
        private readonly ?PokemonCredit $smallShinyCredit,
        #[SerializedName('big_regular_credit')]
        private readonly ?PokemonCredit $bigRegularCredit,
        #[SerializedName('big_shiny_credit')]
        private readonly ?PokemonCredit $bigShinyCredit,
    ) {}
```

Add 4 new getters after `getGameBundles()`:

```php
    public function getSmallRegularCredit(): ?PokemonCredit
    {
        return $this->smallRegularCredit;
    }

    public function getSmallShinyCredit(): ?PokemonCredit
    {
        return $this->smallShinyCredit;
    }

    public function getBigRegularCredit(): ?PokemonCredit
    {
        return $this->bigRegularCredit;
    }

    public function getBigShinyCredit(): ?PokemonCredit
    {
        return $this->bigShinyCredit;
    }
```

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/ResponseObject/Election/TopPokemonInfoTest.php`
Expected: PASS.

- [ ] **Step 3: Fix the now-broken `ResponseObjectTrait::getStubTopPokemon()`**

In `tests/src/Common/Traits/ResponseObjectTrait.php`, append 4 trailing `null,` to the `new TopPokemonInfo(...)` call inside `getStubTopPokemon()`, right after `new TopPokemonGameBundles([], []),`:

```php
                new TopPokemonGameBundles([], []),
                null,
                null,
                null,
                null,
            ),
```

- [ ] **Step 4: Write a failing test for `TopPokemon`'s new flattening getters**

Create `tests/src/Unit/ResponseObject/Election/TopPokemonTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Election;

use App\ResponseObject\Common\PokemonCredit;
use App\ResponseObject\Election\TopPokemon;
use App\ResponseObject\Election\TopPokemonGameBundles;
use App\ResponseObject\Election\TopPokemonInfo;
use App\ResponseObject\Election\TopPokemonLabels;
use App\ResponseObject\Election\TopPokemonScore;
use App\ResponseObject\Election\TopPokemonTypes;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(TopPokemon::class)]
final class TopPokemonTest extends TestCase
{
    public function testFlattenedGettersAndCredits(): void
    {
        $smallRegular = new PokemonCredit(name: 'PokéSprite', url: 'https://github.com/msikma/pokesprite');
        $bigShiny = new PokemonCredit(name: 'PokemonDB', url: 'https://pokemondb.net/sprites/bulbasaur-shiny');

        $info = new TopPokemonInfo(
            'bulbasaur',
            new TopPokemonLabels('Bulbasaur', 'Bulbizarre', 'Bulbasaur', 'Bulbizarre', null, null),
            1,
            null,
            'bulbasaur',
            0,
            null,
            null,
            null,
            new TopPokemonGameBundles([], []),
            $smallRegular,
            null,
            null,
            $bigShiny,
        );

        $topPokemon = new TopPokemon(
            $info,
            null,
            new TopPokemonTypes(null, null),
            new TopPokemonScore(1, false),
        );

        $this->assertSame('bulbasaur', $topPokemon->getPokemonIcon());
        $this->assertSame('Bulbasaur', $topPokemon->getPokemonName());
        $this->assertSame('Bulbizarre', $topPokemon->getPokemonFrenchName());
        $this->assertSame($smallRegular, $topPokemon->getPokemonSmallRegularCredit());
        $this->assertNull($topPokemon->getPokemonSmallShinyCredit());
        $this->assertNull($topPokemon->getPokemonBigRegularCredit());
        $this->assertSame($bigShiny, $topPokemon->getPokemonBigShinyCredit());
    }
}
```

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/ResponseObject/Election/TopPokemonTest.php`
Expected: FAIL — `getPokemonIcon()` etc. don't exist on `TopPokemon`.

- [ ] **Step 5: Implement `TopPokemon`'s new getters**

In `src/ResponseObject/Election/TopPokemon.php`, add `use App\ResponseObject\Common\PokemonCredit;`, then 7 new delegating getters after `getPokemon()`:

```php
    public function getPokemonIcon(): string
    {
        return $this->pokemon->getIcon();
    }

    public function getPokemonName(): string
    {
        return $this->pokemon->getLabels()->getName();
    }

    public function getPokemonFrenchName(): string
    {
        return $this->pokemon->getLabels()->getFrenchName();
    }

    public function getPokemonSmallRegularCredit(): ?PokemonCredit
    {
        return $this->pokemon->getSmallRegularCredit();
    }

    public function getPokemonSmallShinyCredit(): ?PokemonCredit
    {
        return $this->pokemon->getSmallShinyCredit();
    }

    public function getPokemonBigRegularCredit(): ?PokemonCredit
    {
        return $this->pokemon->getBigRegularCredit();
    }

    public function getPokemonBigShinyCredit(): ?PokemonCredit
    {
        return $this->pokemon->getBigShinyCredit();
    }
```

- [ ] **Step 6: Run tests and verify they pass**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/ResponseObject/Election/`
Expected: PASS.

Run: `make tests-unit` to confirm no regressions from the `ResponseObjectTrait` change.

- [ ] **Step 7: Commit**

```bash
git add src/ResponseObject/Election/TopPokemonInfo.php src/ResponseObject/Election/TopPokemon.php tests/src/Unit/ResponseObject/Election/TopPokemonInfoTest.php tests/src/Unit/ResponseObject/Election/TopPokemonTest.php tests/src/Common/Traits/ResponseObjectTrait.php
git commit -m "Thread image credit fields through Election\\TopPokemon/TopPokemonInfo"
```

---

### Task 3: Render credit badges in `_image_macros.html.twig` and prove Album/Election-candidates pick them up

**Files:**
- Modify: `templates/common/Pokemon/_image_macros.html.twig`
- Modify: `translations/messages+intl-icu.en.yaml`
- Modify: `translations/messages+intl-icu.fr.yaml`
- Modify: `tests/resources/moco/Back/responses/album/default/demo-lite.json`
- Modify: `tests/src/Integration/Controller/Album/Display/CommonTest.php`
- Modify: `tests/resources/moco/Back/responses/election/index_demolite.json`
- Modify: `tests/src/Integration/Controller/Election/ElectionIndexTest.php`

**Interfaces:**
- Consumes: `item.pokemonSmallRegularCredit`/`item.pokemonSmallShinyCredit`/`item.pokemonBigRegularCredit`/`item.pokemonBigShinyCredit` (Task 1's `Pokemon` getters, resolved via Twig's property-access-as-getter convention).
- Produces: nothing new consumed elsewhere — `Album/_album_macros.html.twig` and `Election/_candidates.html.twig` require **zero code changes**, since they already call `imageMacros.regularPokemonIcon`/`shinyPokemonIcon`/`regularPokemonImage`/`shinyPokemonImage`, which this task modifies directly. This task's tests exist specifically to prove that transparent inheritance actually works end-to-end.

- [ ] **Step 1: Add translation keys**

In `translations/messages+intl-icu.en.yaml`, add a new top-level `credit:` block (alongside the existing `album:`/`footer:`/`title:` blocks):

```yaml
credit:
  tooltip: "Image credit"
```

In `translations/messages+intl-icu.fr.yaml`:

```yaml
credit:
  tooltip: "Crédit image"
```

- [ ] **Step 2: Write a failing unit-level Twig rendering assertion is not practical here (macros aren't unit-testable in isolation without a Twig environment) — proceed directly to the integration-level failing test in Step 3, then implement.**

- [ ] **Step 3: Add credit data to the Album Moco fixture and write the failing assertion**

In `tests/resources/moco/Back/responses/album/default/demo-lite.json`, find the `bulbasaur` entry's `pokemon` object and add 4 new keys after its `game_bundles` key:

```json
"small_regular_credit": {"name": "PokéSprite", "url": "https://github.com/msikma/pokesprite"},
"small_shiny_credit": null,
"big_regular_credit": {"name": "PokemonDB", "url": "https://pokemondb.net/sprites/bulbasaur"},
"big_shiny_credit": null
```

In `tests/src/Integration/Controller/Album/Display/CommonTest.php`, add a new test method (do not modify the existing `assertRegular`/`assertShiny` helpers — this is a new, additive assertion):

```php
    public function testImageCreditBadgeIsShownWhenCreditExists(): void
    {
        $client = self::createClient();
        $client->request('GET', '/fr/album/demolite?t=7b52009b64fd0a2a49e6d8a939753077792b0554');

        $crawler = $client->getCrawler();

        $bulbasaurCase = $crawler->filter('#bulbasaur');
        $this->assertCount(1, $bulbasaurCase->filter('.pokemon-image-credit'));
        $this->assertSame(
            'https://github.com/msikma/pokesprite',
            $bulbasaurCase->filter('.pokemon-image-credit')->first()->attr('href')
        );
    }
```

Run: `docker compose exec php php vendor/bin/phpunit --filter testImageCreditBadgeIsShownWhenCreditExists tests/src/Integration/Controller/Album/Display/CommonTest.php`
Expected: FAIL — no element matches `.pokemon-image-credit`.

- [ ] **Step 4: Implement the badge in `_image_macros.html.twig`**

Replace the full content of `templates/common/Pokemon/_image_macros.html.twig` with:

```twig
{% macro regularPokemonIcon(item, locale, classMore) %}
    {{ _self.pokemonIcon(
        item,
        'regular',
        locale,
        classMore,
    ) }}
{% endmacro %}
{% macro shinyPokemonIcon(item, locale, classMore) %}
    {{ _self.pokemonIcon(
        item,
        'shiny',
        locale,
        classMore,
    ) }}
{% endmacro %}
{% macro pokemonIcon(item, dir, locale, classMore) %}
    {% set pokemonName = locale is same as('fr') ? item.pokemonFrenchName : item.pokemonName %}

    {% set iconUrl = pokemonIconUrl|format(dir, item.pokemonIcon) %}
    {% set credit = dir is same as('shiny') ? item.pokemonSmallShinyCredit : item.pokemonSmallRegularCredit %}

    <img
        alt="{{ ('album.icon.alt.'~dir)|trans }} {{ pokemonName }}"
        class="pokemon-icon img-fluid {{ classMore }}"
        src="{{ iconUrl }}"
        loading="lazy"
        onerror="this.onerror=null;this.src='/img/pokemon/default_icon.webp';"
    >
    {{ _self.creditBadge(credit) }}
{% endmacro %}

{% macro regularPokemonImage(item, locale, classMore) %}
    {{ _self.pokemonImage(
        item,
        'regular',
        locale,
        classMore,
    ) }}
{% endmacro %}
{% macro shinyPokemonImage(item, locale, classMore) %}
    {{ _self.pokemonImage(
        item,
        'shiny',
        locale,
        classMore,
    ) }}
{% endmacro %}
{% macro pokemonImage(item, dir, locale, classMore) %}
    {% set pokemonName = locale is same as('fr') ? item.pokemonFrenchName : item.pokemonName %}

    {% set imageUrl = pokemonImageUrl|format(dir, item.pokemonIcon) %}
    {% set credit = dir is same as('shiny') ? item.pokemonBigShinyCredit : item.pokemonBigRegularCredit %}

    <img
        alt="{{ ('album.image.alt.'~dir)|trans }} {{ pokemonName }}"
        class="album-modal-image card-img img-fluid {{ classMore }}"
        src="{{ imageUrl }}"
        loading="lazy"
        onerror="this.onerror=null;this.src='/img/pokemon/default_image.webp';"
    >
    {{ _self.creditBadge(credit) }}
{% endmacro %}

{% macro creditBadge(credit) %}
    {% if credit is not null %}
        <a
            href="{{ credit.url }}"
            target="_blank"
            rel="noopener"
            class="pokemon-image-credit"
            data-bs-toggle="tooltip"
            data-bs-title="{{ 'credit.tooltip'|trans }}: {{ credit.name }}"
        ><i class="bi bi-info-circle"></i></a>
    {% endif %}
{% endmacro %}
```

- [ ] **Step 5: Run the Album test and verify it passes**

Run: `docker compose exec php php vendor/bin/phpunit --filter testImageCreditBadgeIsShownWhenCreditExists tests/src/Integration/Controller/Album/Display/CommonTest.php`
Expected: PASS.

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Album/Display/CommonTest.php`
Expected: PASS (no regression — badge renders as empty output when `credit` is null, which is every other pokemon in this fixture, so existing icon/image assertions are unaffected).

- [ ] **Step 6: Add credit data to the Election Moco fixture and prove `_candidates.html.twig` picks it up**

In `tests/resources/moco/Back/responses/election/index_demolite.json`, find the `bulbasaur` entry's `pokemon` object (under the top-level `pokemons` array) and add the same 4 keys as Step 3.

In `tests/src/Integration/Controller/Election/ElectionIndexTest.php`, add a new test method:

```php
    public function testImageCreditBadgeIsShownOnCandidateCard(): void
    {
        $client = self::createClient();
        $client->request('GET', '/fr/election/demolite');

        $crawler = $client->getCrawler();

        $bulbasaurCard = $crawler->filter('#card-bulbasaur');
        $this->assertCount(1, $bulbasaurCard->filter('.pokemon-image-credit'));
        $this->assertSame(
            'https://github.com/msikma/pokesprite',
            $bulbasaurCard->filter('.pokemon-image-credit')->first()->attr('href')
        );
    }
```

- [ ] **Step 7: Run and verify**

Run: `docker compose exec php php vendor/bin/phpunit --filter testImageCreditBadgeIsShownOnCandidateCard tests/src/Integration/Controller/Election/ElectionIndexTest.php`
Expected: PASS.

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Election/ElectionIndexTest.php`
Expected: PASS (full file, no regressions).

- [ ] **Step 8: Commit**

```bash
git add templates/common/Pokemon/_image_macros.html.twig translations/messages+intl-icu.en.yaml translations/messages+intl-icu.fr.yaml tests/resources/moco/Back/responses/album/default/demo-lite.json tests/src/Integration/Controller/Album/Display/CommonTest.php tests/resources/moco/Back/responses/election/index_demolite.json tests/src/Integration/Controller/Election/ElectionIndexTest.php
git commit -m "Render image credit badge in the shared Pokemon image macros"
```

---

### Task 4: Migrate `Election/_top.html.twig` onto the shared macro

**Files:**
- Modify: `templates/Election/_top.html.twig`
- Modify: `tests/resources/moco/Back/responses/election/index_mega_lastone.json`
- Modify: `tests/src/Integration/Controller/Election/ElectionIndexTest.php`

**Interfaces:**
- Consumes: `regularPokemonImage`/`shinyPokemonImage` macros from Task 3, and `TopPokemon::getPokemonIcon()/getPokemonName()/getPokemonFrenchName()/getPokemonBigRegularCredit()/getPokemonBigShinyCredit()` from Task 2 (the macro calls `item.pokemonIcon`/`item.pokemonName`/`item.pokemonFrenchName`/`item.pokemonBigRegularCredit`/`item.pokemonBigShinyCredit` — all now present on `TopPokemon`).

- [ ] **Step 1: Add credit data to the "last one" Election Moco fixture and write the failing test**

In `tests/resources/moco/Back/responses/election/index_mega_lastone.json`, the `election_top` array's **first** item is `venusaur-mega` (confirmed by inspecting the fixture). Find that item's `pokemon` object and add 4 new keys after its `game_bundles` key:

```json
"small_regular_credit": null,
"small_shiny_credit": null,
"big_regular_credit": {"name": "PokemonDB", "url": "https://pokemondb.net/sprites/venusaur-mega"},
"big_shiny_credit": null
```

In `tests/src/Integration/Controller/Election/ElectionIndexTest.php`, extend the existing `testProgressLastOneStep()` test (do not write a parallel test — this is the one test that already exercises `#election-top`) by adding, right after the existing `$this->assertElectionTop($crawler, 'Ton top 5');` line:

```php
        $this->assertCount(1, $crawler->filter('#election-top .pokemon-image-credit'));
```

Run: `docker compose exec php php vendor/bin/phpunit --filter testProgressLastOneStep tests/src/Integration/Controller/Election/ElectionIndexTest.php`
Expected: FAIL — `_top.html.twig` doesn't render any `.pokemon-image-credit` element yet (it doesn't use the macro).

- [ ] **Step 2: Migrate `_top.html.twig` to the shared macro**

Replace the full content of `templates/Election/_top.html.twig` with:

```twig
{% import "common/Pokemon/_image_macros.html.twig" as imageMacros %}

<div id="election-top" class="mt-xl-5 mt-lg-4 mt-3 ms-xl-5 ms-lg-3 ms-0 me-xl-5 me-lg-3 me-0">
  <h4>
    {% if isTheLastOne %}
      {{ 'election.top.title.final'|trans({'n': electionTop.items|length}) }}
    {% else %}
      {{ 'election.top.title.temp'|trans({'n': electionTop.items|length}) }}
    {% endif %}
  </h4>
  <div class="row">
    {% for item in electionTop.items %}
      <div class="election-top-item col text-center position-relative" data-attr-elo="{{ item.score.elo }}">
        {% if pokedex.dex.isShiny %}
          {{ imageMacros.shinyPokemonImage(item, locale) }}
        {% else %}
          {{ imageMacros.regularPokemonImage(item, locale) }}
        {% endif %}
        <br>
        {% if item.score.significance %}
          <i class="bi bi-star"></i>
        {% endif %}
        <strong>{{ locale is same as('fr') ? item.pokemon.labels.simplifiedFrenchName : item.pokemon.labels.simplifiedName }}</strong>
        {% if item.score.significance %}
          <i class="bi bi-star"></i>
        {% endif %}
      </div>
    {% endfor %}
  </div>
</div>
```

Note: `topPokemonName` (previously used only for the manual `<img>`'s `alt` attribute, now built internally by the macro from `item.pokemonFrenchName`/`item.pokemonName`) and the manual `dir`/`imageUrl` variables are removed entirely — the macro now owns all of that. The `<strong>` text line is untouched (`item.pokemon.labels.simplifiedFrenchName`/`simplifiedName` — this still uses the nested, non-flattened shape, which is fine since `TopPokemon::getPokemon()` still returns `TopPokemonInfo` unchanged).

- [ ] **Step 3: Run and verify**

Run: `docker compose exec php php vendor/bin/phpunit --filter testProgressLastOneStep tests/src/Integration/Controller/Election/ElectionIndexTest.php`
Expected: PASS.

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Election/ElectionIndexTest.php`
Expected: PASS (full file — this confirms the `#election-top .election-top-item img` count assertion in `assertElectionTop()` still holds, since the macro also renders exactly one `<img>` per item).

Run: `make tests-browser` if time permits, to catch any Panther-level visual regression from the markup change (the macro's wrapping differs slightly from the removed manual `<img>` block — specifically, the credit badge `<a>` tag is now a sibling of the `<img>` inside `.election-top-item`, which already has `position-relative` from the pre-existing wrapping `<div>`).

- [ ] **Step 4: Commit**

```bash
git add templates/Election/_top.html.twig tests/resources/moco/Back/responses/election/index_mega_lastone.json tests/src/Integration/Controller/Election/ElectionIndexTest.php
git commit -m "Migrate Election/_top.html.twig onto the shared image macro"
```

---

### Task 5: `/credits` page

**Files:**
- Create: `src/Service/Back/GetCreditsService.php`
- Create: `src/Service/GetCreditsService.php`
- Create: `src/Controller/CreditsController.php`
- Create: `templates/Credits/index.html.twig`
- Modify: `templates/_footer.html.twig`
- Modify: `translations/messages+intl-icu.en.yaml`
- Modify: `translations/messages+intl-icu.fr.yaml`
- Modify: `tests/resources/moco/Back/moco.json`
- Create: `tests/resources/moco/Back/responses/credits.json`
- Create: `tests/src/Unit/Service/Back/GetCreditsServiceTest.php`
- Create: `tests/src/Unit/Service/GetCreditsServiceTest.php`
- Create: `tests/src/Integration/Controller/Credits/CreditsTest.php`
- Modify: `tests/src/Integration/Controller/Common/FooterTest.php`

**Interfaces:**
- Consumes: `App\ResponseObject\Common\PokemonCredit` from Task 1 (reused for the `/credits` list items — same `{name, url}` shape).
- Produces: `GET /{_locale}/credits` page listing every distinct credit source.

This follows the existing `HomeController`/`GetLabelsService`/`Service\Back\GetLabelsService` 3-layer pattern exactly: `Controller → Service (caching) → Service\Back (HTTP + deserialization)`.

- [ ] **Step 1: Write the failing `Service\Back\GetCreditsService` unit test**

Create `tests/resources/unit/service/back/credits.json`:

```json
[
    {"name": "PokéSprite", "url": "https://github.com/msikma/pokesprite"},
    {"name": "PokemonDB", "url": "https://pokemondb.net"}
]
```

Create `tests/src/Unit/Service/Back/GetCreditsServiceTest.php`, following `GetLabelsServiceTest`'s (`Service\Back` namespace) exact pattern:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\ResponseObject\Common\PokemonCredit;
use App\Security\UserTokenServiceInterface;
use App\Service\Back\AbstractBackService;
use App\Service\Back\GetCreditsService;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @internal
 */
#[CoversClass(GetCreditsService::class)]
final class GetCreditsServiceTest extends AbstractTestBackService
{
    public const ENDPOINT = 'credits';
    public const RESPONSE_CONTENT = '/app/tests/resources/unit/service/back/credits.json';

    public function testGet(): void
    {
        $json = (new Filesystem())->readFile(self::RESPONSE_CONTENT);

        $credits = [
            new PokemonCredit(name: 'PokéSprite', url: 'https://github.com/msikma/pokesprite'),
            new PokemonCredit(name: 'PokemonDB', url: 'https://pokemondb.net'),
        ];

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer
            ->expects($this->once())
            ->method('deserialize')
            ->with(
                $json,
                PokemonCredit::class.'[]',
                'json',
            )
            ->willReturn($credits)
        ;

        /** @var GetCreditsService $service */
        $service = $this->getServiceWithLoggedUser(
            'GET',
            $json,
            self::ENDPOINT,
            [],
            $serializer,
        );

        $object = $service->get();

        $this->assertCount(2, $object);
        $this->assertSame('PokéSprite', $object[0]->getName());
    }

    #[\Override]
    protected function instanciateService(
        LoggerInterface $logger,
        HttpClientInterface $client,
        string $url,
        string $cafilePath,
        UserTokenServiceInterface $userTokenService,
        SerializerInterface $serializer,
    ): AbstractBackService {
        return new GetCreditsService(
            $logger,
            $client,
            $url,
            $cafilePath,
            $userTokenService,
            $serializer,
        );
    }
}
```

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/Back/GetCreditsServiceTest.php`
Expected: FAIL — class does not exist.

- [ ] **Step 2: Implement `Service\Back\GetCreditsService`**

```php
<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\ResponseObject\Common\PokemonCredit;

class GetCreditsService extends AbstractBackService
{
    /**
     * @return PokemonCredit[]
     */
    public function get(): array
    {
        $json = $this->requestContent(
            'GET',
            '/credits'
        );

        /** @var PokemonCredit[] */
        return $this->serializer->deserialize($json, PokemonCredit::class.'[]', 'json');
    }
}
```

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/Back/GetCreditsServiceTest.php`
Expected: PASS.

- [ ] **Step 3: Write the failing `Service\GetCreditsService` (caching layer) unit test**

Create `tests/src/Unit/Service/GetCreditsServiceTest.php`, following `GetLabelsServiceTest`'s (`Service` namespace) exact pattern:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\ResponseObject\Common\PokemonCredit;
use App\Service\Back\GetCreditsService as BackGetCreditsService;
use App\Service\GetCreditsService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;

/**
 * @internal
 */
#[CoversClass(GetCreditsService::class)]
final class GetCreditsServiceTest extends TestCase
{
    public function testGet(): void
    {
        $credits = [new PokemonCredit(name: 'PokéSprite', url: 'https://github.com/msikma/pokesprite')];

        $backService = $this->createMock(BackGetCreditsService::class);
        $backService
            ->expects($this->once())
            ->method('get')
            ->willReturn($credits)
        ;

        $service = new GetCreditsService($backService, new TagAwareAdapter(new ArrayAdapter()));

        $this->assertSame($credits, $service->get());
    }

    public function testCacheIsInvalidatedByCreditsTag(): void
    {
        $credits = [new PokemonCredit(name: 'PokéSprite', url: 'https://github.com/msikma/pokesprite')];

        $backService = $this->createMock(BackGetCreditsService::class);
        $backService
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturn($credits)
        ;

        $cache = new TagAwareAdapter(new ArrayAdapter());
        $service = new GetCreditsService($backService, $cache);

        $service->get();
        $cache->invalidateTags(['credits']);
        $service->get();
    }
}
```

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/GetCreditsServiceTest.php`
Expected: FAIL — class does not exist.

- [ ] **Step 4: Implement `Service\GetCreditsService`**

```php
<?php

declare(strict_types=1);

namespace App\Service;

use App\ResponseObject\Common\PokemonCredit;
use App\Service\Back\GetCreditsService as BackGetCreditsService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class GetCreditsService
{
    public function __construct(
        private readonly BackGetCreditsService $getService,
        #[Autowire(service: 'cache.labels')]
        private readonly TagAwareCacheInterface $creditsCache,
    ) {}

    /**
     * @return PokemonCredit[]
     */
    public function get(): array
    {
        return $this->creditsCache->get('credits', function (ItemInterface $item): array {
            $item->tag(['credits']);

            return $this->getService->get();
        });
    }
}
```

This reuses the same `cache.labels` cache pool as `GetLabelsService`. Confirmed in `config/packages/cache.yaml`: `cache.labels` is a generic `cache.adapter.redis_tag_aware` pool (1-hour default lifetime) — its name is just an identifier, not a content restriction, so reusing it for credits (rather than defining a new dedicated pool) is consistent with how this codebase already shares that pool across multiple unrelated cached resources.

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/GetCreditsServiceTest.php`
Expected: PASS.

- [ ] **Step 5: Add translation keys**

In `translations/messages+intl-icu.en.yaml`, add to the `title:` block:

```yaml
title:
  credits: "Credits"
```

and to the `footer:` block:

```yaml
footer:
  credits: "Credits"
```

In `translations/messages+intl-icu.fr.yaml`, `title:` block:

```yaml
title:
  credits: "Crédits"
```

and `footer:` block:

```yaml
footer:
  credits: "Crédits"
```

- [ ] **Step 6: Add the Moco route and response fixture**

Create `tests/resources/moco/Back/responses/credits.json`:

```json
[
    {"name": "PokéSprite", "url": "https://github.com/msikma/pokesprite"},
    {"name": "PokemonDB", "url": "https://pokemondb.net/sprites/bulbasaur"},
    {"name": "Bulbapedia", "url": "https://bulbapedia.bulbagarden.net"}
]
```

In `tests/resources/moco/Back/moco.json`, add a new routing entry matching the existing style (check a neighboring simple GET entry, e.g. the `/labels` route, for the exact `headers`/`authorization` matcher pattern already used in this file, and mirror it):

```json
{
  "request": {
    "uri": { "match": "/credits" },
    "method": "get",
    "headers": {
      "X-Provider": { "match": ".*" },
      "authorization": { "match": "Bearer .*" }
    }
  },
  "response": {
    "status": "200",
    "file": "/var/moco/responses/credits.json"
  }
}
```

- [ ] **Step 7: Write the failing controller integration test**

Create `templates/Credits/index.html.twig`:

```twig
{% set locale = app.request.locale %}

{% extends 'base.html.twig' %}
{% use '_nav.html.twig' %}

{% block title %}Pokénini {{ 'title.credits'|trans }}{% endblock title %}

{% block container %}
  <div class="row justify-content-center">
    <div class="col-6">
      <h1>{{ 'title.credits'|trans }}</h1>

      <ul class="list-group">
        {% for credit in credits %}
          <li class="list-group-item">
            <a href="{{ credit.url }}" target="_blank" rel="noopener">{{ credit.name }}</a>
          </li>
        {% endfor %}
      </ul>
    </div>
  </div>
{% endblock container %}
```

Create `src/Controller/CreditsController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\GetCreditsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CreditsController extends AbstractController
{
    #[Route('/credits')]
    public function index(GetCreditsService $service): Response
    {
        return $this->render(
            'Credits/index.html.twig',
            [
                'credits' => $service->get(),
            ]
        );
    }
}
```

Create `tests/src/Integration/Controller/Credits/CreditsTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Credits;

use App\Controller\CreditsController;
use App\Tests\Common\Traits\TestNavTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[Group('api-mocked-testing')]
#[CoversClass(CreditsController::class)]
final class CreditsTest extends WebTestCase
{
    use TestNavTrait;

    public function testIndex(): void
    {
        $client = self::createClient();

        $crawler = $client->request('GET', '/fr/credits');

        $this->assertResponseIsSuccessful();

        $this->assertSame('Pokénini Crédits', $crawler->filter('title')->text());
        $this->assertSame('Crédits', $crawler->filter('h1')->text());

        $items = $crawler->filter('.list-group-item');
        $this->assertCount(3, $items);
        $this->assertSame('PokéSprite', $items->eq(0)->filter('a')->text());
        $this->assertSame('https://github.com/msikma/pokesprite', $items->eq(0)->filter('a')->attr('href'));
    }
}
```

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Credits/CreditsTest.php`
Expected: FAIL — 404 (no such route).

- [ ] **Step 8: Run and verify**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Credits/CreditsTest.php`
Expected: PASS.

- [ ] **Step 9: Add the footer link — write the failing test first**

Modify `tests/src/Integration/Controller/Common/FooterTest.php`: in **both** `testFooter()` and `testFooterAsGuest()`, change `$this->assertCountFilter($crawler, 4, 'footer ul li');` to `$this->assertCountFilter($crawler, 5, 'footer ul li');`, and add a new assertion block after the existing `Cookies` one in both methods:

```php
        ++$index;
        $this->assertStringContainsString('Crédits', $crawler->filter('footer ul li')->eq($index)->text());
```

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Common/FooterTest.php`
Expected: FAIL — count mismatch (4 actual vs 5 expected).

- [ ] **Step 10: Implement the footer link**

In `templates/_footer.html.twig`, add a new `<li>` after the `Cookies` one, before the closing `</ul>`:

```twig
      <li>
        <a href="{{ path('app_credits_index') }}" class="nav-link px-2 text-body-secondary">
          {{ 'footer.credits'|trans }}
        </a>
      </li>
```

(Route name `app_credits_index` follows Symfony's default attribute-route naming: `App\Controller\CreditsController::index()` with no explicit `name:` on `#[Route('/credits')]` auto-generates `app_credits_index` — verify this by running `docker compose exec php php bin/console debug:router | grep credits` after Step 7 and adjust the `path(...)` call here if the generated name differs.)

- [ ] **Step 11: Run and verify**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Common/FooterTest.php`
Expected: PASS.

- [ ] **Step 12: Commit**

```bash
git add src/Service/Back/GetCreditsService.php src/Service/GetCreditsService.php src/Controller/CreditsController.php templates/Credits/index.html.twig templates/_footer.html.twig translations/messages+intl-icu.en.yaml translations/messages+intl-icu.fr.yaml tests/resources/moco/Back/moco.json tests/resources/moco/Back/responses/credits.json tests/resources/unit/service/back/credits.json tests/src/Unit/Service/Back/GetCreditsServiceTest.php tests/src/Unit/Service/GetCreditsServiceTest.php tests/src/Integration/Controller/Credits/CreditsTest.php tests/src/Integration/Controller/Common/FooterTest.php
git commit -m "Add /credits page linked from the footer"
```

---

### Task 6: Final verification

- [ ] **Step 1: Run the full quality suite**

```bash
make code-quality
make coverage
make infection
```

Expected: all green — 100% coverage, 100% MSI, PHPStan level 9 clean, Psalm strict clean, Deptrac clean, PHP CS Fixer clean, W3C validation clean (the new `Credits/index.html.twig` markup must validate).

- [ ] **Step 2: Run the full test suite**

```bash
make tests
```

Expected: all unit + integration + browser tests pass.

- [ ] **Step 3: Manual browser check**

With `pokenini-web`, `pokenini-back`, and `pokenini-api` all running locally (`make start` in each), open an Album page in a browser and hover over a Pokémon sprite that has credit data seeded (via the dev DB fixtures or a manually backfilled Sheet row) to visually confirm the tooltip badge renders and links correctly, and visit `/fr/credits` to confirm the page renders. If no real credit data exists yet (Sheet not backfilled), confirm instead that no badge renders and the credits page shows an empty list without error — this is the expected steady state until the Sheet is backfilled, matching the note in the pokenini-api plan.
