# API Response Migration — Nested Shape Propagation

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Adapt `pokenini-web` ResponseObjects, Moco fixtures, and tests to the new nested API shapes introduced by `pokenini-api`'s `feature/refactoring_responses` branch (the Back propagates the same shapes verbatim).

**Architecture:** `Common/Pokemon.php` is completely rewritten around four inner classes (`PokemonData`, `PokemonSlugRef`, `PokemonForms`, `PokemonTypes`). `Album/Dex.php` gains `DexFlags` and `DexRegion` inner classes. All public getter APIs are preserved so Twig templates and Services remain untouched.

**Tech Stack:** PHP 8.5, Symfony Serializer with `#[SerializedName]`, PHPUnit integration tests (`KernelTestCase`), Moco JSON fixtures.

## Global Constraints

- `declare(strict_types=1)` in every PHP file.
- `final class` for all ResponseObject and test classes; test classes carry `/** @internal */` and `#[CoversClass(TargetClass::class)]`.
- 100 % coverage required (`make measures`); all quality gates must be green (`make quality`).
- No commits in this plan.
- No test execution in this plan.
- All Symfony Serializer deserialization attributes use `#[SerializedName('snake_case_key')]`.
- `@SuppressWarnings("PHPMD.ExcessiveParameterList")` on constructors with > 5 parameters.
- Run tools directly in Docker container (`docker compose exec php ...`), not via Makefile targets.

---

## Already migrated (do NOT redo)

These items were completed in earlier commits on this branch:

| Item | State |
|------|-------|
| `GET /action_logs` → array with `action_type` | ✓ `GetActionLogsService` + fixture |
| `GET /forms` → single endpoint with nested object | ✓ `Label/Forms.php` |
| `GET /game_bundles` → `generation` nested | ✓ `Label/GameBundle.php` + `Label/Generation.php` |
| `GET /election/top` → fully nested `TopPokemon*` | ✓ all `Election/TopPokemon*.php` |
| `ElectionMetrics.completion` object | ✓ `DTO/ElectionMetrics.php` |

---

## File map

### Create (new files)

| File | Purpose |
|------|---------|
| `src/ResponseObject/Common/PokemonSlugRef.php` | `{ "slug": "..." }` slug-only reference |
| `src/ResponseObject/Common/PokemonData.php` | Inner `pokemon:` object in each entry |
| `src/ResponseObject/Common/PokemonForms.php` | Inner `forms:` object (category/regional/special/variant) |
| `src/ResponseObject/Common/PokemonTypes.php` | Inner `types:` object (primary/secondary) |
| `src/ResponseObject/Album/DexFlags.php` | Inner `flags:` object on a Dex |
| `src/ResponseObject/Album/DexRegion.php` | Inner `region:` object on a Dex |
| `tests/src/Unit/ResponseObject/Common/PokemonSlugRefTest.php` | Unit test |
| `tests/src/Unit/ResponseObject/Common/PokemonDataTest.php` | Unit test |
| `tests/src/Unit/ResponseObject/Common/PokemonFormsTest.php` | Unit test |
| `tests/src/Unit/ResponseObject/Common/PokemonTypesTest.php` | Unit test |
| `tests/src/Unit/ResponseObject/Album/DexFlagsTest.php` | Unit test |
| `tests/src/Unit/ResponseObject/Album/DexRegionTest.php` | Unit test |

### Modify (existing files)

| File | Change |
|------|--------|
| `src/ResponseObject/Common/Pokemon.php` | Full internal rewrite; keep all public getters |
| `src/ResponseObject/Album/Dex.php` | Full internal rewrite; keep all public getters |
| `tests/src/Integration/ResponseObject/Common/PokemonTest.php` | New JSON format in test fixtures |
| `tests/src/Integration/ResponseObject/Album/DexTest.php` | New JSON format in test fixtures |
| `tests/resources/moco/Back/responses/reports.json` | `trainer` string → object |
| `templates/Admin/_reports.html.twig` | `row.trainer` → `row.trainer.external_id` |
| `tests/resources/moco/Back/responses/election/election_vote.json` | New nested format |
| All album Moco fixtures (17 files) | New dex + new pokemon format |
| All election index fixtures (9 files) | New pokemon format in `pokemons` array + embedded pokedex |
| All election to-pick fixtures (8 files) | New pokemon format in `items` array |

---

## Task 1 — Create `Common/PokemonSlugRef` + unit test

**Files:**
- Create: `src/ResponseObject/Common/PokemonSlugRef.php`
- Create: `tests/src/Unit/ResponseObject/Common/PokemonSlugRefTest.php`

**Interfaces:**
- Produces: `PokemonSlugRef::getSlug(): string` — consumed by Tasks 2 and 3

- [ ] **Step 1: Create `PokemonSlugRef.php`**

```php
<?php

declare(strict_types=1);

namespace App\ResponseObject\Common;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class PokemonSlugRef
{
    public function __construct(
        #[SerializedName('slug')]
        private readonly string $slug,
    ) {}

    public function getSlug(): string
    {
        return $this->slug;
    }
}
```

- [ ] **Step 2: Create `PokemonSlugRefTest.php`**

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Common;

use App\ResponseObject\Common\PokemonSlugRef;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(PokemonSlugRef::class)]
final class PokemonSlugRefTest extends TestCase
{
    public function testGetSlug(): void
    {
        $ref = new PokemonSlugRef('bulbasaur');

        $this->assertSame('bulbasaur', $ref->getSlug());
    }
}
```

---

## Task 2 — Create `Common/PokemonForms` + `Common/PokemonTypes` + unit tests

**Files:**
- Create: `src/ResponseObject/Common/PokemonForms.php`
- Create: `src/ResponseObject/Common/PokemonTypes.php`
- Create: `tests/src/Unit/ResponseObject/Common/PokemonFormsTest.php`
- Create: `tests/src/Unit/ResponseObject/Common/PokemonTypesTest.php`

**Interfaces:**
- Consumes: `Label\CategoryForm`, `Label\RegionalForm`, `Label\SpecialForm`, `Label\VariantForm`, `Label\Type` (all already exist)
- Produces: `PokemonForms`, `PokemonTypes` — consumed by Task 4 (`Pokemon.php`)

- [ ] **Step 1: Create `PokemonForms.php`**

Mirrors the structure of `Election/TopPokemonForms` but in the `Common` namespace.

```php
<?php

declare(strict_types=1);

namespace App\ResponseObject\Common;

use App\ResponseObject\Label\CategoryForm;
use App\ResponseObject\Label\RegionalForm;
use App\ResponseObject\Label\SpecialForm;
use App\ResponseObject\Label\VariantForm;
use Symfony\Component\Serializer\Attribute\SerializedName;

final class PokemonForms
{
    public function __construct(
        #[SerializedName('category')]
        private readonly ?CategoryForm $category,
        #[SerializedName('regional')]
        private readonly ?RegionalForm $regional,
        #[SerializedName('special')]
        private readonly ?SpecialForm $special,
        #[SerializedName('variant')]
        private readonly ?VariantForm $variant,
    ) {}

    public function getCategory(): ?CategoryForm
    {
        return $this->category;
    }

    public function getRegional(): ?RegionalForm
    {
        return $this->regional;
    }

    public function getSpecial(): ?SpecialForm
    {
        return $this->special;
    }

    public function getVariant(): ?VariantForm
    {
        return $this->variant;
    }
}
```

- [ ] **Step 2: Create `PokemonTypes.php`**

```php
<?php

