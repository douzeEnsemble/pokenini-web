# AlbumFilterBag Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Remplacer le tableau mixte `string[]|string[][]` retourné par `FromRequest` par un value object typé `AlbumFilterBag`, absorber la logique de `Mapping` dedans, et supprimer la classe `Mapping`.

**Architecture:** `AlbumFilterBag` est un `readonly` value object avec deux propriétés typées (`stringFilters: array<string, string>` et `multipleFilters: array<string, string[]>`). Il expose `toRouteParams()` pour `redirectToRoute` et `toApiParams()` pour l'API backend. `FromRequest` le construit, `Mapping` est supprimée.

**Tech Stack:** PHP 8.4, Symfony 8.0, PHPUnit, PHPStan niveau 9, Psalm, Deptrac.

---

### Task 1 : Créer `AlbumFilterBag` (TDD)

**Files:**
- Create: `src/AlbumFilters/AlbumFilterBag.php`
- Create: `tests/src/Unit/AlbumFilters/AlbumFilterBagTest.php`
- Delete: `tests/src/Unit/AlbumFilters/MappingTest.php`

- [ ] **Step 1 : Écrire le test (doit échouer)**

Créer `tests/src/Unit/AlbumFilters/AlbumFilterBagTest.php` :

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\AlbumFilters;

use App\AlbumFilters\AlbumFilterBag;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(AlbumFilterBag::class)]
final class AlbumFilterBagTest extends TestCase
{
    public function testToApiParams(): void
    {
        $bag = new AlbumFilterBag(
            stringFilters: [
                'cs' => 'no',
                'f' => 'pichu',
            ],
            multipleFilters: [
                'fc' => ['cat1', 'cat2'],
                'fr' => ['reg1', 'reg2'],
                'fs' => ['spe1', 'spe2'],
                'fv' => ['var1', 'var2'],
                'at' => ['typ-a.1', 'type-a.2'],
                't1' => ['typ1.1', 'type1.2'],
                't2' => ['typ2.1', 'type2.2'],
                'ogb' => ['ogb1', 'ogb2'],
                'gba' => ['gba1', 'gba2', '!gba3'],
                'gbsa' => ['gbsa1', 'gbsa2', '!gbsa3'],
                'ca' => ['ca1', '!ca2'],
            ],
        );

        $this->assertEquals(
            [
                'catch_states' => ['no'],
                'families' => ['pichu'],
                'category_forms' => ['cat1', 'cat2'],
                'regional_forms' => ['reg1', 'reg2'],
                'special_forms' => ['spe1', 'spe2'],
                'variant_forms' => ['var1', 'var2'],
                'any_types' => ['typ-a.1', 'type-a.2'],
                'primary_types' => ['typ1.1', 'type1.2'],
                'secondary_types' => ['typ2.1', 'type2.2'],
                'original_game_bundles' => ['ogb1', 'ogb2'],
                'game_bundle_availabilities' => ['gba1', 'gba2', '!gba3'],
                'game_bundle_shiny_availabilities' => ['gbsa1', 'gbsa2', '!gbsa3'],
                'collection_availabilities' => ['ca1', '!ca2'],
            ],
            $bag->toApiParams(),
        );
    }

    public function testToRouteParams(): void
    {
        $bag = new AlbumFilterBag(
            stringFilters: ['cs' => 'no', 'f' => 'pichu'],
            multipleFilters: ['fc' => ['cat1', 'cat2'], 'ogb' => ['ogb1']],
        );

        $this->assertEquals(
            [
                'cs' => 'no',
                'f' => 'pichu',
                'fc' => ['cat1', 'cat2'],
                'ogb' => ['ogb1'],
            ],
            $bag->toRouteParams(),
        );
    }

    public function testEmptyBag(): void
    {
        $bag = new AlbumFilterBag();

        $this->assertSame([], $bag->toApiParams());
        $this->assertSame([], $bag->toRouteParams());
    }
}
```

- [ ] **Step 2 : Lancer le test — vérifier qu'il échoue**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Unit/AlbumFilters/AlbumFilterBagTest.php
```

