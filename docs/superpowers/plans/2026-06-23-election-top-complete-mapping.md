# Election Top — Complete Property Mapping

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [x]`) syntax for tracking.

**Goal:** Compléter le mapping de TOUTES les propriétés de `election_top` dans la réponse composite du Back — les ResponseObjects actuels ne capturent qu'un sous-ensemble minimal et laissent `forms`, `types`, et 6 champs de `pokemon` non désérialisés.

**Architecture:** `pokenini-web` consomme `pokenini-back` (BFF). La réponse `/election/{dex}/{election}` du Back contient un tableau `election_top` dont chaque item a la forme imbriquée `{pokemon, forms, types, score}`. Les ResponseObjects PHP (`TopPokemon`, `TopPokemonInfo`, `TopPokemonLabels`) ne mappent actuellement que le sous-ensemble utilisé par les templates ; ce plan complète leur mapping propriété par propriété. Stratégie : **zéro changement de template** — les interfaces publiques sont étendues, jamais réduites.

**Tech Stack:** PHP ≥ 8.5, Symfony 8.0, Symfony Serializer (`#[SerializedName]`), PHPUnit (unit + WebTestCase integration), Moco.

## Global Constraints

- `declare(strict_types=1)` dans tous les fichiers PHP.
- Classes `final` pour ResponseObject / test ; `/** @internal */` + `#[CoversClass(...)]` sur chaque classe de test.
- PHPStan niveau 9 + Psalm strict : phpDoc à jour, aucune régression de baseline.
- **100 % couverture et 100 % MSI** : tout getter nouveau doit être couvert, toutes les branches nullable couvertes.
- Exécution dans le container : `docker compose exec php php vendor/bin/phpunit ...`
- **Aucun commit** (instruction permanente).
- **Aucune exécution de test** dans le cadre de la rédaction du plan — les commandes sont référence pour l'implémentation.

---

## Contexte : état vérifié au 2026-06-23

### Ce qui EST déjà fait

| Changement | Statut |
|---|---|
| `Labels` → `Forms` imbriqué (`category/regional/special/variant`) | ✅ `e98c5fc` |
| `GameBundle` → `Generation.slug` | ✅ `1a521c9` |
| `Reports` → `count` + objets imbriqués | ✅ `bfdba3e` |
| `ElectionMetrics` → `completion`/`view_count`/`win_count` imbriqués | ✅ `7a0a15d` |
| `ActionLogs` → tableau `action_type` | ✅ `8c0eda4` |
| `ElectionVote` → no-op (réponse non consommée) | ✅ vérifié |
| `TopPokemonLabels` → `simplified_name`, `simplified_french_name` | ✅ `8be417c` |
| Templates Election → utilisent `item.pokemon.labels.*` directement | ✅ `8be417c` |

### Ce qui MANQUE (objet de ce plan)

Confirmé par comparaison directe de `tests/resources/integration/back/election_mega_top_5.json` (déjà au nouveau format) et `tests/resources/moco/Back/responses/election/index_mega.json` (`election_top` au nouveau format) contre les classes PHP actuelles :

**`TopPokemon.php`** — ne mappe que `pokemon` et `score` ; manque :
- `forms : ?TopPokemonForms` (nullable, `{ category, regional, special, variant }`)
- `types : TopPokemonTypes` (`{ primary: ?Type, secondary: ?Type }`)

**`TopPokemonInfo.php`** — ne mappe que `slug`, `labels`, `national_dex_number`, `icon` (via clé `pokemon_icon` legacy) ; manque :
- `regional_dex_number : ?int` (clé `regional_dex_number`)
- `icon` : changer `#[SerializedName('pokemon_icon')]` → `#[SerializedName('icon')]` (clé canonique)
- `family_order : int`
- `family_lead : TopPokemonSlugRef` (`{ slug: string }`)
- `original_game_bundle : ?TopPokemonSlugRef` (`{ slug: string } | null`)
- `order_number : ?string`
- `game_bundles : TopPokemonGameBundles` (`{ normal: [{slug}], shiny: [{slug}] }`)

**`TopPokemonLabels.php`** — ne mappe que `name`, `french_name`, `simplified_name`, `simplified_french_name` ; manque :
- `forms_label : ?string`
- `forms_french_label : ?string`

**Les fixtures ne sont PAS à modifier** — `election_mega_top_5.json` et tous les `index_*.json` contiennent déjà le format complet.

---

## Forme cible complète (extrait de `election_mega_top_5.json`)