declare(strict_types=1);

namespace App\ResponseObject\Common;

use App\ResponseObject\Label\Type;
use Symfony\Component\Serializer\Attribute\SerializedName;

final class PokemonTypes
{
    public function __construct(
        #[SerializedName('primary')]
        private readonly ?Type $primary,
        #[SerializedName('secondary')]
        private readonly ?Type $secondary,
    ) {}

    public function getPrimary(): ?Type
    {
        return $this->primary;
    }

    public function getSecondary(): ?Type
    {
        return $this->secondary;
    }
}
```

- [ ] **Step 3: Create `PokemonFormsTest.php`**

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Common;

use App\ResponseObject\Common\PokemonForms;
use App\ResponseObject\Label\CategoryForm;
use App\ResponseObject\Label\RegionalForm;
use App\ResponseObject\Label\SpecialForm;
use App\ResponseObject\Label\VariantForm;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(PokemonForms::class)]
final class PokemonFormsTest extends TestCase
{
    public function testGetters(): void
    {
        $category = new CategoryForm('Starter', 'de Départ', 'starter');
        $regional = new RegionalForm('Alolan', "d'Alola", 'alolan');
        $special  = new SpecialForm('Mega', 'Mega', 'mega');
        $variant  = new VariantForm('Gender', 'Genre', 'gender');

        $forms = new PokemonForms($category, $regional, $special, $variant);

        $this->assertSame($category, $forms->getCategory());
        $this->assertSame($regional, $forms->getRegional());
        $this->assertSame($special, $forms->getSpecial());
        $this->assertSame($variant, $forms->getVariant());
    }

    public function testNullable(): void
    {
        $forms = new PokemonForms(null, null, null, null);

        $this->assertNull($forms->getCategory());
        $this->assertNull($forms->getRegional());
        $this->assertNull($forms->getSpecial());
        $this->assertNull($forms->getVariant());
    }
}
```

- [ ] **Step 4: Create `PokemonTypesTest.php`**

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Common;

use App\ResponseObject\Common\PokemonTypes;
use App\ResponseObject\Label\Type;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(PokemonTypes::class)]
final class PokemonTypesTest extends TestCase
{
    public function testGetters(): void
    {
        $primary   = new Type('Grass', 'Plante', 'grass', '#78C850');
        $secondary = new Type('Poison', 'Poison', 'poison', '#A040A0');

        $types = new PokemonTypes($primary, $secondary);

        $this->assertSame($primary, $types->getPrimary());
        $this->assertSame($secondary, $types->getSecondary());
    }

    public function testNullableSecondary(): void
    {
        $primary = new Type('Fire', 'Feu', 'fire', '#FF9D55');
        $types   = new PokemonTypes($primary, null);

        $this->assertSame($primary, $types->getPrimary());
        $this->assertNull($types->getSecondary());
    }
}
```

---

## Task 3 — Create `Common/PokemonData` + unit test

**Files:**
- Create: `src/ResponseObject/Common/PokemonData.php`
- Create: `tests/src/Unit/ResponseObject/Common/PokemonDataTest.php`

**Interfaces:**
- Consumes: `PokemonSlugRef` (Task 1)
- Produces: `PokemonData` — consumed by Task 4 (`Pokemon.php`)

New API shape for the inner `pokemon:` object (from `endpoints.md`, same for `/album` and `/pokemons/to_choose`):
```json
{
  "slug": "bulbasaur",
  "name": "Bulbasaur",
  "french_name": "Bulbizarre",
  "national_dex_number": 1,
  "regional_dex_number": 1,
  "simplified_name": "Bulbasaur",
  "forms_label": "",
  "simplified_french_name": "Bulbizarre",
  "forms_french_label": "",
  "icon": "bulbasaur",
  "family_order": 0,
  "family_lead": { "slug": "bulbasaur" },
  "original_game_bundle": { "slug": "redgreenblueyellow" },
  "order_number": "0001-0001-000",
  "game_bundles": [{ "slug": "redgreenblueyellow" }],
  "game_bundles_shiny": [{ "slug": "redgreenblueyellow" }]
}
```

- [ ] **Step 1: Create `PokemonData.php`**

```php
<?php

declare(strict_types=1);

namespace App\ResponseObject\Common;

use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * @SuppressWarnings("PHPMD.ExcessiveParameterList")
 */
final class PokemonData
{
    /**
     * @param PokemonSlugRef[] $gameBundles
     * @param PokemonSlugRef[] $gameBundlesShiny
     */
    public function __construct(
        #[SerializedName('slug')]
        private readonly string $slug,
        #[SerializedName('name')]
        private readonly string $name,
        #[SerializedName('french_name')]
        private readonly string $frenchName,
        #[SerializedName('national_dex_number')]
        private readonly int $nationalDexNumber,
        #[SerializedName('regional_dex_number')]
        private readonly ?int $regionalDexNumber,
        #[SerializedName('simplified_name')]
        private readonly string $simplifiedName,
        #[SerializedName('forms_label')]
        private readonly string $formsLabel,
        #[SerializedName('simplified_french_name')]
        private readonly string $simplifiedFrenchName,
        #[SerializedName('forms_french_label')]
        private readonly string $formsFrenchLabel,
        #[SerializedName('icon')]
        private readonly string $icon,
        #[SerializedName('family_order')]
        private readonly int $familyOrder,
        #[SerializedName('family_lead')]
        private readonly ?PokemonSlugRef $familyLead,
        #[SerializedName('original_game_bundle')]
        private readonly ?PokemonSlugRef $originalGameBundle,
        #[SerializedName('order_number')]
        private readonly string $orderNumber,
        #[SerializedName('game_bundles')]
        private readonly array $gameBundles,
        #[SerializedName('game_bundles_shiny')]
        private readonly array $gameBundlesShiny,
    ) {}

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFrenchName(): string
    {
        return $this->frenchName;
    }

    public function getNationalDexNumber(): int
    {
        return $this->nationalDexNumber;
    }

    public function getRegionalDexNumber(): ?int
    {
        return $this->regionalDexNumber;
    }

    public function getSimplifiedName(): string
    {
        return $this->simplifiedName;
    }

    public function getFormsLabel(): string
    {
        return $this->formsLabel;
    }

    public function getSimplifiedFrenchName(): string
    {
        return $this->simplifiedFrenchName;
    }