Attendu : FAIL — `Class "App\AlbumFilters\AlbumFilterBag" not found`.

- [ ] **Step 3 : Créer `AlbumFilterBag`**

Créer `src/AlbumFilters/AlbumFilterBag.php` :

```php
<?php

declare(strict_types=1);

namespace App\AlbumFilters;

final readonly class AlbumFilterBag
{
    private const array MAPPING = [
        'cs'   => 'catch_states',
        'f'    => 'families',
        'fc'   => 'category_forms',
        'fr'   => 'regional_forms',
        'fs'   => 'special_forms',
        'fv'   => 'variant_forms',
        'at'   => 'any_types',
        't1'   => 'primary_types',
        't2'   => 'secondary_types',
        'ogb'  => 'original_game_bundles',
        'gba'  => 'game_bundle_availabilities',
        'gbsa' => 'game_bundle_shiny_availabilities',
        'ca'   => 'collection_availabilities',
    ];

    /**
     * @param array<string, string>   $stringFilters
     * @param array<string, string[]> $multipleFilters
     */
    public function __construct(
        public array $stringFilters = [],
        public array $multipleFilters = [],
    ) {}

    /**
     * @return array<string, string|string[]>
     */
    public function toRouteParams(): array
    {
        return array_merge($this->stringFilters, $this->multipleFilters);
    }

    /**
     * @return array<string, string[]>
     */
    public function toApiParams(): array
    {
        $result = [];

        foreach ($this->stringFilters as $key => $value) {
            $result[self::MAPPING[$key]] = [$value];
        }

        foreach ($this->multipleFilters as $key => $values) {
            $result[self::MAPPING[$key]] = $values;
        }

        return $result;
    }
}
```

- [ ] **Step 4 : Lancer le test — vérifier qu'il passe**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Unit/AlbumFilters/AlbumFilterBagTest.php
```

Attendu : 3 tests, vert.

- [ ] **Step 5 : Supprimer `MappingTest.php`**

```bash
rm tests/src/Unit/AlbumFilters/MappingTest.php
```

- [ ] **Step 6 : Commit**

```bash
git add src/AlbumFilters/AlbumFilterBag.php tests/src/Unit/AlbumFilters/AlbumFilterBagTest.php tests/src/Unit/AlbumFilters/MappingTest.php
git commit -m "feat: introduce AlbumFilterBag value object, remove MappingTest"
```

---

### Task 2 : Adapter `FromRequest` pour retourner `AlbumFilterBag`

**Files:**
- Modify: `src/AlbumFilters/FromRequest.php`
- Modify: `tests/src/Unit/AlbumFilters/FromRequestTest.php`

- [ ] **Step 1 : Mettre à jour `FromRequestTest`**

Remplacer le contenu de `tests/src/Unit/AlbumFilters/FromRequestTest.php` :

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\AlbumFilters;

use App\AlbumFilters\AlbumFilterBag;
use App\AlbumFilters\FromRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[CoversClass(FromRequest::class)]
final class FromRequestTest extends TestCase
{
    public function testGet(): void
    {
        $request = new Request([
            'cs' => 'no',
            'f' => 'pichu',
            'fc' => ['cat1', 'cat2', null],
            'fr' => ['reg1', 'reg2'],
            'fs' => ['spe1', 'spe2'],
            'fv' => ['var1', 'var2', null],
            'at' => ['typ-a.1', 'type-a.2'],
            't1' => ['typ1.1', 'type1.2'],
            't2' => ['typ2.1', 'type2.2'],
            'ogb' => ['ogb1', 'ogb2'],
            'gba' => ['gba1', 'gba2'],
            'gbsa' => ['gbsa1', 'gbsa2'],
            'ca' => ['ca1', 'ca2'],
        ]);

        $bag = FromRequest::get($request);

        $this->assertInstanceOf(AlbumFilterBag::class, $bag);
        $this->assertEquals(
            ['cs' => 'no', 'f' => 'pichu'],
            $bag->stringFilters,
        );
        $this->assertEquals(
            [
                'fc' => ['cat1', 'cat2'],
                'fr' => ['reg1', 'reg2'],
                'fs' => ['spe1', 'spe2'],
                'fv' => ['var1', 'var2'],
                'at' => ['typ-a.1', 'type-a.2'],
                't1' => ['typ1.1', 'type1.2'],
                't2' => ['typ2.1', 'type2.2'],
                'ogb' => ['ogb1', 'ogb2'],
                'gba' => ['gba1', 'gba2'],
                'gbsa' => ['gbsa1', 'gbsa2'],
                'ca' => ['ca1', 'ca2'],
            ],
            $bag->multipleFilters,
        );
    }

    public function testGetWithNegatives(): void
    {
        $request = new Request([
            'cs' => '!no',
            'f' => 'pichu',
            'ogb' => ['ogb1', 'ogb2'],
            'gba' => ['gba1', 'gba2', '!gba3'],
            'gbsa' => ['gbsa1', 'gbsa2', '!gbsa3'],
            'ca' => ['ca1', '!ca2'],
        ]);

        $bag = FromRequest::get($request);

        $this->assertEquals(['cs' => '!no', 'f' => 'pichu'], $bag->stringFilters);
        $this->assertEquals(
            [
                'ogb' => ['ogb1', 'ogb2'],
                'gba' => ['gba1', 'gba2', '!gba3'],
                'gbsa' => ['gbsa1', 'gbsa2', '!gbsa3'],
                'ca' => ['ca1', '!ca2'],
            ],
            $bag->multipleFilters,
        );
    }

    public function testGetEmptyRequest(): void
    {
        $bag = FromRequest::get(new Request());

        $this->assertEquals(new AlbumFilterBag(), $bag);
    }
}
```

