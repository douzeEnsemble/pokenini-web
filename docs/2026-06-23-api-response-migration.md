# Plan de migration — Réponses API (pokenini-web, approche B) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal :** Adapter `pokenini-web` aux réponses réagencées de l'API Pokénini (migration `feature/refactoring_responses`), **propagées à l'identique par `pokenini-back`**, en faisant remonter l'imbrication jusque dans les templates Twig (**approche B**).

**Architecture :** `pokenini-web` ne parle jamais à l'API directement — il consomme les endpoints composites de `pokenini-back` (BFF). Sous l'approche B, chaque réponse réagencée est désérialisée dans des Value Objects **qui reflètent la forme imbriquée de l'API**, **sans getter de compatibilité** : ce sont les contrôleurs et templates qui sont mis à jour pour traverser les chemins imbriqués. C'est l'opposé d'une stratégie de préservation d'interface — on accepte une surface de modification (et de tests) plus large en échange de VO « bêtes » alignés 1:1 sur le JSON.

**Tech Stack :** PHP 8.4, Symfony 8.0, Twig, Symfony Serializer (désérialisation des `ResponseObject`), `OptionsResolver` (DTO), Moco (mock HTTP), PHPUnit, Infection.

## Global Constraints

- `declare(strict_types=1)` dans **tous** les fichiers PHP.
- Classes `final` pour DTO / ResponseObject / Controller / tests ; classes **non**-`final` pour les Services (mockables).
- Tout ResponseObject/DTO public porte `#[SerializedName('snake_case_key')]` sur chaque propriété mappée.
- PHPStan niveau 9 + Psalm strict (taint + standard) : phpDoc à jour partout (`@param array<int, X>`, `array{...}`).
- 100 % de couverture **et** 100 % MSI (Infection) — couvrir les branches d'erreur de chaque parsing imbriqué.
- Toolchain Docker only : exécuter directement dans le conteneur `php` (`docker compose exec php …`), pas via `make`.
- **Aucun commit git ne doit être créé** (politique projet). Les étapes « Commit » du gabarit writing-plans sont **remplacées** par une étape « Pas de commit ».
- **Aucune exécution de test dans le cadre de ce plan.** Les commandes `phpunit`/`make` sont **indicatives**, à lancer manuellement ensuite.
- **Hypothèse de base (« from scratch ») :** ce plan suppose un point de départ aux **formes plates pré-refactoring** et décrit la migration complète vers les formes imbriquées cibles. Les formes cibles sont les formes **canoniques** de `pokenini-api/doc/endpoints.md` + `doc/migration.md`, puisque le Back les propage à l'identique.

---

## File Structure

**ResponseObject (désérialisation directe du JSON Back) :**
- `src/ResponseObject/Label/Generation.php` — VO `{slug}` imbriqué dans `GameBundle`.
- `src/ResponseObject/Label/GameBundle.php` — `generation` devient un VO `Generation`.
- `src/ResponseObject/Label/Forms.php` — VO `{category,regional,special,variant}` imbriqué dans `Labels`.
- `src/ResponseObject/Label/Labels.php` — `forms` devient un VO `Forms` ; suppression des 4 tableaux de tête.
- `src/ResponseObject/Election/TopPokemonScore.php` — VO `{elo:float,significance:bool}`.
- `src/ResponseObject/Election/TopPokemonLabels.php` — VO `{name,french_name,simplified_name,simplified_french_name}`.
- `src/ResponseObject/Election/TopPokemonInfo.php` — VO `{slug,labels,national_dex_number,pokemon_icon}`.
- `src/ResponseObject/Election/TopPokemon.php` — `{pokemon:TopPokemonInfo, score:TopPokemonScore}` sans getters de compat.
- `src/ResponseObject/ActionLog.php` — inchangé (`created_at`/`done_at`/`execution_time`/`details`/`error_trace`).

**DTO :**
- `src/DTO/ElectionMetricsCounts.php` — VO `{sum:int,max:int}` réutilisé pour `view_count` et `win_count`.
- `src/DTO/ElectionMetricsCompletion.php` — VO `{under_max_count:int, at_max_count:int}`.
- `src/DTO/ElectionMetrics.php` — sous-objets `viewCount`/`winCount`/`completion` ; `roundCount`/`totalRoundCount`/`winnerAverage` restent plats.
- `src/DTO/ActionLogData.php` — `{actionType:string, current:?ActionLog, last:?ActionLog}`.

**Services :**
- `src/Service/Back/GetReportsService.php` — JSON brut, inchangé (les clés changent dans les templates/fixtures).
- `src/Service/Back/GetActionLogsService.php` — renvoie désormais une **liste** `array<int, ActionLogData>`.
- `src/Service/ElectionIndexService.php` — alimente `ElectionMetrics::createFromArray()` (forme imbriquée).

**Contrôleurs :**
- `src/Controller/AlbumIndexController.php` / `src/Controller/ElectionIndexController.php` — passent `labels.getForms()` (et non plus 4 tableaux) au template.
- `src/Controller/AdminController.php` — passe la **liste** d'action logs au template.

**Templates :**
- `templates/common/Filter/_dex_filters_blocks.html.twig` — itère `forms.category` / `.regional` / `.special` / `.variant`.
- `templates/Admin/_reports.html.twig` + `templates/Admin/_reports_scripts.html.twig` — `.count` + chemins `dex.*` / `catch_state.*`.
- `templates/Election/_top.html.twig` — `item.score.*` + `item.pokemon.labels.*`.
- `templates/Election/_bar_top.html.twig` — `metrics.completion.*`.
- `templates/Admin/_macros.html.twig` — itère la liste d'action logs et filtre par `action_type`.