    public function getFormsFrenchLabel(): string
    {
        return $this->formsFrenchLabel;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function getFamilyOrder(): int
    {
        return $this->familyOrder;
    }

    public function getFamilyLead(): ?PokemonSlugRef
    {
        return $this->familyLead;
    }

    public function getOriginalGameBundle(): ?PokemonSlugRef
    {
        return $this->originalGameBundle;
    }

    public function getOrderNumber(): string
    {
        return $this->orderNumber;
    }

    /**
     * @return PokemonSlugRef[]
     */
    public function getGameBundles(): array
    {
        return $this->gameBundles;
    }

    /**
     * @return PokemonSlugRef[]
     */
    public function getGameBundlesShiny(): array
    {
        return $this->gameBundlesShiny;
    }
}
```

- [ ] **Step 2: Create `PokemonDataTest.php`**

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Common;

use App\ResponseObject\Common\PokemonData;
use App\ResponseObject\Common\PokemonSlugRef;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(PokemonData::class)]
final class PokemonDataTest extends TestCase
{
    public function testGetters(): void
    {
        $familyLead          = new PokemonSlugRef('charmander');
        $originalGameBundle  = new PokemonSlugRef('xy');
        $bundle1             = new PokemonSlugRef('xy');
        $bundle2             = new PokemonSlugRef('omegarubyalphasapphire');

        $data = new PokemonData(
            slug: 'charizard-mega-y',
            name: 'Mega Charizard Y',
            frenchName: 'Méga Dracaufeu Y',
            nationalDexNumber: 6,
            regionalDexNumber: null,
            simplifiedName: 'Charizard',
            formsLabel: 'Mega Y',
            simplifiedFrenchName: 'Dracaufeu',
            formsFrenchLabel: 'Méga Y',
            icon: 'charizard-mega-y',
            familyOrder: 4,
            familyLead: $familyLead,
            originalGameBundle: $originalGameBundle,
            orderNumber: '9999-0006-004',
            gameBundles: [$bundle1],
            gameBundlesShiny: [$bundle2],
        );

        $this->assertSame('charizard-mega-y', $data->getSlug());
        $this->assertSame('Mega Charizard Y', $data->getName());
        $this->assertSame('Méga Dracaufeu Y', $data->getFrenchName());
        $this->assertSame(6, $data->getNationalDexNumber());
        $this->assertNull($data->getRegionalDexNumber());
        $this->assertSame('Charizard', $data->getSimplifiedName());
        $this->assertSame('Mega Y', $data->getFormsLabel());
        $this->assertSame('Dracaufeu', $data->getSimplifiedFrenchName());
        $this->assertSame('Méga Y', $data->getFormsFrenchLabel());
        $this->assertSame('charizard-mega-y', $data->getIcon());
        $this->assertSame(4, $data->getFamilyOrder());
        $this->assertSame($familyLead, $data->getFamilyLead());
        $this->assertSame($originalGameBundle, $data->getOriginalGameBundle());
        $this->assertSame('9999-0006-004', $data->getOrderNumber());
        $this->assertSame([$bundle1], $data->getGameBundles());
        $this->assertSame([$bundle2], $data->getGameBundlesShiny());
    }

    public function testNullableFamilyLead(): void
    {
        $data = new PokemonData(
            slug: 'bulbasaur',
            name: 'Bulbasaur',
            frenchName: 'Bulbizarre',
            nationalDexNumber: 1,
            regionalDexNumber: 1,
            simplifiedName: 'Bulbasaur',
            formsLabel: '',
            simplifiedFrenchName: 'Bulbizarre',
            formsFrenchLabel: '',
            icon: 'bulbasaur',
            familyOrder: 0,
            familyLead: null,
            originalGameBundle: null,
            orderNumber: '0001-0001-000',
            gameBundles: [],
            gameBundlesShiny: [],
        );

        $this->assertNull($data->getFamilyLead());
        $this->assertNull($data->getOriginalGameBundle());
        $this->assertSame(1, $data->getRegionalDexNumber());
    }
}
```

---

## Task 4 — Rewrite `Common/Pokemon.php` + update integration test

**Files:**
- Modify: `src/ResponseObject/Common/Pokemon.php`
- Modify: `tests/src/Integration/ResponseObject/Common/PokemonTest.php`

**Interfaces:**
- Consumes: `PokemonData` (Task 3), `PokemonForms` (Task 2), `PokemonTypes` (Task 2), `Label\CatchState` (already exists)
- Produces: All existing public getters (unchanged) — consumed by Twig templates and services

New API shapes:
```json
{
  "pokemon": { "slug": "...", "name": "...", ... },
  "catch_state": { "slug": "no", "name": "No", "french_name": "Non", "color": "#e57373" },
  "forms": { "category": {...}, "regional": null, "special": null, "variant": null },
  "types": { "primary": {...}, "secondary": {...} }
}
```
`catch_state` is `null` for `/pokemons/to_choose` entries (no catch state set yet) and may be null in album entries (pokemon not yet caught).

- [ ] **Step 1: Rewrite `Pokemon.php`**

```php
<?php

declare(strict_types=1);

namespace App\ResponseObject\Common;

use App\ResponseObject\Label\CatchState;
use Symfony\Component\Serializer\Attribute\SerializedName;

final class Pokemon
{
    public function __construct(
        #[SerializedName('pokemon')]
        private readonly PokemonData $pokemon,
        #[SerializedName('catch_state')]
        private readonly ?CatchState $catchState,
        #[SerializedName('forms')]
        private readonly ?PokemonForms $forms,
        #[SerializedName('types')]
        private readonly PokemonTypes $types,
    ) {}

    public function getPokemonSlug(): string
    {
        return $this->pokemon->getSlug();
    }

    public function getPokemonName(): string
    {
        return $this->pokemon->getName();
    }

    public function getPokemonFrenchName(): string
    {
        return $this->pokemon->getFrenchName();
    }

    public function getPokemonNationalDexNumber(): int
    {
        return $this->pokemon->getNationalDexNumber();
    }

    public function getPokemonRegionalDexNumber(): ?int
    {
        return $this->pokemon->getRegionalDexNumber();
    }

    public function getPokemonSimplifiedName(): string
    {
        return $this->pokemon->getSimplifiedName();
    }

    public function getPokemonFormsLabel(): string
    {
        return $this->pokemon->getFormsLabel();
    }

    public function getPokemonSimplifiedFrenchName(): string
    {
        return $this->pokemon->getSimplifiedFrenchName();
    }

    public function getPokemonFormsFrenchLabel(): string
    {
        return $this->pokemon->getFormsFrenchLabel();
    }

    public function getPokemonIcon(): string
    {
        return $this->pokemon->getIcon();
    }

    public function getPokemonFamilyOrder(): int
    {
        return $this->pokemon->getFamilyOrder();
    }

    public function getFamilyLeadSlug(): ?string
    {
        return $this->pokemon->getFamilyLead()?->getSlug();
    }

    public function getPokemonOrderNumber(): string
    {
        return $this->pokemon->getOrderNumber();
    }

    public function getCatchStateSlug(): ?string
    {
        return $this->catchState?->getSlug();
    }

    public function getCatchStateName(): ?string
    {
        return $this->catchState?->getName();
    }

    public function getCatchStateFrenchName(): ?string
    {
        return $this->catchState?->getFrenchName();
    }

    public function getCategoryFormSlug(): ?string
    {
        return $this->forms?->getCategory()?->getSlug();
    }

    public function getCategoryFormName(): ?string
    {
        return $this->forms?->getCategory()?->getName();
    }

    public function getRegionalFormSlug(): ?string
    {
        return $this->forms?->getRegional()?->getSlug();
    }

    public function getRegionalFormName(): ?string
    {
        return $this->forms?->getRegional()?->getName();
    }

    public function getSpecialFormSlug(): ?string
    {
        return $this->forms?->getSpecial()?->getSlug();
    }

    public function getSpecialFormName(): ?string
    {
        return $this->forms?->getSpecial()?->getName();
    }

    public function getVariantFormSlug(): ?string
    {
        return $this->forms?->getVariant()?->getSlug();
    }

    public function getVariantFormName(): ?string
    {
        return $this->forms?->getVariant()?->getName();
    }

    public function getPrimaryTypeSlug(): ?string
    {
        return $this->types->getPrimary()?->getSlug();
    }

    public function getPrimaryTypeName(): ?string
    {
        return $this->types->getPrimary()?->getName();
    }

    public function getPrimaryTypeFrenchName(): ?string
    {
        return $this->types->getPrimary()?->getFrenchName();
    }

    public function getSecondaryTypeSlug(): ?string
    {
        return $this->types->getSecondary()?->getSlug();
    }

    public function getSecondaryTypeName(): ?string
    {
        return $this->types->getSecondary()?->getName();
    }

    public function getSecondaryTypeFrenchName(): ?string
    {
        return $this->types->getSecondary()?->getFrenchName();
    }
}
```

- [ ] **Step 2: Rewrite `PokemonTest.php` (integration)**

Replace both test methods with new JSON that matches the new nested format. Keep the same assertion method names and values — only the JSON input changes.

```php
<?php

declare(strict_types=1);

namespace App\Tests\Integration\ResponseObject\Common;

use App\ResponseObject\Common\Pokemon;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
#[CoversClass(Pokemon::class)]
final class PokemonTest extends KernelTestCase
{
    public function testDeserialize(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get(SerializerInterface::class);

        $json = <<<'JSON'
            {
                "pokemon": {
                    "slug": "charizard-mega-y",
                    "name": "Mega Charizard Y",
                    "french_name": "Méga Dracaufeu Y",
                    "national_dex_number": 6,
                    "regional_dex_number": 9854,
                    "simplified_name": "Charizard",
                    "forms_label": "Mega Y",
                    "simplified_french_name": "Dracaufeu",
                    "forms_french_label": "Méga Y",
                    "icon": "charizard-mega-y",
                    "family_order": 4,
                    "family_lead": { "slug": "charmander" },
                    "original_game_bundle": { "slug": "xy" },
                    "order_number": "9999-0006-004",
                    "game_bundles": [{ "slug": "xy" }, { "slug": "omegarubyalphasapphire" }],
                    "game_bundles_shiny": [{ "slug": "xy" }]
                },
                "catch_state": {
                    "slug": "yes",
                    "name": "Yes",
                    "french_name": "Oui",
                    "color": "#66bb6a"
                },
                "forms": {
                    "category": { "slug": "starter", "name": "Starter", "french_name": "de Départ" },
                    "regional": { "slug": "kantonian", "name": "Kantonian", "french_name": "Kantien" },
                    "special": { "slug": "mega", "name": "Mega", "french_name": "Mega" },
                    "variant": { "slug": "battle", "name": "Battle", "french_name": "Combat" }
                },
                "types": {
                    "primary": { "slug": "fire", "name": "Fire", "french_name": "Feu", "color": "#FF9D55" },
                    "secondary": { "slug": "flying", "name": "Flying", "french_name": "Vol", "color": "#8EA9DF" }
                }
            }
            JSON;

        $object = $serializer->deserialize($json, Pokemon::class, 'json');

        $this->assertInstanceOf(Pokemon::class, $object);
        $this->assertSame('charizard-mega-y', $object->getPokemonSlug());
        $this->assertSame('Mega Charizard Y', $object->getPokemonName());
        $this->assertSame(6, $object->getPokemonNationalDexNumber());
        $this->assertSame('Charizard', $object->getPokemonSimplifiedName());
        $this->assertSame('Mega Y', $object->getPokemonFormsLabel());
        $this->assertSame('Méga Dracaufeu Y', $object->getPokemonFrenchName());
        $this->assertSame('Dracaufeu', $object->getPokemonSimplifiedFrenchName());
        $this->assertSame('Méga Y', $object->getPokemonFormsFrenchLabel());
        $this->assertSame('charizard-mega-y', $object->getPokemonIcon());
        $this->assertSame(4, $object->getPokemonFamilyOrder());
        $this->assertSame('charmander', $object->getFamilyLeadSlug());
        $this->assertSame('starter', $object->getCategoryFormSlug());
        $this->assertSame('Starter', $object->getCategoryFormName());
        $this->assertSame('kantonian', $object->getRegionalFormSlug());
        $this->assertSame('Kantonian', $object->getRegionalFormName());
        $this->assertSame('mega', $object->getSpecialFormSlug());
        $this->assertSame('Mega', $object->getSpecialFormName());
        $this->assertSame('battle', $object->getVariantFormSlug());
        $this->assertSame('Battle', $object->getVariantFormName());
        $this->assertSame('yes', $object->getCatchStateSlug());
        $this->assertSame('Yes', $object->getCatchStateName());
        $this->assertSame('Oui', $object->getCatchStateFrenchName());
        $this->assertSame(9854, $object->getPokemonRegionalDexNumber());
        $this->assertSame('fire', $object->getPrimaryTypeSlug());
        $this->assertSame('Fire', $object->getPrimaryTypeName());
        $this->assertSame('Feu', $object->getPrimaryTypeFrenchName());
        $this->assertSame('flying', $object->getSecondaryTypeSlug());
        $this->assertSame('Flying', $object->getSecondaryTypeName());
        $this->assertSame('Vol', $object->getSecondaryTypeFrenchName());
        $this->assertSame('9999-0006-004', $object->getPokemonOrderNumber());
    }

    public function testDeserializeWithNullValues(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get(SerializerInterface::class);

        $json = <<<'JSON'
            {
                "pokemon": {
                    "slug": "charizard-mega-y",
                    "name": "Mega Charizard Y",
                    "french_name": "Méga Dracaufeu Y",
                    "national_dex_number": 6,
                    "regional_dex_number": null,
                    "simplified_name": "Charizard",
                    "forms_label": "Mega Y",
                    "simplified_french_name": "Dracaufeu",
                    "forms_french_label": "Méga Y",
                    "icon": "charizard-mega-y",
                    "family_order": 4,
                    "family_lead": { "slug": "charmander" },
                    "original_game_bundle": { "slug": "xy" },
                    "order_number": "9999-0006-004",
                    "game_bundles": [{ "slug": "xy" }],
                    "game_bundles_shiny": []
                },
                "catch_state": null,
                "forms": {
                    "category": null,
                    "regional": null,
                    "special": { "slug": "mega", "name": "Mega", "french_name": "Mega" },
                    "variant": null
                },
                "types": {
                    "primary": { "slug": "fire", "name": "Fire", "french_name": "Feu", "color": "#FF9D55" },
                    "secondary": null
                }
            }
            JSON;

        $object = $serializer->deserialize($json, Pokemon::class, 'json');

        $this->assertInstanceOf(Pokemon::class, $object);
        $this->assertSame('charizard-mega-y', $object->getPokemonSlug());
        $this->assertSame('Mega Charizard Y', $object->getPokemonName());
        $this->assertSame(6, $object->getPokemonNationalDexNumber());
        $this->assertSame('Charizard', $object->getPokemonSimplifiedName());
        $this->assertSame('Mega Y', $object->getPokemonFormsLabel());
        $this->assertSame('Méga Dracaufeu Y', $object->getPokemonFrenchName());
        $this->assertSame('Dracaufeu', $object->getPokemonSimplifiedFrenchName());
        $this->assertSame('Méga Y', $object->getPokemonFormsFrenchLabel());
        $this->assertSame('charizard-mega-y', $object->getPokemonIcon());
        $this->assertSame(4, $object->getPokemonFamilyOrder());
        $this->assertSame('charmander', $object->getFamilyLeadSlug());
        $this->assertNull($object->getCategoryFormSlug());
        $this->assertNull($object->getCategoryFormName());
        $this->assertNull($object->getRegionalFormSlug());
        $this->assertNull($object->getRegionalFormName());
        $this->assertSame('mega', $object->getSpecialFormSlug());
        $this->assertSame('Mega', $object->getSpecialFormName());
        $this->assertNull($object->getVariantFormSlug());
        $this->assertNull($object->getVariantFormName());
        $this->assertNull($object->getCatchStateSlug());
        $this->assertNull($object->getCatchStateName());
        $this->assertNull($object->getCatchStateFrenchName());
        $this->assertNull($object->getPokemonRegionalDexNumber());
        $this->assertSame('fire', $object->getPrimaryTypeSlug());
        $this->assertSame('Fire', $object->getPrimaryTypeName());
        $this->assertSame('Feu', $object->getPrimaryTypeFrenchName());
        $this->assertNull($object->getSecondaryTypeSlug());
        $this->assertNull($object->getSecondaryTypeName());
        $this->assertNull($object->getSecondaryTypeFrenchName());
        $this->assertSame('9999-0006-004', $object->getPokemonOrderNumber());
    }
}
```

---

## Task 5 — Create `Album/DexFlags` + `Album/DexRegion` + unit tests

**Files:**
- Create: `src/ResponseObject/Album/DexFlags.php`
- Create: `src/ResponseObject/Album/DexRegion.php`
- Create: `tests/src/Unit/ResponseObject/Album/DexFlagsTest.php`
- Create: `tests/src/Unit/ResponseObject/Album/DexRegionTest.php`

**Interfaces:**
- Produces: `DexFlags`, `DexRegion` — consumed by Task 6 (`Dex.php`)

New API shape for `flags` (from `endpoints.md`):
```json
{
  "is_shiny": false,
  "is_private": false,
  "is_on_home": false,
  "is_display_form": true,
  "is_released": true,
  "is_premium": false,
  "is_custom": false
}
```

- [ ] **Step 1: Create `DexFlags.php`**

```php
<?php

declare(strict_types=1);

namespace App\ResponseObject\Album;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class DexFlags
{
    public function __construct(
        #[SerializedName('is_shiny')]
        private readonly bool $isShiny,
        #[SerializedName('is_private')]
        private readonly bool $isPrivate,
        #[SerializedName('is_on_home')]
        private readonly bool $isOnHome,
        #[SerializedName('is_display_form')]
        private readonly bool $isDisplayForm,
        #[SerializedName('is_released')]
        private readonly bool $isReleased,
        #[SerializedName('is_premium')]
        private readonly bool $isPremium,
        #[SerializedName('is_custom')]
        private readonly bool $isCustom,
    ) {}

    public function isShiny(): bool
    {
        return $this->isShiny;
    }

    public function isPrivate(): bool
    {
        return $this->isPrivate;
    }

    public function isOnHome(): bool
    {
        return $this->isOnHome;
    }

    public function isDisplayForm(): bool
    {
        return $this->isDisplayForm;
    }

    public function isReleased(): bool
    {
        return $this->isReleased;
    }

    public function isPremium(): bool
    {
        return $this->isPremium;
    }

    public function isCustom(): bool
    {
        return $this->isCustom;
    }
}
```

- [ ] **Step 2: Create `DexRegion.php`**

```php
<?php

declare(strict_types=1);

namespace App\ResponseObject\Album;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class DexRegion
{
    public function __construct(
        #[SerializedName('name')]
        private readonly string $name,
        #[SerializedName('french_name')]
        private readonly string $frenchName,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getFrenchName(): string
    {
        return $this->frenchName;
    }
}
```

- [ ] **Step 3: Create `DexFlagsTest.php`**

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Album;

use App\ResponseObject\Album\DexFlags;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(DexFlags::class)]
final class DexFlagsTest extends TestCase
{
    public function testGetters(): void
    {
        $flags = new DexFlags(
            isShiny: true,
            isPrivate: false,
            isOnHome: true,
            isDisplayForm: true,
            isReleased: true,
            isPremium: false,
            isCustom: true,
        );

        $this->assertTrue($flags->isShiny());
        $this->assertFalse($flags->isPrivate());
        $this->assertTrue($flags->isOnHome());
        $this->assertTrue($flags->isDisplayForm());
        $this->assertTrue($flags->isReleased());
        $this->assertFalse($flags->isPremium());
        $this->assertTrue($flags->isCustom());
    }
}
```

- [ ] **Step 4: Create `DexRegionTest.php`**

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Album;

use App\ResponseObject\Album\DexRegion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(DexRegion::class)]
final class DexRegionTest extends TestCase
{
    public function testGetters(): void
    {
        $region = new DexRegion('Kanto', 'Kanto');

        $this->assertSame('Kanto', $region->getName());
        $this->assertSame('Kanto', $region->getFrenchName());
    }
}
```