- [ ] **Step 2 : Lancer le test — vérifier qu'il échoue**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Unit/AlbumFilters/FromRequestTest.php
```

Attendu : FAIL — `FromRequest::get()` retourne encore un `array`.

- [ ] **Step 3 : Mettre à jour `FromRequest`**

Remplacer le contenu de `src/AlbumFilters/FromRequest.php` :

```php
<?php

declare(strict_types=1);

namespace App\AlbumFilters;

use Symfony\Component\HttpFoundation\Request;

final class FromRequest
{
    private const array STRING_FILTERS = [
        'cs',
        'f',
    ];

    private const array MULTIPLE_FILTERS = [
        'fc',
        'fr',
        'fs',
        'fv',
        'at',
        't1',
        't2',
        'ogb',
        'gba',
        'gbsa',
        'ca',
    ];

    public static function get(Request $request): AlbumFilterBag
    {
        $stringFilters = [];
        $multipleFilters = [];

        foreach (self::STRING_FILTERS as $filterName) {
            if ($request->query->has($filterName)) {
                $stringFilters[$filterName] = $request->query->getString($filterName);
            }
        }

        foreach (self::MULTIPLE_FILTERS as $filterName) {
            if ($request->query->has($filterName)) {
                /** @var null|string[] $values */
                $values = $request->query->all()[$filterName];
                $values ??= [];
                $multipleFilters[$filterName] = array_filter($values);
            }
        }

        return new AlbumFilterBag(
            stringFilters: $stringFilters,
            multipleFilters: $multipleFilters,
        );
    }
}
```

- [ ] **Step 4 : Lancer le test — vérifier qu'il passe**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Unit/AlbumFilters/FromRequestTest.php
```

Attendu : 3 tests, vert.

- [ ] **Step 5 : Commit**

```bash
git add src/AlbumFilters/FromRequest.php tests/src/Unit/AlbumFilters/FromRequestTest.php
git commit -m "refactor: FromRequest::get() returns AlbumFilterBag"
```

---

### Task 3 : Mettre à jour les controllers