**Fixtures :** `tests/resources/moco/Back/responses/*`, `tests/resources/unit/service/back/*`, `tests/resources/integration/back/*`.
**Stubs de test :** `tests/src/Common/Traits/ResponseObjectTrait.php`.

> Tout consommateur d'un getter de compatibilité supprimé (ex. `getGenerationSlug()`, `getCategoryForms()`, `getElo()`) doit être réécrit. Avant chaque task, lancer le `grep` indiqué pour lister les consommateurs réels et ne pas en oublier.

---

## Task 1 : game_bundles — `generation_slug` → `generation.slug`

**Files:**
- Create: `src/ResponseObject/Label/Generation.php`
- Modify: `src/ResponseObject/Label/GameBundle.php`
- Modify: consommateurs de `generationSlug` (voir grep Step 0)
- Test: `tests/src/Unit/ResponseObject/Label/GenerationTest.php`, `tests/src/Unit/ResponseObject/Label/GameBundleTest.php`
- Fixtures: `tests/resources/moco/Back/responses/labels.json`, `tests/resources/unit/service/back/labels.json`, `tests/resources/integration/back/labels.json`

**Interfaces:**
- Produces : `Generation::getSlug(): string` ; `GameBundle::getGeneration(): Generation` (plus de `getGenerationSlug()`).

- [ ] **Step 0 : Recenser les consommateurs**

Run : `grep -rn "getGenerationSlug\|generationSlug" src/ templates/ tests/`
Pour chaque hit hors `GameBundle` lui-même : remplacer plus bas par `…->getGeneration()->getSlug()` (PHP) ou `gameBundle.generation.slug` (Twig).

- [ ] **Step 1 : Test du VO `Generation` (échec attendu)**

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Label;

use App\ResponseObject\Label\Generation;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/** @internal */
#[CoversClass(Generation::class)]
final class GenerationTest extends TestCase
{
    public function testExposesSlug(): void
    {
        self::assertSame('1', (new Generation('1'))->getSlug());
    }
}
```

- [ ] **Step 2 : Lancer le test (manuel) — doit échouer**

Run : `docker compose exec php php vendor/bin/phpunit --filter GenerationTest`
Expected : FAIL (`Class "App\ResponseObject\Label\Generation" not found`).

- [ ] **Step 3 : Créer le VO `Generation`**

```php
<?php

declare(strict_types=1);

namespace App\ResponseObject\Label;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class Generation
{
    public function __construct(
        #[SerializedName('slug')] private readonly string $slug,
    ) {
    }

    public function getSlug(): string
    {
        return $this->slug;
    }
}
```

- [ ] **Step 4 : Migrer `GameBundle`**

Remplacer le paramètre plat `generation_slug` par le VO et **supprimer** `getGenerationSlug()` :

```php
public function __construct(
    #[SerializedName('name')] private readonly string $name,
    #[SerializedName('french_name')] private readonly string $frenchName,
    #[SerializedName('slug')] private readonly string $slug,
    #[SerializedName('generation')] private readonly Generation $generation,
) {
}

public function getGeneration(): Generation
{
    return $this->generation;
}
```

- [ ] **Step 5 : Réécrire les consommateurs listés au Step 0**

PHP : `$bundle->getGenerationSlug()` → `$bundle->getGeneration()->getSlug()`.
Twig : `bundle.generationSlug` → `bundle.generation.slug`.

- [ ] **Step 6 : Migrer les 3 fixtures `labels.json`**

Dans chaque `game_bundles[]`, remplacer `"generation_slug": "1"` par `"generation": { "slug": "1" }` (valeurs conservées).

- [ ] **Step 7 : Tests `GameBundle` + stub**

Mettre à jour `GameBundleTest` (construction via `new GameBundle(..., new Generation('1'))`, assert `getGeneration()->getSlug()`), et le stub de `ResponseObjectTrait::getStubLabels()` si présent (`new GameBundle(..., new Generation('1'))`).

- [ ] **Step 8 : Vérification (manuelle)**

Run : `docker compose exec php php vendor/bin/phpunit --filter 'Generation|GameBundle|Labels'`
Expected : PASS.

- [ ] **Step 9 : Pas de commit** (politique projet).

---

## Task 2 : forms — 4 tableaux plats → objet `forms` imbriqué

**Files:**
- Create: `src/ResponseObject/Label/Forms.php`
- Modify: `src/ResponseObject/Label/Labels.php` (suppression de `getCategoryForms/…`)
- Modify: `src/Controller/AlbumIndexController.php`, `src/Controller/ElectionIndexController.php`
- Modify: `templates/common/Filter/_dex_filters_blocks.html.twig`
- Test: `tests/src/Unit/ResponseObject/Label/FormsTest.php`, `tests/src/Unit/ResponseObject/Label/LabelsTest.php`, `tests/src/Integration/ResponseObject/Label/LabelsTest.php`
- Fixtures: `tests/resources/moco/Back/responses/labels.json`, `tests/resources/unit/service/back/labels.json`, `tests/resources/integration/back/labels.json`

**Interfaces:**
- Consumes : `CategoryForm`/`RegionalForm`/`SpecialForm`/`VariantForm` (existants, extends `AbstractForm` → `getName()/getFrenchName()/getSlug()`).
- Produces : `Forms::getCategory()/getRegional()/getSpecial()/getVariant(): array<int, *Form>` ; `Labels::getForms(): Forms` (plus de `getCategoryForms()` etc.).

- [ ] **Step 0 : Recenser les consommateurs**

Run : `grep -rn "getCategoryForms\|getRegionalForms\|getSpecialForms\|getVariantForms\|categoryForms\|regionalForms\|specialForms\|variantForms" src/ templates/ tests/`
Attendu : `_dex_filters_blocks.html.twig`, `AlbumIndexController`, `ElectionIndexController`, tests.

- [ ] **Step 1 : Test du VO `Forms` (échec attendu)**

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Label;

use App\ResponseObject\Label\CategoryForm;
use App\ResponseObject\Label\Forms;
use App\ResponseObject\Label\RegionalForm;
use App\ResponseObject\Label\SpecialForm;
use App\ResponseObject\Label\VariantForm;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/** @internal */
#[CoversClass(Forms::class)]
final class FormsTest extends TestCase
{
    public function testExposesEachGroup(): void
    {
        $forms = new Forms(
            [new CategoryForm('Starter', 'de Départ', 'starter')],
            [new RegionalForm('Alolan', "d'Alola", 'alolan')],
            [new SpecialForm('Mega', 'Mega', 'mega')],
            [new VariantForm('Gender', 'Sexe', 'gender')],
        );

        self::assertSame('starter', $forms->getCategory()[0]->getSlug());
        self::assertSame('alolan', $forms->getRegional()[0]->getSlug());
        self::assertSame('mega', $forms->getSpecial()[0]->getSlug());
        self::assertSame('gender', $forms->getVariant()[0]->getSlug());
    }
}
```