---

## Task 6 — Rewrite `Album/Dex.php` + update integration test

**Files:**
- Modify: `src/ResponseObject/Album/Dex.php`
- Modify: `tests/src/Integration/ResponseObject/Album/DexTest.php`

**Interfaces:**
- Consumes: `DexFlags` (Task 5), `DexRegion` (Task 5)
- Produces: All existing public getters (unchanged) — consumed by Twig templates

New API shape for `dex` in album (from `endpoints.md`):
```json
{
  "slug": "redgreenblueyellow",
  "original_slug": "redgreenblueyellow",
  "name": "Red / Green / Blue / Yellow",
  "french_name": "Rouge / Vert / Bleu / Jaune",
  "flags": {
    "is_shiny": false,
    "is_private": false,
    "is_on_home": false,
    "is_display_form": true,
    "is_released": true,
    "is_premium": false,
    "is_custom": false
  },
  "display_template": "box",
  "region": { "name": "Kanto", "french_name": "Kanto" },
  "selection_rule": "",
  "description": "First generation Pokédex",
  "french_description": "Pokédex de la première génération",
  "version": "20230221.085100"
}
```

- [ ] **Step 1: Rewrite `Dex.php`**

```php
<?php

declare(strict_types=1);

namespace App\ResponseObject\Album;

use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * @SuppressWarnings("PHPMD.ExcessiveParameterList")
 */
final class Dex
{
    public function __construct(
        #[SerializedName('slug')]
        private readonly string $slug,
        #[SerializedName('original_slug')]
        private readonly string $originalSlug,
        #[SerializedName('name')]
        private readonly string $name,
        #[SerializedName('french_name')]
        private readonly string $frenchName,
        #[SerializedName('flags')]
        private readonly DexFlags $flags,
        #[SerializedName('display_template')]
        private readonly ?string $displayTemplate,
        #[SerializedName('region')]
        private readonly ?DexRegion $region,
        #[SerializedName('selection_rule')]
        private readonly string $selectionRule,
        #[SerializedName('description')]
        private readonly string $description,
        #[SerializedName('french_description')]
        private readonly string $frenchDescription,
        #[SerializedName('version')]
        private readonly string $version,
    ) {}

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getOriginalSlug(): string
    {
        return $this->originalSlug;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFrenchName(): string
    {
        return $this->frenchName;
    }

    public function getFlags(): DexFlags
    {
        return $this->flags;
    }

    public function getDisplayTemplate(): ?string
    {
        return $this->displayTemplate;
    }

    public function getRegion(): ?DexRegion
    {
        return $this->region;
    }

    public function getSelectionRule(): string
    {
        return $this->selectionRule;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getFrenchDescription(): string
    {
        return $this->frenchDescription;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    // ── Delegation methods — keep public API unchanged for Twig templates ──

    public function isShiny(): bool
    {
        return $this->flags->isShiny();
    }

    public function isPrivate(): bool
    {
        return $this->flags->isPrivate();
    }

    public function isOnHome(): bool
    {
        return $this->flags->isOnHome();
    }

    public function isDisplayForm(): bool
    {
        return $this->flags->isDisplayForm();
    }

    public function isReleased(): bool
    {
        return $this->flags->isReleased();
    }

    public function isPremium(): bool
    {
        return $this->flags->isPremium();
    }

    public function isCustom(): bool
    {
        return $this->flags->isCustom();
    }

    public function getRegionName(): ?string
    {
        return $this->region?->getName();
    }

    public function getRegionFrenchName(): ?string
    {
        return $this->region?->getFrenchName();
    }
}
```