```json
{
    "pokemon": {
        "slug": "venusaur-mega",
        "labels": {
            "name": "Mega Venusaur",
            "french_name": "Mega Florizarre",
            "simplified_name": "Venusaur",
            "simplified_french_name": "Florizarre",
            "forms_label": "Mega",
            "forms_french_label": "Mega"
        },
        "national_dex_number": 3,
        "regional_dex_number": null,
        "icon": "venusaur-mega",
        "family_order": 4,
        "family_lead": { "slug": "bulbasaur" },
        "original_game_bundle": { "slug": "redgreenblueyellow" },
        "order_number": "9999-0003-004",
        "game_bundles": {
            "normal": [{ "slug": "redgreenblueyellow" }],
            "shiny": [{ "slug": "redgreenblueyellow" }]
        },
        "pokemon_icon": "venusaur-mega"
    },
    "forms": {
        "category": null,
        "regional": null,
        "special": { "slug": "mega", "name": "Mega", "french_name": "Mega" },
        "variant": null
    },
    "types": {
        "primary": { "slug": "grass", "name": "Grass", "french_name": "Plante", "color": "#78C850" },
        "secondary": { "slug": "poison", "name": "Poison", "french_name": "Poison", "color": "#A040A0" }
    },
    "score": { "elo": 1000.0, "significance": false }
}
```

Cas importants à couvrir (présents dans la fixture) :
- `original_game_bundle: null` (item Blastoise)
- `order_number: null` (item Blastoise)
- `types.primary: null` et `types.secondary: null` (items Blastoise, Beedrill, Pidgeot, Alakazam)
- `forms` lui-même peut être `null` (cas de l'endpoints.md, même si la fixture le a toujours comme objet)

---

## File Structure

| Fichier | Action |
|---|---|
| `src/ResponseObject/Election/TopPokemonSlugRef.php` | **Créer** — VO `{ slug: string }` partagé par `family_lead`, `original_game_bundle`, items de `game_bundles` |
| `src/ResponseObject/Election/TopPokemonGameBundles.php` | **Créer** — VO `{ normal: SlugRef[], shiny: SlugRef[] }` |
| `src/ResponseObject/Election/TopPokemonForms.php` | **Créer** — VO `{ category: ?CategoryForm, regional: ?RegionalForm, special: ?SpecialForm, variant: ?VariantForm }` |
| `src/ResponseObject/Election/TopPokemonTypes.php` | **Créer** — VO `{ primary: ?Type, secondary: ?Type }` |
| `src/ResponseObject/Election/TopPokemonLabels.php` | **Modifier** — +`forms_label: ?string`, `forms_french_label: ?string` |
| `src/ResponseObject/Election/TopPokemonInfo.php` | **Modifier** — +6 propriétés ; `pokemon_icon` → `icon` comme SerializedName |
| `src/ResponseObject/Election/TopPokemon.php` | **Modifier** — +`forms: ?TopPokemonForms`, `types: TopPokemonTypes` |
| `tests/src/Unit/ResponseObject/Election/TopPokemonSlugRefTest.php` | **Créer** |
| `tests/src/Unit/ResponseObject/Election/TopPokemonGameBundlesTest.php` | **Créer** |
| `tests/src/Unit/ResponseObject/Election/TopPokemonFormsTest.php` | **Créer** |
| `tests/src/Unit/ResponseObject/Election/TopPokemonTypesTest.php` | **Créer** |
| `tests/src/Unit/ResponseObject/Election/TopPokemonLabelsTest.php` | **Modifier** — +assertions `forms_label`, `forms_french_label` |
| `tests/src/Unit/ResponseObject/Election/TopPokemonInfoTest.php` | **Modifier** — nouvelle signature constructeur + tous les getters |
| `tests/src/Integration/ResponseObject/Election/TopPokemonTest.php` | **Modifier** — JSON enrichi + assertions sur toutes les nouvelles propriétés |

---

## Task 1 : VOs auxiliaires — `TopPokemonSlugRef` et `TopPokemonGameBundles`

Ces deux VOs sont indépendants de tout le reste et doivent être créés en premier.

**Files:**
- Create: `src/ResponseObject/Election/TopPokemonSlugRef.php`
- Create: `src/ResponseObject/Election/TopPokemonGameBundles.php`
- Create: `tests/src/Unit/ResponseObject/Election/TopPokemonSlugRefTest.php`
- Create: `tests/src/Unit/ResponseObject/Election/TopPokemonGameBundlesTest.php`

**Interfaces:**
- Produit: `TopPokemonSlugRef::__construct(string $slug)` + `getSlug(): string`
- Produit: `TopPokemonGameBundles::__construct(TopPokemonSlugRef[] $normal, TopPokemonSlugRef[] $shiny)` + `getNormal(): array` + `getShiny(): array`

---

- [x] **Step 1 : Créer `TopPokemonSlugRef.php`**

```php
<?php

declare(strict_types=1);

namespace App\ResponseObject\Election;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class TopPokemonSlugRef
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

- [x] **Step 2 : Créer `TopPokemonGameBundles.php`**

```php
<?php

declare(strict_types=1);

namespace App\ResponseObject\Election;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class TopPokemonGameBundles
{
    /**
     * @param TopPokemonSlugRef[] $normal
     * @param TopPokemonSlugRef[] $shiny
     */
    public function __construct(
        #[SerializedName('normal')]
        private readonly array $normal,
        #[SerializedName('shiny')]
        private readonly array $shiny,
    ) {}

    /**
     * @return TopPokemonSlugRef[]
     */
    public function getNormal(): array
    {
        return $this->normal;
    }

    /**
     * @return TopPokemonSlugRef[]
     */
    public function getShiny(): array
    {
        return $this->shiny;
    }
}
```

- [x] **Step 3 : Créer `tests/src/Unit/ResponseObject/Election/TopPokemonSlugRefTest.php`**

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Election;

use App\ResponseObject\Election\TopPokemonSlugRef;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(TopPokemonSlugRef::class)]
final class TopPokemonSlugRefTest extends TestCase
{
    public function testConstructor(): void
    {
        $object = new TopPokemonSlugRef('bulbasaur');

        $this->assertSame('bulbasaur', $object->getSlug());
    }
}
```

- [x] **Step 4 : Créer `tests/src/Unit/ResponseObject/Election/TopPokemonGameBundlesTest.php`**

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Election;

use App\ResponseObject\Election\TopPokemonGameBundles;
use App\ResponseObject\Election\TopPokemonSlugRef;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(TopPokemonGameBundles::class)]
final class TopPokemonGameBundlesTest extends TestCase
{
    public function testConstructor(): void
    {
        $ref1 = new TopPokemonSlugRef('redgreenblueyellow');
        $ref2 = new TopPokemonSlugRef('goldsilvercrystal');
        $object = new TopPokemonGameBundles([$ref1], [$ref2]);

        $this->assertSame([$ref1], $object->getNormal());
        $this->assertSame([$ref2], $object->getShiny());
    }