> Vérifier l'ordre exact des paramètres d'`AbstractForm` (`name`, `french_name`, `slug`) avant d'écrire ce test ; l'ajuster si l'ordre diffère.

- [ ] **Step 2 : Lancer le test (manuel) — doit échouer**

Run : `docker compose exec php php vendor/bin/phpunit --filter FormsTest`
Expected : FAIL (`Class "App\ResponseObject\Label\Forms" not found`).

- [ ] **Step 3 : Créer le VO `Forms`**

```php
<?php

declare(strict_types=1);

namespace App\ResponseObject\Label;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class Forms
{
    /**
     * @param array<int, CategoryForm> $category
     * @param array<int, RegionalForm> $regional
     * @param array<int, SpecialForm>  $special
     * @param array<int, VariantForm>  $variant
     */
    public function __construct(
        #[SerializedName('category')] private readonly array $category,
        #[SerializedName('regional')] private readonly array $regional,
        #[SerializedName('special')] private readonly array $special,
        #[SerializedName('variant')] private readonly array $variant,
    ) {
    }

    /** @return array<int, CategoryForm> */
    public function getCategory(): array
    {
        return $this->category;
    }

    /** @return array<int, RegionalForm> */
    public function getRegional(): array
    {
        return $this->regional;
    }

    /** @return array<int, SpecialForm> */
    public function getSpecial(): array
    {
        return $this->special;
    }

    /** @return array<int, VariantForm> */
    public function getVariant(): array
    {
        return $this->variant;
    }
}
```

- [ ] **Step 4 : Migrer `Labels`**

Remplacer les 4 paramètres `category_forms`/`regional_forms`/`special_forms`/`variant_forms` par un seul, **supprimer** les 4 getters de compat, exposer `getForms()` :

```php
#[SerializedName('forms')] private readonly Forms $forms,
// …
public function getForms(): Forms
{
    return $this->forms;
}
```

Mettre à jour le phpDoc du constructeur. `catch_states`, `types`, `game_bundles`, `collections` inchangés.

- [ ] **Step 5 : Adapter les contrôleurs**

Dans `AlbumIndexController` et `ElectionIndexController`, remplacer les 4 variables de template `categoryForms`/… par une seule `forms` :

```php
// avant
'categoryForms' => $labels->getCategoryForms(),
'regionalForms' => $labels->getRegionalForms(),
'specialForms'  => $labels->getSpecialForms(),
'variantForms'  => $labels->getVariantForms(),
// après
'forms' => $labels->getForms(),
```

- [ ] **Step 6 : Adapter `_dex_filters_blocks.html.twig`**