- [ ] **Step 2: Rewrite `DexTest.php` (integration)**

Replace both test methods with new nested-flags format. Keep assertion method names and values unchanged — only the JSON input changes.

```php
<?php

declare(strict_types=1);

namespace App\Tests\Integration\ResponseObject\Album;

use App\ResponseObject\Album\Dex;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
#[CoversClass(Dex::class)]
final class DexTest extends KernelTestCase
{
    public function testDeserialize(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get(SerializerInterface::class);

        $json = <<<'JSON'
            {
                "slug": "lite",
                "original_slug": "litelite",
                "name": "Demo light",
                "french_name": "Démo, extrait",
                "flags": {
                    "is_shiny": false,
                    "is_private": false,
                    "is_on_home": false,
                    "is_display_form": true,
                    "is_released": true,
                    "is_premium": false,
                    "is_custom": false
                },
                "display_template": "box",
                "region": { "name": "Kanto", "french_name": "Kan-to" },
                "selection_rule": "",
                "description": "A small pokedex",
                "french_description": "Un petit pokédex",
                "version": "0.0"
            }
            JSON;

        $object = $serializer->deserialize($json, Dex::class, 'json');

        $this->assertInstanceOf(Dex::class, $object);
        $this->assertSame('lite', $object->getSlug());
        $this->assertSame('litelite', $object->getOriginalSlug());
        $this->assertSame('Demo light', $object->getName());
        $this->assertSame('Démo, extrait', $object->getFrenchName());
        $this->assertFalse($object->isShiny());
        $this->assertFalse($object->isPrivate());
        $this->assertTrue($object->isDisplayForm());
        $this->assertSame('box', $object->getDisplayTemplate());
        $this->assertSame('Kanto', $object->getRegionName());
        $this->assertSame('Kan-to', $object->getRegionFrenchName());
        $this->assertSame('A small pokedex', $object->getDescription());
        $this->assertSame('Un petit pokédex', $object->getFrenchDescription());
        $this->assertSame('0.0', $object->getVersion());
        $this->assertTrue($object->isReleased());
        $this->assertFalse($object->isPremium());
        $this->assertFalse($object->isCustom());
    }

    public function testDeserializeWithNull(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get(SerializerInterface::class);

        $json = <<<'JSON'
            {
                "slug": "lite",
                "original_slug": "litelite",
                "name": "Demo light",
                "french_name": "Démo, extrait",
                "flags": {
                    "is_shiny": false,
                    "is_private": false,
                    "is_on_home": false,
                    "is_display_form": true,
                    "is_released": true,
                    "is_premium": false,
                    "is_custom": false
                },
                "display_template": null,
                "region": null,
                "selection_rule": "",
                "description": "A small pokedex",
                "french_description": "Un petit pokédex",
                "version": "0"
            }
            JSON;

        $object = $serializer->deserialize($json, Dex::class, 'json');

        $this->assertInstanceOf(Dex::class, $object);
        $this->assertSame('lite', $object->getSlug());
        $this->assertSame('litelite', $object->getOriginalSlug());
        $this->assertSame('Demo light', $object->getName());
        $this->assertSame('Démo, extrait', $object->getFrenchName());
        $this->assertFalse($object->isShiny());
        $this->assertFalse($object->isPrivate());
        $this->assertTrue($object->isDisplayForm());
        $this->assertNull($object->getDisplayTemplate());
        $this->assertNull($object->getRegionName());
        $this->assertNull($object->getRegionFrenchName());
        $this->assertSame('A small pokedex', $object->getDescription());
        $this->assertSame('Un petit pokédex', $object->getFrenchDescription());
        $this->assertSame('0', $object->getVersion());
        $this->assertTrue($object->isReleased());
        $this->assertFalse($object->isPremium());
        $this->assertFalse($object->isCustom());
    }
}
```