    public function testEmpty(): void
    {
        $object = new TopPokemonGameBundles([], []);

        $this->assertSame([], $object->getNormal());
        $this->assertSame([], $object->getShiny());
    }
}
```

---

## Task 2 : VOs auxiliaires — `TopPokemonForms` et `TopPokemonTypes`

**Files:**
- Create: `src/ResponseObject/Election/TopPokemonForms.php`
- Create: `src/ResponseObject/Election/TopPokemonTypes.php`
- Create: `tests/src/Unit/ResponseObject/Election/TopPokemonFormsTest.php`
- Create: `tests/src/Unit/ResponseObject/Election/TopPokemonTypesTest.php`

**Interfaces:**
- Consomme (Task 1) : aucun
- Consomme (externe) : `Label\CategoryForm`, `Label\RegionalForm`, `Label\SpecialForm`, `Label\VariantForm`, `Label\Type`
- Produit:
  - `TopPokemonForms::getCategory(): ?CategoryForm`
  - `TopPokemonForms::getRegional(): ?RegionalForm`
  - `TopPokemonForms::getSpecial(): ?SpecialForm`
  - `TopPokemonForms::getVariant(): ?VariantForm`
  - `TopPokemonTypes::getPrimary(): ?Type`
  - `TopPokemonTypes::getSecondary(): ?Type`

---

- [x] **Step 5 : Créer `TopPokemonForms.php`**

```php
<?php

declare(strict_types=1);

namespace App\ResponseObject\Election;

use App\ResponseObject\Label\CategoryForm;
use App\ResponseObject\Label\RegionalForm;
use App\ResponseObject\Label\SpecialForm;
use App\ResponseObject\Label\VariantForm;
use Symfony\Component\Serializer\Attribute\SerializedName;

final class TopPokemonForms
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

- [x] **Step 6 : Créer `TopPokemonTypes.php`**

```php
<?php

declare(strict_types=1);

namespace App\ResponseObject\Election;

use App\ResponseObject\Label\Type;
use Symfony\Component\Serializer\Attribute\SerializedName;

final class TopPokemonTypes
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

- [x] **Step 7 : Créer `tests/src/Unit/ResponseObject/Election/TopPokemonFormsTest.php`**

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Election;

use App\ResponseObject\Election\TopPokemonForms;
use App\ResponseObject\Label\CategoryForm;
use App\ResponseObject\Label\RegionalForm;
use App\ResponseObject\Label\SpecialForm;
use App\ResponseObject\Label\VariantForm;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(TopPokemonForms::class)]
final class TopPokemonFormsTest extends TestCase
{
    public function testAllNull(): void
    {
        $object = new TopPokemonForms(null, null, null, null);

        $this->assertNull($object->getCategory());
        $this->assertNull($object->getRegional());
        $this->assertNull($object->getSpecial());
        $this->assertNull($object->getVariant());
    }

    public function testAllSet(): void
    {
        $category = new CategoryForm('Legendary', 'Légendaire', 'legendary');
        $regional = new RegionalForm('Alolan', "d'Alola", 'alolan');
        $special  = new SpecialForm('Mega', 'Mega', 'mega');
        $variant  = new VariantForm('Gender', 'Sexe', 'gender');

        $object = new TopPokemonForms($category, $regional, $special, $variant);

        $this->assertSame($category, $object->getCategory());
        $this->assertSame($regional, $object->getRegional());
        $this->assertSame($special, $object->getSpecial());
        $this->assertSame($variant, $object->getVariant());
    }
}
```

- [x] **Step 8 : Créer `tests/src/Unit/ResponseObject/Election/TopPokemonTypesTest.php`**

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Election;