Remplacer les 4 boucles : `{% for item in categoryForms %}` → `{% for item in forms.category %}` ; idem `forms.regional`, `forms.special`, `forms.variant`. Les accès `item.slug`, `item.frenchName`, `item.name` restent inchangés (getters d'`AbstractForm`).

- [ ] **Step 7 : Migrer les 3 fixtures `labels.json`**

Remplacer les 4 tableaux de tête :
```json
"category_forms": [ ... ],
"regional_forms": [ ... ],
"special_forms":  [ ... ],
"variant_forms":  [ ... ],
```
par un bloc unique (mêmes objets `{slug,name,french_name}`, valeurs et échappement préservés) :
```json
"forms": {
  "category": [ ... ],
  "regional": [ ... ],
  "special":  [ ... ],
  "variant":  [ ... ]
}
```

- [ ] **Step 8 : Mettre à jour les tests + stub**

- `LabelsTest` (unit + integration) : construction/JSON via le bloc `forms` imbriqué ; remplacer les assertions `getCategoryForms()` par `getForms()->getCategory()`.
- `ResponseObjectTrait::getStubLabels()` : `new Labels(..., new Forms([...], [...], [...], [...]), ...)`.

- [ ] **Step 9 : Vérification (manuelle)**

Run : `docker compose exec php php vendor/bin/phpunit --filter 'Labels|Forms'`
Expected : PASS.

- [ ] **Step 10 : Pas de commit.**

---

## Task 3 : reports — `nb` → `count` + objets imbriqués

`GetReportsService` renvoie du **JSON brut** (`array`), sans VO : toute la migration est au niveau **templates + fixtures**.

**Files:**
- Modify: `templates/Admin/_reports.html.twig`, `templates/Admin/_reports_scripts.html.twig`
- Fixtures: `tests/resources/moco/Back/responses/reports.json`, `tests/resources/unit/service/back/reports.json`
- Test: `tests/src/Integration/Controller/AdminControllerTest.php` (ou équivalent rendant `_reports*`)

**Interfaces:** aucune signature PHP modifiée.

- [ ] **Step 1 : Migrer `_reports.html.twig`**

- `row.nb` / `d.nb` → `row.count` / `d.count`.
- Réductions : `reduce((total, d) => total + d.count)`.
- Libellés : `row.catch_state.name` / `row.catch_state.french_name` (au lieu d'un champ plat).

- [ ] **Step 2 : Migrer `_reports_scripts.html.twig`**

- `d.nb` → `d.count`.
- `dex_usage` : `d.dex.name` / `d.dex.french_name` (et `d.dex.slug` si besoin de clé).
- `catch_state_usage` : `d.catch_state.name` / `d.catch_state.french_name` / `d.catch_state.color`.

- [ ] **Step 3 : Migrer les fixtures `reports.json` (×2)**

Pour chaque entrée :
```json
// avant
{ "nb": 2, "dex": { "name": "Home", "french_name": "Home" } }
{ "nb": 11, "catch_state": { "name": "No", "french_name": "Non", "color": "#e57373" } }
// après
{ "count": 2, "dex": { "slug": "home", "name": "Home", "french_name": "Home" } }
{ "count": 11, "catch_state": { "slug": "no", "name": "No", "french_name": "Non", "color": "#e57373" } }
```
Ajouter aussi le tableau `catch_state_counts_defined_by_trainer` (`{count, trainer:{external_id}}`) si la forme cible l'exige et qu'un consommateur le lit.

- [ ] **Step 4 : Vérifier les assertions de test**

Les tests d'intégration `Admin` n'assertent en principe que des **comptages/titres** (inchangés). Si une assertion lit un libellé précis, l'ajuster à la nouvelle source (`catch_state.name`).

- [ ] **Step 5 : Vérification (manuelle)**

Run : `docker compose exec php php vendor/bin/phpunit --filter 'Admin|Report'`
Expected : PASS.

- [ ] **Step 6 : Pas de commit.**

---

## Task 4 : election/top — plat → imbriqué (`pokemon`/`score`)

**Files:**
- Create: `src/ResponseObject/Election/TopPokemonScore.php`, `TopPokemonLabels.php`, `TopPokemonInfo.php`
- Modify: `src/ResponseObject/Election/TopPokemon.php`
- Modify: `templates/Election/_top.html.twig`
- Test: `tests/src/Unit/ResponseObject/Election/TopPokemon*Test.php`, intégration `ElectionIndex`
- Fixtures: `tests/resources/unit/service/back/election_top_*.json`, `tests/resources/moco/Back/responses/election/index_*.json`, `tests/resources/integration/back/election_*.json`

**Interfaces:**
- Produces :
  - `TopPokemonScore::getElo(): float`, `isSignificance(): bool`
  - `TopPokemonLabels::getName(): string`, `getFrenchName(): string`, `getSimplifiedName(): string`, `getSimplifiedFrenchName(): string`
  - `TopPokemonInfo::getSlug(): string`, `getLabels(): TopPokemonLabels`, `getNationalDexNumber(): int`, `getIcon(): string`
  - `TopPokemon::getPokemon(): TopPokemonInfo`, `getScore(): TopPokemonScore` (plus aucun getter aplati `getElo()`/`getPokemonSimplifiedName()`…).

- [ ] **Step 0 : Recenser les consommateurs aplatis**

Run : `grep -rn "getElo\|isSignificance\|getPokemonSimplified\|getPokemonName\|getPokemonSlug\|getPokemonIcon\|\.elo\|\.significance\|pokemonSimplified" src/ templates/ tests/`
Attendu : `_top.html.twig`, `TopPokemon*Test`.

- [ ] **Step 1 : Test `TopPokemonScore` (échec attendu)**

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Election;

use App\ResponseObject\Election\TopPokemonScore;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/** @internal */
#[CoversClass(TopPokemonScore::class)]
final class TopPokemonScoreTest extends TestCase
{
    public function testExposesScore(): void
    {
        $score = new TopPokemonScore(1016.0, true);
        self::assertSame(1016.0, $score->getElo());
        self::assertTrue($score->isSignificance());
    }
}
```

- [ ] **Step 2 : Lancer le test (manuel) — doit échouer**

Run : `docker compose exec php php vendor/bin/phpunit --filter TopPokemonScoreTest`
Expected : FAIL (classe absente).

- [ ] **Step 3 : Créer les 3 VO**

`TopPokemonScore.php` :
```php
<?php

declare(strict_types=1);

namespace App\ResponseObject\Election;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class TopPokemonScore
{
    public function __construct(
        #[SerializedName('elo')] private readonly float $elo,
        #[SerializedName('significance')] private readonly bool $significance,
    ) {
    }

    public function getElo(): float
    {
        return $this->elo;
    }

    public function isSignificance(): bool
    {
        return $this->significance;
    }
}
```

`TopPokemonLabels.php` :
```php
<?php

declare(strict_types=1);

namespace App\ResponseObject\Election;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class TopPokemonLabels
{
    public function __construct(
        #[SerializedName('name')] private readonly string $name,
        #[SerializedName('french_name')] private readonly string $frenchName,
        #[SerializedName('simplified_name')] private readonly string $simplifiedName,
        #[SerializedName('simplified_french_name')] private readonly string $simplifiedFrenchName,
    ) {
    }

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
}
```

`TopPokemonInfo.php` :
```php
<?php

declare(strict_types=1);

namespace App\ResponseObject\Election;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class TopPokemonInfo
{
    public function __construct(
        #[SerializedName('slug')] private readonly string $slug,
        #[SerializedName('labels')] private readonly TopPokemonLabels $labels,
        #[SerializedName('national_dex_number')] private readonly int $nationalDexNumber,
        #[SerializedName('pokemon_icon')] private readonly string $icon,
    ) {
    }

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

    public function getIcon(): string
    {
        return $this->icon;
    }
}
```

> Confirmer la clé d'icône réellement émise par le Back (`pokemon_icon` vs `icon`) sur la fixture `election_top_*.json` et aligner `#[SerializedName]`.

- [ ] **Step 4 : Migrer `TopPokemon` (sans getters aplatis)**

```php
public function __construct(
    #[SerializedName('pokemon')] private readonly TopPokemonInfo $pokemon,
    #[SerializedName('score')] private readonly TopPokemonScore $score,
) {
}

public function getPokemon(): TopPokemonInfo
{
    return $this->pokemon;
}

public function getScore(): TopPokemonScore
{
    return $this->score;
}
```

- [ ] **Step 5 : Migrer `_top.html.twig`**

- `item.elo` → `item.score.elo`
- `item.significance` → `item.score.significance`
- `item.pokemonSimplifiedName` → `item.pokemon.labels.simplifiedName`
- `item.pokemonSimplifiedFrenchName` → `item.pokemon.labels.simplifiedFrenchName`

(Twig camelCase ⇒ getters correspondants.)

- [ ] **Step 6 : Migrer les fixtures `election_top` / index**

Chaque item plat :
```json
{ "elo": 1016.0, "significance": true, "pokemon_slug": "butterfree",
  "pokemon_simplified_name": "Butterfree", "pokemon_simplified_french_name": "Papilusion",
  "pokemon_name": "Butterfree", "pokemon_french_name": "Papilusion",
  "national_dex_number": 12, "pokemon_icon": "butterfree" }
```
→ imbriqué :
```json
{
  "pokemon": {
    "slug": "butterfree",
    "labels": {
      "name": "Butterfree", "french_name": "Papilusion",
      "simplified_name": "Butterfree", "simplified_french_name": "Papilusion"
    },
    "national_dex_number": 12,
    "pokemon_icon": "butterfree"
  },
  "score": { "elo": 1016.0, "significance": true }
}
```
Appliquer à **toutes** les fixtures portant `election_top` (vérifier par `grep -rln 'election_top' tests/resources/`).

- [ ] **Step 7 : Réécrire les tests `TopPokemon*` + intégration**

- Tests unitaires des 3 nouveaux VO (couvrir chaque getter).
- `TopPokemonTest` : construction imbriquée, asserts `getPokemon()->getLabels()->getSimplifiedName()` et `getScore()->getElo()`.
- `ResponseObjectTrait::getStubTopPokemon()` : `new TopPokemon(new TopPokemonInfo('…', new TopPokemonLabels(...), 12, '…'), new TopPokemonScore(1016.0, true))`.
- Intégration `ElectionIndex` : assertions de rendu alignées sur les nouveaux chemins.

- [ ] **Step 8 : Vérification (manuelle)**

Run : `docker compose exec php php vendor/bin/phpunit --filter 'TopPokemon|ElectionIndex'`
Expected : PASS.

- [ ] **Step 9 : Pas de commit.**

---

## Task 5 : election/metrics — `completion` imbriqué (+ `view_count`/`win_count`)

**Files:**
- Create: `src/DTO/ElectionMetricsCounts.php`, `src/DTO/ElectionMetricsCompletion.php`
- Modify: `src/DTO/ElectionMetrics.php`
- Modify: `templates/Election/_bar_top.html.twig`
- Test: `tests/src/Unit/DTO/ElectionMetricsTest.php`, intégration `ElectionIndex`
- Fixtures: `tests/resources/unit/service/back/election_metrics_*.json`, `tests/resources/moco/Back/responses/election/index_*.json` (bloc `metrics`), `tests/resources/integration/back/election_index.json`

**Interfaces:**
- Produces :
  - `ElectionMetricsCounts::getSum(): int`, `getMax(): int`
  - `ElectionMetricsCompletion::getUnderMaxCount(): int`, `getAtMaxCount(): int`
  - `ElectionMetrics::getViewCount(): ElectionMetricsCounts`, `getWinCount(): ElectionMetricsCounts`, `getCompletion(): ElectionMetricsCompletion`, `getDexTotalCount(): int`, `getRoundCount(): int`, `getWinnerAverage(): float`, `getTotalRoundCount(): int`.
- Consumes : `ElectionMetrics::createFromArray(array $data): self` alimenté par `ElectionIndexService` depuis `ElectionIndex::getMetrics()`.

> `round_count`, `winner_average`, `total_round_count` sont **spécifiques au Back** (absents de l'API) → restent plats. Seuls `view_count`/`win_count`/`completion` sont imbriqués.

- [ ] **Step 0 : Recenser les consommateurs**

Run : `grep -rn "underMaxViewCount\|maxViewCount\|viewCountSum\|winCountSum\|viewCountMax\|winCountMax\|roundCount\|winnerAverage\|totalRoundCount" src/ templates/ tests/`
Attendu : `_bar_top.html.twig` (underMax/max), `_info.html.twig` (round/total/winnerAverage), `ElectionMetricsTest`.

- [ ] **Step 1 : Test `createFromArray` imbriqué (échec attendu)**

```php
public function testCreateFromNestedArray(): void
{
    $metrics = ElectionMetrics::createFromArray([
        'view_count' => ['sum' => 5, 'max' => 1],
        'win_count' => ['sum' => 10, 'max' => 1],
        'completion' => ['under_max_count' => 15, 'at_max_count' => 15],
        'dex_total_count' => 21,
        'round_count' => 3,
        'winner_average' => 2.5,
        'total_round_count' => 7,
    ]);

    self::assertSame(15, $metrics->getCompletion()->getUnderMaxCount());
    self::assertSame(15, $metrics->getCompletion()->getAtMaxCount());
    self::assertSame(5, $metrics->getViewCount()->getSum());
    self::assertSame(1, $metrics->getWinCount()->getMax());
    self::assertSame(2.5, $metrics->getWinnerAverage());
}
```

Ajouter au moins un test d'erreur (sous-tableau manquant/mal typé) pour le MSI :
```php
public function testCreateFromArrayRejectsMissingCompletion(): void
{
    $this->expectException(\Symfony\Component\OptionsResolver\Exception\MissingOptionsException::class);
    ElectionMetrics::createFromArray(['view_count' => ['sum' => 0, 'max' => 0]]);
}
```

- [ ] **Step 2 : Lancer le test (manuel) — doit échouer**

Run : `docker compose exec php php vendor/bin/phpunit --filter ElectionMetricsTest`
Expected : FAIL.

- [ ] **Step 3 : Créer les VO `ElectionMetricsCounts` et `ElectionMetricsCompletion`**

`ElectionMetricsCounts.php` :
```php
<?php

declare(strict_types=1);

namespace App\DTO;

final class ElectionMetricsCounts
{
    public function __construct(
        public readonly int $sum,
        public readonly int $max,
    ) {
    }

    public function getSum(): int
    {
        return $this->sum;
    }

    public function getMax(): int
    {
        return $this->max;
    }
}
```

`ElectionMetricsCompletion.php` :
```php
<?php

declare(strict_types=1);

namespace App\DTO;

final class ElectionMetricsCompletion
{
    public function __construct(
        public readonly int $underMaxCount,
        public readonly int $atMaxCount,
    ) {
    }

    public function getUnderMaxCount(): int
    {
        return $this->underMaxCount;
    }

    public function getAtMaxCount(): int
    {
        return $this->atMaxCount;
    }
}
```

- [ ] **Step 4 : Refondre `ElectionMetrics`**

Constructeur privé avec sous-objets ; `createFromArray()` configure l'`OptionsResolver` sur la forme imbriquée :

```php
private function __construct(
    public readonly ElectionMetricsCounts $viewCount,
    public readonly ElectionMetricsCounts $winCount,
    public readonly ElectionMetricsCompletion $completion,
    public readonly int $dexTotalCount,
    public readonly int $roundCount,
    public readonly float $winnerAverage,
    public readonly int $totalRoundCount,
) {
}

/**
 * @param array{
 *   view_count: array{sum:int, max:int},
 *   win_count: array{sum:int, max:int},
 *   completion: array{under_max_count:int, at_max_count:int},
 *   dex_total_count:int, round_count:int, winner_average:float, total_round_count:int
 * } $data
 */
public static function createFromArray(array $data): self
{
    $resolver = new OptionsResolver();
    $resolver->setRequired(['view_count', 'win_count', 'completion', 'dex_total_count', 'round_count', 'winner_average', 'total_round_count']);
    $resolver->setAllowedTypes('view_count', 'array');
    $resolver->setAllowedTypes('win_count', 'array');
    $resolver->setAllowedTypes('completion', 'array');
    $resolver->setAllowedTypes('dex_total_count', 'int');
    $resolver->setAllowedTypes('round_count', 'int');
    $resolver->setAllowedTypes('winner_average', ['int', 'float']);
    $resolver->setAllowedTypes('total_round_count', 'int');

    /** @var array{view_count:array{sum:int,max:int}, win_count:array{sum:int,max:int}, completion:array{under_max_count:int,at_max_count:int}, dex_total_count:int, round_count:int, winner_average:int|float, total_round_count:int} $r */
    $r = $resolver->resolve($data);

    return new self(
        new ElectionMetricsCounts(self::int($r['view_count'], 'sum'), self::int($r['view_count'], 'max')),
        new ElectionMetricsCounts(self::int($r['win_count'], 'sum'), self::int($r['win_count'], 'max')),
        new ElectionMetricsCompletion(self::int($r['completion'], 'under_max_count'), self::int($r['completion'], 'at_max_count')),
        $r['dex_total_count'],
        $r['round_count'],
        (float) $r['winner_average'],
        $r['total_round_count'],
    );
}

/** @param array<string, mixed> $sub */
private static function int(array $sub, string $key): int
{
    if (!isset($sub[$key]) || !is_int($sub[$key])) {
        throw new \InvalidArgumentException(sprintf('Missing or invalid metrics key "%s".', $key));
    }

    return $sub[$key];
}
```

Ajouter les getters `getViewCount()/getWinCount()/getCompletion()/getDexTotalCount()/getRoundCount()/getWinnerAverage()/getTotalRoundCount()`. La validation des sous-clés via `self::int()` crée les branches d'erreur nécessaires au MSI.

- [ ] **Step 5 : Migrer `_bar_top.html.twig`**

- `metrics.underMaxViewCount` → `metrics.completion.underMaxCount`
- `metrics.maxViewCount` → `metrics.completion.atMaxCount`
- `metrics.roundCount` / `metrics.totalRoundCount` : inchangés (champs plats).

`_info.html.twig` (round/total/winnerAverage) : **inchangé**.

- [ ] **Step 6 : Migrer les fixtures `metrics`**

Repérer : `grep -rln '"metrics"' tests/resources/` + `grep -rln 'under_max_view_count\|view_count_sum' tests/resources/`.
Pour chaque bloc `metrics` :
```json
// avant
"view_count_sum": 5, "view_count_max": 1,
"win_count_sum": 10, "win_count_max": 1,
"under_max_view_count": 15, "max_view_count": 15,
"dex_total_count": 21, "round_count": 3, "winner_average": 2.5, "total_round_count": 7
// après
"view_count": { "sum": 5, "max": 1 },
"win_count":  { "sum": 10, "max": 1 },
"completion": { "under_max_count": 15, "at_max_count": 15 },
"dex_total_count": 21, "round_count": 3, "winner_average": 2.5, "total_round_count": 7
```

- [ ] **Step 7 : Tests**

- `ElectionMetricsTest` : cas nominal imbriqué + cas d'erreur (sous-tableau manquant / clé mal typée) pour 100 % MSI.
- Intégration `ElectionIndex` : asserts inchangés si seuls comptages/titres ; sinon ajuster.

- [ ] **Step 8 : Vérification (manuelle)**

Run : `docker compose exec php php vendor/bin/phpunit --filter 'ElectionMetrics|ElectionIndex'`
Expected : PASS.

- [ ] **Step 9 : Pas de commit.**

---

## Task 6 : action_logs — objet à clés → liste `action_type`

**Files:**
- Modify: `src/Service/Back/GetActionLogsService.php` (retourne une **liste**)
- Modify: `src/DTO/ActionLogData.php` (`actionType`, `current` nullable)
- Modify: `src/Controller/AdminController.php` (passe la liste)
- Modify: `templates/Admin/_macros.html.twig` (itère + filtre par `action_type`)
- Test: `tests/src/Unit/Service/Back/GetActionLogsServiceTest.php`, intégration `Admin`
- Fixtures: `tests/resources/moco/Back/responses/action-logs.json`, `tests/resources/unit/service/back/action-logs.json`

**Interfaces:**
- Produces : `ActionLogData::getActionType(): string`, `getCurrent(): ?ActionLog`, `getLast(): ?ActionLog` ; `GetActionLogsService::get(): array<int, ActionLogData>` (liste, **plus** keyée).

- [ ] **Step 0 : Vérifier la nullabilité de `current`**

`migration.md` montre `current: null`. Lire `src/ResponseObject/ActionLog.php` et `_macros.html.twig` (gardes `is defined`/`is not empty`) pour confirmer la tolérance au `null`. `current`/`last` deviennent tous deux `?ActionLog`.

- [ ] **Step 1 : Test `GetActionLogsService` (échec attendu)**

```php
public function testReturnsListWithActionType(): void
{
    // JSON Back : tableau d'objets {action_type, current, last}
    $service = $this->buildServiceReturning(__DIR__ . '/../../../resources/unit/service/back/action-logs.json');

    $result = $service->get();

    self::assertIsList($result);
    self::assertSame('update_pokemons', $result[0]->getActionType());
    self::assertNull($result[1]->getCurrent()); // entrée avec current:null
}
```

> Adapter `buildServiceReturning()` au harnais existant du test (mock HTTP / décodeur). S'appuyer sur le test actuel comme modèle.

- [ ] **Step 2 : Lancer le test (manuel) — doit échouer**

Run : `docker compose exec php php vendor/bin/phpunit --filter GetActionLogsServiceTest`
Expected : FAIL.

- [ ] **Step 3 : Migrer `ActionLogData`**

```php
<?php

declare(strict_types=1);

namespace App\DTO;

use App\ResponseObject\ActionLog;

final class ActionLogData
{
    public function __construct(
        public readonly string $actionType,
        public readonly ?ActionLog $current = null,
        public readonly ?ActionLog $last = null,
    ) {
    }

    public function getActionType(): string
    {
        return $this->actionType;
    }

    public function getCurrent(): ?ActionLog
    {
        return $this->current;
    }

    public function getLast(): ?ActionLog
    {
        return $this->last;
    }
}
```

- [ ] **Step 4 : Migrer `GetActionLogsService::get()`**

```php
/**
 * @return array<int, ActionLogData>
 */
public function get(): array
{
    /** @var array<int, array{action_type: string, current: array<string, mixed>|null, last: array<string, mixed>|null}> $rows */
    $rows = $this->jsonDecoder->decode($json);

    $list = [];
    foreach ($rows as $row) {
        $list[] = new ActionLogData(
            $row['action_type'],
            null === $row['current'] ? null : $this->denormalizeActionLog($row['current']),
            null === $row['last'] ? null : $this->denormalizeActionLog($row['last']),
        );
    }

    return $list;
}
```

`denormalizeActionLog()` reproduit la désérialisation `ActionLog` déjà en place (Serializer). Couvrir les 4 branches (`current` null/non-null, `last` null/non-null) pour le MSI.

- [ ] **Step 5 : Adapter `AdminController`**

Passer la liste telle quelle : `'actionLogsData' => $this->getActionLogsService->get()` (le template gère le filtrage).

- [ ] **Step 6 : Migrer `_macros.html.twig`**

Remplacer l'accès par clé `actionLogsData[actionItem]` par une recherche dans la liste :

```twig
{% set entry = null %}
{% for candidate in actionLogsData if candidate.actionType == actionItem %}
    {% set entry = candidate %}
{% endfor %}
{% if entry is not null and entry.current is not null %}
    {% set currentActionLog = entry.current %}
    {# … rendu inchangé : currentActionLog.createdAt / doneAt / errorTrace … #}
{% endif %}
{% if entry is not null and entry.last is not null %}
    {% set lastActionLog = entry.last %}
{% endif %}
```

Conserver les gardes `is not empty` existantes sur `createdAt`/`doneAt`/`errorTrace`/`executionTime`.

- [ ] **Step 7 : Migrer les fixtures `action-logs.json` (×2)**

Objet racine → **tableau** :
```json
// avant
{ "update_pokemons": { "item": "update_pokemons", "current": { ... }, "last": { ... } },
  "calculate_dex_availabilities": { "current": null, "last": { ... } } }
// après
[ { "action_type": "update_pokemons", "current": { ... }, "last": { ... } },
  { "action_type": "calculate_dex_availabilities", "current": null, "last": { ... } } ]
```
Conserver l'ordre et les valeurs ; inclure au moins une entrée `current: null` et une `last: null` pour couvrir les branches.

- [ ] **Step 8 : Tests**

- `GetActionLogsServiceTest` : liste en entrée → liste `ActionLogData` ; couvrir `current: null` et `last: null`.
- Intégration `Admin` : rendu inchangé (le template retrouve l'entrée par `action_type`).

- [ ] **Step 9 : Vérification (manuelle)**

Run : `docker compose exec php php vendor/bin/phpunit --filter 'ActionLog|Admin'`
Expected : PASS.

- [ ] **Step 10 : Pas de commit.**

---

## Task 7 : election/vote — vérification (no-op)

**Files:** aucun changement de code attendu.
- Inspect: `src/Service/Back/PostElectionVoteService.php`, `src/Controller/ElectionVoteController.php`, `src/Service/ElectionVoteService.php`

**Interfaces:** inchangées (`vote(ElectionVote): void`).

- [ ] **Step 1 : Confirmer le no-op**

- Vérifier que `PostElectionVoteService::vote()` retourne `void` et ne désérialise pas la réponse.
- Run : `grep -rn "election_vote\|pokemons_elo\|trainer_external_id" src/ templates/`
  Attendu : aucun parsing de la réponse de vote côté Web.
- Confirmer que le corps **Web→Back** reste `{ "winners_slugs": [...], "losers_slugs": [...] }` (l'imbrication `trainer`+`score` est un détail Back→API, non consommé par le Web).

- [ ] **Step 2 : (Optionnel) fixtures documentaires**

Si l'on tient à l'exactitude documentaire, mettre `tests/resources/moco/Back/responses/election/election_vote.json` et `tests/resources/unit/service/back/election_vote_*.json` à la forme `election_vote.trainer.external_id` + `pokemons_elo` + `score`. **Sans impact fonctionnel** (non parsées). Sinon ignorer.

- [ ] **Step 3 : Vérification (manuelle)**

Run : `docker compose exec php php vendor/bin/phpunit --filter 'ElectionVote'`
Expected : PASS.

- [ ] **Step 4 : Pas de commit.**

---

## Vérification finale (manuelle, hors périmètre d'écriture)

- [ ] `make quality` — 0 erreur PHPStan/Psalm/PHPMD/Deptrac/CS Fixer + W3C.
- [ ] `make tests` — unit + intégration + browser (Chrome + Firefox) verts.
- [ ] `make measures` — 100 % couverture + 100 % MSI.

> Régénération des snapshots : décommenter `file_put_contents('tests/last.html', $client->getCrawler()->html())` dans le test, relancer le test ciblé, copier le rendu dans le répertoire de référence, recommenter.

---

## Self-Review — Couverture du spec

| Changement API (`migration.md`) | Atteint le Web ? | Task | Approche B |
|---|---|---|---|
| `GET /forms` (consolidation) | Oui (via `/labels`) | Task 2 | VO `Forms` + template `forms.category` |
| `GET /game_bundles` (generation imbriqué) | Oui (via `/labels`) | Task 1 | VO `Generation`, `bundle.generation.slug` |
| `GET /reports` (slugs + count) | Oui | Task 3 | templates `.count` + `dex.*`/`catch_state.*` |
| `GET /election/top` (imbriqué) | Oui | Task 4 | VO `pokemon`/`score`, `item.score.elo` |
| `GET /election/metrics` (`completion`) | Oui | Task 5 | VO `completion`, `metrics.completion.*` |
| `GET /action_logs` (tableau `action_type`) | Oui | Task 6 | service liste + template filtre |
| `POST /election/vote` (`trainer`+`score`) | Réponse non consommée | Task 7 | No-op vérifié |
| `GET /album/*` (ajouts non-breaking) | Non (additif) | — | N/A |
| `GET /pokemons/to_choose` (ajouts) | Non (additif) | — | N/A |
| `GET /debogage/*` | Non consommé | — | N/A |
| Renommages camelCase→snake_case | Déjà snake_case Back→Web | — | N/A |

**Cohérence des types (vérifiée) :** `Generation::getSlug` (T1) ↔ `GameBundle::getGeneration` (T1) ; `Forms::getCategory` (T2) ↔ template `forms.category` (T2) ; `TopPokemonScore::getElo`/`isSignificance` (T4) ↔ `item.score.elo`/`item.score.significance` (T4) ; `ElectionMetricsCompletion::getUnderMaxCount`/`getAtMaxCount` (T5) ↔ `metrics.completion.underMaxCount`/`atMaxCount` (T5) ; `ActionLogData::getActionType` (T6) ↔ template `candidate.actionType` (T6).

**Risques résiduels :**
1. **Surface templates (propre à B)** : 5 templates modifiés (`_dex_filters_blocks`, `_top`, `_bar_top`, `_reports*`, `_macros`) → réexécuter les tests de rendu intégration/browser.
2. **Clé d'icône `election_top`** (`pokemon_icon` vs `icon`) : confirmer sur fixture réelle avant de figer le `#[SerializedName]` (T4 Step 3).
3. **MSI 100 %** : couvrir les branches d'erreur de `ElectionMetrics::int()` (T5) et les 4 combinaisons `current`/`last` null (T6).
4. **Réconciliation Back** : confirmer chaque forme composite contre les fixtures `functional/controller/*` du Back une fois propagé ; le Back fait foi.