---

## Task 7 — Update album Moco fixtures

**Files:** 17 files under `tests/resources/moco/Back/responses/album/`

**Interfaces:**
- Consumes: new `Dex` JSON shape (Task 6), new `Pokemon` JSON shape (Task 4)

Two transformations per fixture:

### 7a — Transform the `dex` object

**Before:**
```json
"dex": {
  "slug": "demo",
  "original_slug": "demo",
  "name": "Demo",
  "french_name": "Démo",
  "is_shiny": false,
  "is_private": true,
  "is_display_form": true,
  "display_template": "box",
  "region_name": null,
  "region_french_name": null,
  "description": "...",
  "french_description": "...",
  "version": "412",
  "is_released": true,
  "is_premium": false,
  "is_custom": false
}
```

**After:**
```json
"dex": {
  "slug": "demo",
  "original_slug": "demo",
  "name": "Demo",
  "french_name": "Démo",
  "flags": {
    "is_shiny": false,
    "is_private": true,
    "is_on_home": false,
    "is_display_form": true,
    "is_released": true,
    "is_premium": false,
    "is_custom": false
  },
  "display_template": "box",
  "region": null,
  "selection_rule": "",
  "description": "...",
  "french_description": "...",
  "version": "412"
}
```

Rules:
- Move `is_shiny`, `is_private`, `is_display_form`, `is_released`, `is_premium`, `is_custom` into a `flags` object. Add `is_on_home: false` (new field, default `false`).
- Replace `region_name` + `region_french_name` with `region: null` when both were null, or `region: { "name": "...", "french_name": "..." }` when set.
- Add `selection_rule: ""`.
- Remove the old flat flag keys from root.

### 7b — Transform each `pokemon` entry in the `pokemons` array

**Before (flat format):**
```json
{
  "pokemon_slug": "bulbasaur",
  "pokemon_name": "Bulbasaur",
  "pokemon_national_dex_number": 1,
  "pokemon_simplified_name": "Bulbasaur",
  "pokemon_forms_label": "",
  "pokemon_french_name": "Bulbizarre",
  "pokemon_simplified_french_name": "Bulbizarre",
  "pokemon_forms_french_label": "",
  "pokemon_icon": "bulbasaur",
  "pokemon_family_order": 0,
  "family_lead_slug": null,
  "category_form_slug": "starter",
  "category_form_name": "Starter",
  "regional_form_slug": null,
  "regional_form_name": null,
  "special_form_slug": null,
  "special_form_name": null,
  "variant_form_slug": null,
  "variant_form_name": null,
  "catch_state_slug": null,
  "catch_state_name": null,
  "catch_state_french_name": null,
  "pokemon_regional_dex_number": null,
  "primary_type_slug": "grass",
  "primary_type_name": "Grass",
  "primary_type_french_name": "Plante",
  "secondary_type_slug": "poison",
  "secondary_type_name": "Poison",
  "secondary_type_french_name": "Poison",
  "pokemon_order_number": "9999-0001-000",
  "original_game_bundle_slug": "redgreenblueyellow",
  "game_bundles": ["redgreenblueyellow", "goldsilvercrystal", ...],
  "game_bundles_shiny": [...]
}
```