use App\ResponseObject\Election\TopPokemonTypes;
use App\ResponseObject\Label\Type;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(TopPokemonTypes::class)]
final class TopPokemonTypesTest extends TestCase
{
    public function testBothNull(): void
    {
        $object = new TopPokemonTypes(null, null);

        $this->assertNull($object->getPrimary());
        $this->assertNull($object->getSecondary());
    }

    public function testBothSet(): void
    {
        $primary   = new Type('Grass', 'Plante', 'grass', '#78C850');
        $secondary = new Type('Poison', 'Poison', 'poison', '#A040A0');

        $object = new TopPokemonTypes($primary, $secondary);

        $this->assertSame($primary, $object->getPrimary());
        $this->assertSame($secondary, $object->getSecondary());
    }

    public function testOnlyPrimary(): void
    {
        $primary = new Type('Normal', 'Normal', 'normal', '#A8A878');
        $object  = new TopPokemonTypes($primary, null);

        $this->assertSame($primary, $object->getPrimary());
        $this->assertNull($object->getSecondary());
    }
}
```

> `Label\Type` a le constructeur `(string $name, string $frenchName, string $slug, string $color)` — vérifié.
> `AbstractForm` (parent de `CategoryForm`, `RegionalForm`, `SpecialForm`, `VariantForm`) a le constructeur `(string $name, string $frenchName, string $slug)` — vérifié.

---

## Task 3 : Compléter `TopPokemonLabels`

**Files:**
- Modify: `src/ResponseObject/Election/TopPokemonLabels.php`
- Modify: `tests/src/Unit/ResponseObject/Election/TopPokemonLabelsTest.php`

**Interfaces:**
- Produit: `getFormsLabel(): ?string` et `getFormsFrenchLabel(): ?string` (nouveaux getters)
- Conserve: tous les getters existants (`getName`, `getFrenchName`, `getSimplifiedName`, `getSimplifiedFrenchName`)

---

- [x] **Step 9 : Modifier `TopPokemonLabels.php`** — ajouter `forms_label` et `forms_french_label`

Remplacer le contenu par :

```php
<?php

declare(strict_types=1);

namespace App\ResponseObject\Election;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class TopPokemonLabels
{
    public function __construct(
        #[SerializedName('name')]
        private readonly string $name,
        #[SerializedName('french_name')]
        private readonly string $frenchName,
        #[SerializedName('simplified_name')]
        private readonly string $simplifiedName,
        #[SerializedName('simplified_french_name')]
        private readonly string $simplifiedFrenchName,
        #[SerializedName('forms_label')]
        private readonly ?string $formsLabel,
        #[SerializedName('forms_french_label')]
        private readonly ?string $formsFrenchLabel,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getFrenchName(): string
    {
        return $this->frenchName;
    }

    public function getSimplifiedName(): string
    {
        return $this->simplifiedName;
    }

    public function getSimplifiedFrenchName(): string
    {
        return $this->simplifiedFrenchName;
    }

    public function getFormsLabel(): ?string
    {
        return $this->formsLabel;
    }

    public function getFormsFrenchLabel(): ?string
    {
        return $this->formsFrenchLabel;
    }
}
```

- [x] **Step 10 : Modifier `TopPokemonLabelsTest.php`** — mettre à jour le constructeur et ajouter les assertions

Remplacer le corps de `testConstructor` :

```php
public function testConstructor(): void
{
    $object = new TopPokemonLabels('Mega Venusaur', 'Mega Florizarre', 'Venusaur', 'Florizarre', 'Mega', 'Mega');

    $this->assertSame('Mega Venusaur', $object->getName());
    $this->assertSame('Mega Florizarre', $object->getFrenchName());
    $this->assertSame('Venusaur', $object->getSimplifiedName());
    $this->assertSame('Florizarre', $object->getSimplifiedFrenchName());
    $this->assertSame('Mega', $object->getFormsLabel());
    $this->assertSame('Mega', $object->getFormsFrenchLabel());
}

public function testNullForms(): void
{
    $object = new TopPokemonLabels('Bulbasaur', 'Bulbizarre', 'Bulbasaur', 'Bulbizarre', null, null);

    $this->assertNull($object->getFormsLabel());
    $this->assertNull($object->getFormsFrenchLabel());
}
```

---

## Task 4 : Compléter `TopPokemonInfo`

**Files:**
- Modify: `src/ResponseObject/Election/TopPokemonInfo.php`
- Modify: `tests/src/Unit/ResponseObject/Election/TopPokemonInfoTest.php`

**Interfaces:**
- Consomme (Tasks 1) : `TopPokemonSlugRef`, `TopPokemonGameBundles`
- Produit (nouveaux getters) :
  - `getRegionalDexNumber(): ?int`
  - `getFamilyOrder(): int`
  - `getFamilyLead(): TopPokemonSlugRef`
  - `getOriginalGameBundle(): ?TopPokemonSlugRef`
  - `getOrderNumber(): ?string`
  - `getGameBundles(): TopPokemonGameBundles`
- Conserve: `getSlug()`, `getLabels()`, `getNationalDexNumber()`, `getIcon()`
- **Note** : `icon` est maintenant mappé depuis la clé canonique `icon` (et non plus `pokemon_icon` — la clé `pokemon_icon` reste présente dans la fixture comme alias legacy mais ce n'est plus elle qu'on lit).

---

- [x] **Step 11 : Modifier `TopPokemonInfo.php`**

Remplacer le contenu par :

```php
<?php

declare(strict_types=1);

namespace App\ResponseObject\Election;

use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * @SuppressWarnings("PHPMD.ExcessiveParameterList")
 */
final class TopPokemonInfo
{
    public function __construct(
        #[SerializedName('slug')]
        private readonly string $slug,
        #[SerializedName('labels')]
        private readonly TopPokemonLabels $labels,
        #[SerializedName('national_dex_number')]
        private readonly int $nationalDexNumber,
        #[SerializedName('regional_dex_number')]
        private readonly ?int $regionalDexNumber,
        #[SerializedName('icon')]
        private readonly string $icon,
        #[SerializedName('family_order')]
        private readonly int $familyOrder,
        #[SerializedName('family_lead')]
        private readonly TopPokemonSlugRef $familyLead,
        #[SerializedName('original_game_bundle')]
        private readonly ?TopPokemonSlugRef $originalGameBundle,
        #[SerializedName('order_number')]
        private readonly ?string $orderNumber,
        #[SerializedName('game_bundles')]
        private readonly TopPokemonGameBundles $gameBundles,
    ) {}

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getLabels(): TopPokemonLabels
    {
        return $this->labels;
    }