**Files:**
- Modify: `src/Controller/AlbumIndexController.php`
- Modify: `src/Controller/ElectionIndexController.php`
- Modify: `src/Controller/ElectionVoteController.php`
- Delete: `src/AlbumFilters/Mapping.php`

- [ ] **Step 1 : Mettre à jour `AlbumIndexController`**

Dans `src/Controller/AlbumIndexController.php`, supprimer l'import de `Mapping` et remplacer les deux lignes concernées :

Avant :
```php
use App\AlbumFilters\FromRequest;
use App\AlbumFilters\Mapping;
// ...
        $filters = FromRequest::get($request);
        $apiFilters = Mapping::get($filters);
```

Après :
```php
use App\AlbumFilters\FromRequest;
// ...
        $filterBag = FromRequest::get($request);
        $apiFilters = $filterBag->toApiParams();
```

- [ ] **Step 2 : Mettre à jour `ElectionIndexController`**

Dans `src/Controller/ElectionIndexController.php`, même changement :

Avant :
```php
use App\AlbumFilters\FromRequest;
use App\AlbumFilters\Mapping;
// ...
        $filters = FromRequest::get($request);
        $apiFilters = Mapping::get($filters);
```

Après :
```php
use App\AlbumFilters\FromRequest;
// ...
        $filterBag = FromRequest::get($request);
        $apiFilters = $filterBag->toApiParams();
```

- [ ] **Step 3 : Mettre à jour `ElectionVoteController`**

Dans `src/Controller/ElectionVoteController.php`, remplacer l'usage de `$filters` dans `array_merge` :

Avant :
```php
        $filters = FromRequest::get($request);

        return $this->redirectToRoute(
            'app_electionindex_index',
            array_merge(
                [
                    'dexSlug' => $electionVote->dexSlug,
                    'electionSlug' => $electionVote->electionSlug,
                ],
                $filters,
            ),
        );
```

Après :
```php
        $filterBag = FromRequest::get($request);

        return $this->redirectToRoute(
            'app_electionindex_index',
            array_merge(
                [
                    'dexSlug' => $electionVote->dexSlug,
                    'electionSlug' => $electionVote->electionSlug,
                ],
                $filterBag->toRouteParams(),
            ),
        );
```

- [ ] **Step 4 : Supprimer `Mapping.php`**

```bash
rm src/AlbumFilters/Mapping.php
```

- [ ] **Step 5 : Lancer les tests unitaires**

```bash
make tests-unit
```

Attendu : tous les tests unitaires verts (aucune référence à `Mapping` ne doit subsister).

- [ ] **Step 6 : Commit**

```bash
git add src/Controller/AlbumIndexController.php src/Controller/ElectionIndexController.php src/Controller/ElectionVoteController.php src/AlbumFilters/Mapping.php
git commit -m "refactor: use AlbumFilterBag in controllers, remove Mapping class"
```

---

### Task 4 : Vérification qualité complète

**Files:** aucun fichier supplémentaire

- [ ] **Step 1 : Lancer tous les tests**

```bash
make tests-unit
```

Attendu : vert.

- [ ] **Step 2 : Lancer les outils de qualité**

```bash
make code-quality
```

Attendu : PHPStan niveau 9 vert, Psalm vert, Deptrac 0 violations, PHP CS Fixer sans diff.

Si PHP CS Fixer signale des diffs :
```bash
make phpcsfixer-fix
git add -p
git commit -m "style: fix code style after AlbumFilterBag refactor"
```

- [ ] **Step 3 : Mettre à jour `doc/improvement.md`**

Dans `doc/improvement.md`, au point **#16**, ajouter après `**Fichiers** : ...` :

```
**Traité** : `AlbumFilterBag` readonly value object avec `toApiParams()` (absorbe `Mapping`) et `toRouteParams()`. `FromRequest::get()` retourne `AlbumFilterBag`. `Mapping` supprimée. PHPStan niveau 9 + tests unitaires verts.
```

- [ ] **Step 4 : Commit final**

```bash
git add doc/improvement.md
git commit -m "docs: mark improvement #16 as done"
```