**After (nested format):**
```json
{
  "pokemon": {
    "slug": "bulbasaur",
    "name": "Bulbasaur",
    "french_name": "Bulbizarre",
    "national_dex_number": 1,
    "regional_dex_number": null,
    "simplified_name": "Bulbasaur",
    "forms_label": "",
    "simplified_french_name": "Bulbizarre",
    "forms_french_label": "",
    "icon": "bulbasaur",
    "family_order": 0,
    "family_lead": null,
    "original_game_bundle": { "slug": "redgreenblueyellow" },
    "order_number": "9999-0001-000",
    "game_bundles": [
      { "slug": "redgreenblueyellow" },
      { "slug": "goldsilvercrystal" }
    ],
    "game_bundles_shiny": [
      { "slug": "redgreenblueyellow" }
    ]
  },
  "catch_state": null,
  "forms": {
    "category": { "slug": "starter", "name": "Starter", "french_name": "de Départ" },
    "regional": null,
    "special": null,
    "variant": null
  },
  "types": {
    "primary": { "slug": "grass", "name": "Grass", "french_name": "Plante", "color": "#78C850" },
    "secondary": { "slug": "poison", "name": "Poison", "french_name": "Poison", "color": "#A040A0" }
  }
}
```

Mapping rules:
- `pokemon_slug` → `pokemon.slug`
- `pokemon_name` → `pokemon.name`
- `pokemon_french_name` → `pokemon.french_name`
- `pokemon_national_dex_number` → `pokemon.national_dex_number`
- `pokemon_regional_dex_number` → `pokemon.regional_dex_number`
- `pokemon_simplified_name` → `pokemon.simplified_name`
- `pokemon_forms_label` → `pokemon.forms_label`
- `pokemon_simplified_french_name` → `pokemon.simplified_french_name`
- `pokemon_forms_french_label` → `pokemon.forms_french_label`
- `pokemon_icon` → `pokemon.icon`
- `pokemon_family_order` → `pokemon.family_order`
- `family_lead_slug` → `pokemon.family_lead: null | { "slug": "..." }`
- `original_game_bundle_slug` → `pokemon.original_game_bundle: { "slug": "..." }`
- `pokemon_order_number` → `pokemon.order_number`
- `game_bundles: ["slug1", ...]` → `pokemon.game_bundles: [{ "slug": "slug1" }, ...]`
- `game_bundles_shiny: [...]` → `pokemon.game_bundles_shiny: [{ "slug": "..." }, ...]`
- `catch_state_slug` + `catch_state_name` + `catch_state_french_name` → `catch_state: null | { "slug": "...", "name": "...", "french_name": "...", "color": "..." }` (add color from catch state list in `labels.json`)
- `category_form_slug/name` → `forms.category: null | { "slug": "...", "name": "...", "french_name": "..." }`
- `regional_form_slug/name` → `forms.regional: null | { ... }`
- `special_form_slug/name` → `forms.special: null | { ... }`
- `variant_form_slug/name` → `forms.variant: null | { ... }`
- `primary_type_slug/name/french_name` → `types.primary: { "slug": "...", "name": "...", "french_name": "...", "color": "..." }` (add color from types list in `labels.json`)
- `secondary_type_slug/name/french_name` → `types.secondary: null | { ... }` (add color)

> ⚠️ For `color` fields: look up the color from `tests/resources/moco/Back/responses/labels.json` — `types[*]` and use `label.color`. For forms `french_name`, look up from the same `labels.json` → `forms.*[*]`.

- [ ] **Step 1: Update `album/default/demo.json`** (template file for all others)

Apply transformation 7a to the `dex` object and 7b to each entry in the `pokemons` array.

- [ ] **Step 2: Update `album/default/demo_list3.json`** and `demo_list3_catchstatesno.json`

Same transformations. `catchstatesno` variants have all `catch_state` set to `null`.

- [ ] **Step 3: Update `album/default/demo_list5.json`** and `demo_list5_catchstatesno.json`

- [ ] **Step 4: Update `album/default/demo_list7.json`** and `demo_list7_catchstatesno.json`

- [ ] **Step 5: Update `album/default/demo-lite.json`**, `demo-lite_catchstatesno.json`, `demo-lite-shiny.json`

Note: `demo-lite-shiny.json` has `is_shiny: true` — set `flags.is_shiny: true` in its `dex.flags`.

- [ ] **Step 6: Update `album/default/demo_notemplate.json`** and `demo_notemplate_catchstatesno.json`

Note: `display_template` is `null` in these fixtures; keep it `null` under the same key.

- [ ] **Step 7: Update `album/default/demo_unknowntemplate.json`** and `demo_unknowntemplate_catchstatesno.json`

- [ ] **Step 8: Update remaining `album/default/` fixtures**

`blackwhite.json`, `goldsilvercrystal.json`, `home.json`, `homepokemongo.json`, `mega.json`, `redgreenblueyellow.json`, `swordshield.json`, `virgin.json`

For `home.json`: the dex has no region (null).
For `redgreenblueyellow.json`: the dex has `region: { "name": "Kanto", "french_name": "Kanto" }`.
For `swordshield.json`: the dex has `region: { "name": "Galar", "french_name": "Galar" }`.
`virgin.json` typically has an empty/null dex — verify and apply only the applicable transformations.

- [ ] **Step 9: Update `album/7b52009b.../demo.json`**, `goldsilvercrystal.json`, `home.json`

Trainer-specific variants. Same transformations. Verify each dex's original `region_name` before converting.

- [ ] **Step 10: Update `album/6c33064427.../demo.json`** and `album/159bb9b6.../goldsilvercrystal.json`

---

## Task 8 — Update election to-pick Moco fixtures

**Files:** 8 files under `tests/resources/moco/Back/responses/election/pokemons_to*`

The `ElectionList.items` uses `Pokemon[]`. After Task 4, it expects the new nested format. Each `item` in these fixtures needs transformation 7b **without** `catch_state` (always `null` for to-pick items, so use `"catch_state": null`).

The outer envelope is already correct (`"type": "pick"`, `"items": [...]`).

- [ ] **Step 1: Update `election/pokemons_topick_demolite_12.json`**