    public function getNationalDexNumber(): int
    {
        return $this->nationalDexNumber;
    }

    public function getRegionalDexNumber(): ?int
    {
        return $this->regionalDexNumber;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function getFamilyOrder(): int
    {
        return $this->familyOrder;
    }

    public function getFamilyLead(): TopPokemonSlugRef
    {
        return $this->familyLead;
    }

    public function getOriginalGameBundle(): ?TopPokemonSlugRef
    {
        return $this->originalGameBundle;
    }

    public function getOrderNumber(): ?string
    {
        return $this->orderNumber;
    }

    public function getGameBundles(): TopPokemonGameBundles
    {
        return $this->gameBundles;
    }
}
```

- [x] **Step 12 : Modifier `TopPokemonInfoTest.php`** — mettre à jour la signature et couvrir tous les getters

Remplacer le contenu du fichier par :

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Election;

use App\ResponseObject\Election\TopPokemonGameBundles;
use App\ResponseObject\Election\TopPokemonInfo;
use App\ResponseObject\Election\TopPokemonLabels;
use App\ResponseObject\Election\TopPokemonSlugRef;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(TopPokemonInfo::class)]
final class TopPokemonInfoTest extends TestCase
{
    public function testConstructorFull(): void
    {
        $labels       = new TopPokemonLabels('Mega Venusaur', 'Mega Florizarre', 'Venusaur', 'Florizarre', 'Mega', 'Mega');
        $familyLead   = new TopPokemonSlugRef('bulbasaur');
        $origBundle   = new TopPokemonSlugRef('redgreenblueyellow');
        $gameBundles  = new TopPokemonGameBundles([new TopPokemonSlugRef('redgreenblueyellow')], []);

        $object = new TopPokemonInfo(
            'venusaur-mega',
            $labels,
            3,
            null,
            'venusaur-mega',
            4,
            $familyLead,
            $origBundle,
            '9999-0003-004',
            $gameBundles,
        );

        $this->assertSame('venusaur-mega', $object->getSlug());
        $this->assertSame($labels, $object->getLabels());
        $this->assertSame(3, $object->getNationalDexNumber());
        $this->assertNull($object->getRegionalDexNumber());
        $this->assertSame('venusaur-mega', $object->getIcon());
        $this->assertSame(4, $object->getFamilyOrder());
        $this->assertSame($familyLead, $object->getFamilyLead());
        $this->assertSame($origBundle, $object->getOriginalGameBundle());
        $this->assertSame('9999-0003-004', $object->getOrderNumber());
        $this->assertSame($gameBundles, $object->getGameBundles());
    }

    public function testNullableFields(): void
    {
        $labels      = new TopPokemonLabels('Blastoise', 'Tortank', 'Blastoise', 'Tortank', null, null);
        $familyLead  = new TopPokemonSlugRef('squirtle');
        $gameBundles = new TopPokemonGameBundles([], []);

        $object = new TopPokemonInfo(
            'blastoise-mega',
            $labels,
            9,
            null,
            'blastoise-mega',
            3,
            $familyLead,
            null,
            null,
            $gameBundles,
        );

        $this->assertNull($object->getOriginalGameBundle());
        $this->assertNull($object->getOrderNumber());
        $this->assertNull($object->getRegionalDexNumber());
    }

    public function testRegionalDexNumber(): void
    {
        $labels      = new TopPokemonLabels('Raichu', 'Raichu', 'Raichu', 'Raichu', null, null);
        $familyLead  = new TopPokemonSlugRef('pichu');
        $gameBundles = new TopPokemonGameBundles([], []);

        $object = new TopPokemonInfo(
            'raichu',
            $labels,
            26,
            14,
            'raichu',
            2,
            $familyLead,
            null,
            null,
            $gameBundles,
        );

        $this->assertSame(14, $object->getRegionalDexNumber());
    }
}
```

---

## Task 5 : Compléter `TopPokemon`

**Files:**
- Modify: `src/ResponseObject/Election/TopPokemon.php`
- Modify: `tests/src/Integration/ResponseObject/Election/TopPokemonTest.php`

**Interfaces:**
- Consomme (Tasks 1, 2) : `TopPokemonForms`, `TopPokemonTypes`
- Produit (nouveaux getters) :
  - `getForms(): ?TopPokemonForms`
  - `getTypes(): TopPokemonTypes`
- Conserve: `getPokemon(): TopPokemonInfo`, `getScore(): TopPokemonScore`

---

- [x] **Step 13 : Modifier `TopPokemon.php`**

Remplacer le contenu par :

```php
<?php

declare(strict_types=1);

namespace App\ResponseObject\Election;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class TopPokemon
{
    public function __construct(
        #[SerializedName('pokemon')]
        private readonly TopPokemonInfo $pokemon,
        #[SerializedName('forms')]
        private readonly ?TopPokemonForms $forms,
        #[SerializedName('types')]
        private readonly TopPokemonTypes $types,
        #[SerializedName('score')]
        private readonly TopPokemonScore $score,
    ) {}

    public function getPokemon(): TopPokemonInfo
    {
        return $this->pokemon;
    }

    public function getForms(): ?TopPokemonForms
    {
        return $this->forms;
    }

    public function getTypes(): TopPokemonTypes
    {
        return $this->types;
    }

    public function getScore(): TopPokemonScore
    {
        return $this->score;
    }
}
```

- [x] **Step 14 : Modifier `TopPokemonTest.php`** — enrichir le JSON inline et les assertions

Remplacer le contenu de `testDeserialize` (garder les autres méthodes inchangées) :

```php
    public function testDeserialize(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get(SerializerInterface::class);

        $json = <<<'JSON'
            {
                "pokemon": {
                    "slug": "venusaur-mega",
                    "labels": {
                        "name": "Mega Venusaur",
                        "french_name": "Mega Florizarre",
                        "simplified_name": "Venusaur",
                        "simplified_french_name": "Florizarre",
                        "forms_label": "Mega",
                        "forms_french_label": "Mega"
                    },
                    "national_dex_number": 3,
                    "regional_dex_number": null,
                    "icon": "venusaur-mega",
                    "family_order": 4,
                    "family_lead": { "slug": "bulbasaur" },
                    "original_game_bundle": { "slug": "redgreenblueyellow" },
                    "order_number": "9999-0003-004",
                    "game_bundles": {
                        "normal": [{ "slug": "redgreenblueyellow" }],
                        "shiny": []
                    }
                },
                "forms": {
                    "category": null,
                    "regional": null,
                    "special": { "slug": "mega", "name": "Mega", "french_name": "Mega" },
                    "variant": null
                },
                "types": {
                    "primary": { "slug": "grass", "name": "Grass", "french_name": "Plante", "color": "#78C850" },
                    "secondary": { "slug": "poison", "name": "Poison", "french_name": "Poison", "color": "#A040A0" }
                },
                "score": {
                    "elo": 1000,
                    "significance": false
                }
            }
            JSON;

        $object = $serializer->deserialize($json, TopPokemon::class, 'json');

        $this->assertSame('venusaur-mega', $object->getPokemon()->getSlug());
        $this->assertSame('Mega Venusaur', $object->getPokemon()->getLabels()->getName());
        $this->assertSame('Mega Florizarre', $object->getPokemon()->getLabels()->getFrenchName());
        $this->assertSame('Venusaur', $object->getPokemon()->getLabels()->getSimplifiedName());
        $this->assertSame('Florizarre', $object->getPokemon()->getLabels()->getSimplifiedFrenchName());
        $this->assertSame('Mega', $object->getPokemon()->getLabels()->getFormsLabel());
        $this->assertSame('Mega', $object->getPokemon()->getLabels()->getFormsFrenchLabel());
        $this->assertSame(3, $object->getPokemon()->getNationalDexNumber());
        $this->assertNull($object->getPokemon()->getRegionalDexNumber());
        $this->assertSame('venusaur-mega', $object->getPokemon()->getIcon());
        $this->assertSame(4, $object->getPokemon()->getFamilyOrder());
        $this->assertSame('bulbasaur', $object->getPokemon()->getFamilyLead()->getSlug());
        $this->assertNotNull($object->getPokemon()->getOriginalGameBundle());
        $this->assertSame('redgreenblueyellow', $object->getPokemon()->getOriginalGameBundle()->getSlug());
        $this->assertSame('9999-0003-004', $object->getPokemon()->getOrderNumber());
        $this->assertCount(1, $object->getPokemon()->getGameBundles()->getNormal());
        $this->assertCount(0, $object->getPokemon()->getGameBundles()->getShiny());
        $this->assertNotNull($object->getForms());
        $this->assertNull($object->getForms()->getCategory());
        $this->assertNotNull($object->getForms()->getSpecial());
        $this->assertSame('mega', $object->getForms()->getSpecial()->getSlug());
        $this->assertNotNull($object->getTypes()->getPrimary());
        $this->assertSame('grass', $object->getTypes()->getPrimary()->getSlug());
        $this->assertNotNull($object->getTypes()->getSecondary());
        $this->assertSame('poison', $object->getTypes()->getSecondary()->getSlug());
        $this->assertSame(1000.0, $object->getScore()->getElo());
        $this->assertFalse($object->getScore()->isSignificance());
    }
```

Mettre à jour `testDeserializeSignificant` — ajouter les champs manquants dans le JSON (même structure, changer `elo` et `significance`) :

```php
    public function testDeserializeSignificant(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get(SerializerInterface::class);

        $json = <<<'JSON'
            {
                "pokemon": {
                    "slug": "blastoise-mega",
                    "labels": {
                        "name": "Mega Blastoise",
                        "french_name": "Mega Tortank",
                        "simplified_name": "Blastoise",
                        "simplified_french_name": "Tortank",
                        "forms_label": "Mega",
                        "forms_french_label": "Mega"
                    },
                    "national_dex_number": 9,
                    "regional_dex_number": null,
                    "icon": "blastoise-mega",
                    "family_order": 3,
                    "family_lead": { "slug": "squirtle" },
                    "original_game_bundle": null,
                    "order_number": null,
                    "game_bundles": { "normal": [], "shiny": [] }
                },
                "forms": {
                    "category": null,
                    "regional": null,
                    "special": { "slug": "mega", "name": "Mega", "french_name": "Mega" },
                    "variant": null
                },
                "types": { "primary": null, "secondary": null },
                "score": { "elo": 1016.5, "significance": true }
            }
            JSON;

        $object = $serializer->deserialize($json, TopPokemon::class, 'json');

        $this->assertNull($object->getPokemon()->getOriginalGameBundle());
        $this->assertNull($object->getPokemon()->getOrderNumber());
        $this->assertNull($object->getTypes()->getPrimary());
        $this->assertNull($object->getTypes()->getSecondary());
        $this->assertSame(1016.5, $object->getScore()->getElo());
        $this->assertTrue($object->getScore()->isSignificance());
    }
```

Ajouter un test pour `forms: null` au niveau top-level :

```php
    public function testDeserializeNullForms(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get(SerializerInterface::class);

        $json = <<<'JSON'
            {
                "pokemon": {
                    "slug": "pikachu",
                    "labels": {
                        "name": "Pikachu",
                        "french_name": "Pikachu",
                        "simplified_name": "Pikachu",
                        "simplified_french_name": "Pikachu",
                        "forms_label": null,
                        "forms_french_label": null
                    },
                    "national_dex_number": 25,
                    "regional_dex_number": null,
                    "icon": "pikachu",
                    "family_order": 1,
                    "family_lead": { "slug": "pichu" },
                    "original_game_bundle": null,
                    "order_number": null,
                    "game_bundles": { "normal": [], "shiny": [] }
                },
                "forms": null,
                "types": { "primary": { "slug": "electric", "name": "Electric", "french_name": "Electrik", "color": "#FFCC33" }, "secondary": null },
                "score": { "elo": 1000.0, "significance": false }
            }
            JSON;

        $object = $serializer->deserialize($json, TopPokemon::class, 'json');

        $this->assertNull($object->getForms());
        $this->assertNull($object->getPokemon()->getLabels()->getFormsLabel());
        $this->assertNull($object->getPokemon()->getLabels()->getFormsFrenchLabel());
    }
```

> `testDeserializeArray` et `testDeserializeEmptyArray` n'ont pas besoin de modification.

---

## Task 6 : Vérification et mise à jour des outils de qualité

**Files:**
- Inspect: `tests/src/Common/Traits/ResponseObjectTrait.php` (si présent — chercher les stubs `TopPokemon`)
- Inspect: `phpmd.ruleset.xml` (si `ExcessiveParameterList` doit être supprimé dans `TopPokemonInfo`)
- Inspect: Baselines PHPStan / Psalm (si de nouvelles suppressions sont nécessaires)

---

- [x] **Step 15 : Vérifier les stubs dans `ResponseObjectTrait`**

```bash
grep -rn "TopPokemon\|TopPokemonInfo\|TopPokemonLabels" tests/src/Common/
```

Si des stubs construisent `TopPokemon`, `TopPokemonInfo` ou `TopPokemonLabels` directement, mettre à jour les appels pour inclure les nouveaux paramètres requis.

- [x] **Step 16 : Vérifier les autres tests qui instancient les classes modifiées**

```bash
grep -rn "new TopPokemon\|new TopPokemonInfo\|new TopPokemonLabels" tests/
```

Mettre à jour tous les appels identifiés (signature de constructeur élargie).

- [x] **Step 17 : Vérification statique (pas de commit)**

```bash
docker compose exec php php tools/phpstan/vendor/bin/phpstan --memory-limit=-1
docker compose exec php php tools/psalm/vendor/bin/psalm --show-info=false --no-cache --no-suggestions
```

Expected: 0 erreur. Si des erreurs de baseline apparaissent sur les nouvelles suppressions `@SuppressWarnings("PHPMD.ExcessiveParameterList")` dans `TopPokemonInfo`, les ajouter à `phpmd.ruleset.xml` ou régénérer la baseline PHPMD.

- [x] **Step 18 : Vérification couverture (référence)**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Unit/ResponseObject/Election tests/src/Integration/ResponseObject/Election/TopPokemonTest.php
```

Expected: tous verts (≈ 11 tests : 4 unit SlugRef/GameBundles/Forms/Types + 2 Labels + 3 Info + 5 intégration TopPokemon).

---

## Self-Review — Couverture des propriétés

| Propriété (fixture `election_top[]`) | VO / classe | Getters | Couvert ? |
|---|---|---|---|
| `pokemon.slug` | `TopPokemonInfo.slug` | `getSlug()` | ✅ déjà fait |
| `pokemon.labels.name` | `TopPokemonLabels.name` | `getName()` | ✅ déjà fait |
| `pokemon.labels.french_name` | `TopPokemonLabels.frenchName` | `getFrenchName()` | ✅ déjà fait |
| `pokemon.labels.simplified_name` | `TopPokemonLabels.simplifiedName` | `getSimplifiedName()` | ✅ déjà fait |
| `pokemon.labels.simplified_french_name` | `TopPokemonLabels.simplifiedFrenchName` | `getSimplifiedFrenchName()` | ✅ déjà fait |
| `pokemon.labels.forms_label` | `TopPokemonLabels.formsLabel` | `getFormsLabel()` | **Task 3** |
| `pokemon.labels.forms_french_label` | `TopPokemonLabels.formsFrenchLabel` | `getFormsFrenchLabel()` | **Task 3** |
| `pokemon.national_dex_number` | `TopPokemonInfo.nationalDexNumber` | `getNationalDexNumber()` | ✅ déjà fait |
| `pokemon.regional_dex_number` | `TopPokemonInfo.regionalDexNumber` | `getRegionalDexNumber()` | **Task 4** |
| `pokemon.icon` | `TopPokemonInfo.icon` (clé `icon`) | `getIcon()` | **Task 4** (renommage) |
| `pokemon.family_order` | `TopPokemonInfo.familyOrder` | `getFamilyOrder()` | **Task 4** |
| `pokemon.family_lead.slug` | `TopPokemonSlugRef.slug` | `getFamilyLead().getSlug()` | **Tasks 1+4** |
| `pokemon.original_game_bundle.slug` | `?TopPokemonSlugRef.slug` | `getOriginalGameBundle()?.getSlug()` | **Tasks 1+4** |
| `pokemon.order_number` | `TopPokemonInfo.orderNumber` | `getOrderNumber()` | **Task 4** |
| `pokemon.game_bundles.normal[].slug` | `TopPokemonGameBundles.normal[]` | `getGameBundles().getNormal()` | **Tasks 1+4** |
| `pokemon.game_bundles.shiny[].slug` | `TopPokemonGameBundles.shiny[]` | `getGameBundles().getShiny()` | **Tasks 1+4** |
| `forms.category` (nullable) | `TopPokemonForms.category` | `getForms().getCategory()` | **Tasks 2+5** |
| `forms.regional` (nullable) | `TopPokemonForms.regional` | `getForms().getRegional()` | **Tasks 2+5** |
| `forms.special` (nullable) | `TopPokemonForms.special` | `getForms().getSpecial()` | **Tasks 2+5** |
| `forms.variant` (nullable) | `TopPokemonForms.variant` | `getForms().getVariant()` | **Tasks 2+5** |
| `types.primary` (nullable Type) | `TopPokemonTypes.primary` | `getTypes().getPrimary()` | **Tasks 2+5** |
| `types.secondary` (nullable Type) | `TopPokemonTypes.secondary` | `getTypes().getSecondary()` | **Tasks 2+5** |
| `score.elo` | `TopPokemonScore.elo` | `getScore().getElo()` | ✅ déjà fait |
| `score.significance` | `TopPokemonScore.significance` | `getScore().isSignificance()` | ✅ déjà fait |

**Propriétés hors périmètre (autres endpoints, vérifiés non-breaking ou non consommés) :**
- `pokemon.pokemon_icon` dans la fixture — clé legacy conservée par le Back ; non mappée volontairement (`icon` est la clé canonique).
- `game_bundles`, `game_bundles_shiny` (strings) dans `ElectionIndex.pokemons` / `Common\Pokemon` — non requis par les templates.
- `forms.*.french_name` dans album — additif non-breaking sur `Common\Pokemon`.
- `/action_logs`, `/reports`, `/labels`, `/election/metrics` — déjà migrés, hors périmètre de ce plan.