Apply transformation 7b to each entry in `items`. Set `catch_state: null` for all.
`game_bundles` and `game_bundles_shiny` were absent in the old flat format — add them as `[]` (the election to-choose endpoint doesn't have them in test data; use `[]`).

- [ ] **Step 2: Update `election/pokemons_topick_demoliteshiny_12.json`**

- [ ] **Step 3: Update `election/pokemons_topick_mega_12.json`**

- [ ] **Step 4: Update `election/pokemons_topick_mega_favorite_12.json`**

- [ ] **Step 5: Update `election/pokemons_topick_swordshield_12.json`**

- [ ] **Step 6: Update `election/pokemons_tovote_mega_lastone_12.json`**

- [ ] **Step 7: Update `election/pokemons_tovote_mega_lastpage_12.json`**

- [ ] **Step 8: Update `election/pokemons_tovote_mega_vote_12.json`**

---

## Task 9 — Update election index Moco fixtures

**Files:** 9 files under `tests/resources/moco/Back/responses/election/index_*.json`

`ElectionIndex.pokemons` is `Pokemon[]`. The `pokemons` array entries use the same flat format as to-pick. Transform each with 7b (no catch_state → null).

`ElectionIndex.pokedex` is a `?Pokedex` which embeds a `Dex` and `Pokemon[]` — apply both 7a (to the embedded `dex`) and 7b (to the embedded `pokemons`).

`ElectionIndex.electionTop` is `TopPokemon[]` — check if these are already in the new nested `TopPokemon` format. The prior commits should have updated them; **do not re-convert if already nested**.

- [ ] **Step 1: Update `election/index_mega.json`**

- Apply 7b to `pokemons[]` (no catch_state).
- Apply 7a + 7b to the embedded `pokedex.dex` and `pokedex.pokemons[]` respectively.
- Verify `election_top[]` is in new TopPokemon format; update only if still flat.

- [ ] **Step 2: Update `election/index_mega_favorite.json`**

Same as Step 1.

- [ ] **Step 3: Update `election/index_mega_vote.json`**

- [ ] **Step 4: Update `election/index_mega_lastone.json`**

- [ ] **Step 5: Update `election/index_mega_lastpage.json`**

- [ ] **Step 6: Update `election/index_demolite.json`**

- [ ] **Step 7: Update `election/index_demoliteshiny.json`**

The embedded dex has `is_shiny: true` — set `flags.is_shiny: true`.

- [ ] **Step 8: Update `election/index_swordshield.json`**

- [ ] **Step 9: Update `election/index_swordshield_favorite.json`**

---

## Task 10 — Update `election_vote.json` + `reports.json` + template

**Files:**
- Modify: `tests/resources/moco/Back/responses/election/election_vote.json`
- Modify: `tests/resources/moco/Back/responses/reports.json`
- Modify: `templates/Admin/_reports.html.twig`

### 10a — `election_vote.json`

The `PostElectionVoteService` does not parse the response body, so this is a fixture accuracy update only.

**Before:**
```json
{
  "election_vote": {
    "trainer_external_id": "7b52009b64fd0a2a49e6d8a939753077792b0554",
    "election_slug": "",
    "winners_slugs": ["butterfree"],
    "losers_slugs": ["caterpie", "metapod"]
  },
  "pokemons_elo": {
    "winners": [{ "pokemon_slug": "butterfree", "elo": 1016 }],
    "losers": [
      { "pokemon_slug": "caterpie", "elo": 984 },
      { "pokemon_slug": "metapod", "elo": 984 }
    ]
  }
}
```

**After (from `endpoints.md` + `migration.md`):**
```json
{
  "election_vote": {
    "trainer": { "external_id": "7b52009b64fd0a2a49e6d8a939753077792b0554" },
    "dex": { "slug": "demo" },
    "election_slug": "",
    "winners": [{ "slug": "butterfree" }],
    "losers": [{ "slug": "caterpie" }, { "slug": "metapod" }]
  },
  "pokemons_elo": {
    "winners": [{ "pokemon": { "slug": "butterfree" }, "elo": 1016 }],
    "losers": [
      { "pokemon": { "slug": "caterpie" }, "elo": 984 },
      { "pokemon": { "slug": "metapod" }, "elo": 984 }
    ]
  }
}
```

- [ ] **Step 1: Replace `election_vote.json`** with the new content above.

### 10b — `reports.json` — trainer field

The current fixture has `"trainer": "f86cbe..."` (string). The API now returns `"trainer": { "external_id": "..." }` (object).

**Before (each entry in `catch_state_counts_defined_by_trainer`):**
```json
{ "count": 5735, "trainer": "f86cbe805674d85f7806b175b70647a6a9334631" }
```

**After:**
```json
{ "count": 5735, "trainer": { "external_id": "f86cbe805674d85f7806b175b70647a6a9334631" } }
```

Apply this transformation to all entries in `catch_state_counts_defined_by_trainer`.

- [ ] **Step 2: Update `reports.json`** — convert each `trainer` string to `{ "external_id": "..." }`.

### 10c — `templates/Admin/_reports.html.twig`

After 10b, `row.trainer` is now an array `{ external_id: "..." }` instead of a plain string. All Twig references to `row.trainer` must become `row.trainer.external_id`.

**Before (lines 40, 42, 48, 57, 58):**
```twig
id="catch_state_counts_defined_by_trainer-row-{{ row.trainer }}"
id="catch_state_counts_defined_by_trainer-toggle-{{ row.trainer }}"
<canvas id="catch_state_counts_defined_by_trainer-legend-{{ row.trainer }}"></canvas>
id="catch_state_counts_defined_by_trainer-text-{{ row.trainer }}"
{{ row.trainer }}
```

**After:**
```twig
id="catch_state_counts_defined_by_trainer-row-{{ row.trainer.external_id }}"
id="catch_state_counts_defined_by_trainer-toggle-{{ row.trainer.external_id }}"
<canvas id="catch_state_counts_defined_by_trainer-legend-{{ row.trainer.external_id }}"></canvas>
id="catch_state_counts_defined_by_trainer-text-{{ row.trainer.external_id }}"
{{ row.trainer.external_id }}
```

- [ ] **Step 3: Update `_reports.html.twig`** — replace all 5 occurrences of `row.trainer` with `row.trainer.external_id`.

---

## Self-Review Checklist

### Spec coverage

| migration.md change | Task |
|---------------------|------|
| `GET /forms` → consolidated | Already done ✓ |
| `GET /game_bundles` → nested generation | Already done ✓ |
| `GET /reports` → slug in nested objects | `reports.json` fixture already has this; `trainer` string→object in Task 10b |
| `GET /album` → `game_bundles` added, `french_name` on forms | Task 7b (pokemon conversion) |
| `GET /pokemons/to_choose` → `game_bundles` + `color` added | Task 8 |
| `POST /election/vote` → trainer nested, score added | Task 10a |
| `GET /election/top` → fully nested | Already done ✓ |
| `GET /election/metrics` → completion object | Already done ✓ |
| `GET /action_logs` → array with action_type | Already done ✓ |
| `GET /debogage/pokemon/{slug}` → family.slug, bank.* | Debug endpoint, Web doesn't use it |
| `GET /debogage/pokemon/{slug}/availabilities` → nested groups | Debug endpoint, Web doesn't use it |

### Additional `endpoints.md` changes not listed in migration.md

| change | Task |
|--------|------|
| `dex.flags` nested under `flags` object | Tasks 5, 6, 7a, 9 |
| `dex.region` nested + `selection_rule` added | Tasks 5, 6, 7a, 9 |
| `dex.is_on_home` new flag | Task 5 (DexFlags) |
| `reports.trainer` string → object | Task 10b |

### Placeholder scan

None — all steps contain concrete code or concrete JSON.

### Type consistency

- `PokemonData` created in Task 3; `Pokemon.php` consumes it in Task 4 → ✓
- `PokemonForms.getCategory()` → `?CategoryForm` in Task 2; `Pokemon.getCategoryFormSlug()` calls `getCategory()?->getSlug()` in Task 4 → ✓
- `PokemonTypes.getPrimary()` → `?Type` in Task 2; `Pokemon.getPrimaryTypeSlug()` calls `getPrimary()?->getSlug()` in Task 4 → ✓
- `DexFlags.isShiny()` defined in Task 5; `Dex.isShiny()` delegates to it in Task 6 → ✓
- `DexRegion.getName()` defined in Task 5; `Dex.getRegionName()` delegates to `region?->getName()` in Task 6 → ✓
- Fixture format in Tasks 7/8/9 matches JSON structure expected by `Pokemon` (Task 4) and `Dex` (Task 6) → ✓
