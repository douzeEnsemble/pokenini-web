# API Response Shape Migration Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Align pokenini-web with the new pokenini-back API shapes: nested `catch_state` in `report.detail`, flat `metrics` keys, and nested `flags` objects in dex list endpoints.

**Architecture:** Three independent shape changes affect PHP deserialization (ReportDetail, ElectionMetrics), Twig templates (dex flags), and all test fixtures in bulk. A single Python batch script handles fixture migrations to avoid editing ~60 JSON files by hand.

**Tech Stack:** PHP 8.5, Symfony Serializer, Twig, PHPUnit, Moco fixture JSON files, Python 3 (host-side batch script).

## Global Constraints

- No git commits — ever. (User constraint.)
- No test execution in this session — plan documents the TDD cycle for a future executor.
- All PHP files: `declare(strict_types=1)`, `final` classes for ResponseObjects/DTOs/tests.
- All test classes: `/** @internal */`, `#[CoversClass(...)]`, extend `TestCase` or `KernelTestCase`.
- Quality gates must pass before any push: `make quality` + `make measures` (100% coverage, 100% MSI).
- Docker toolchain: run quality and tests via `docker compose exec php ...` — no host PHP.
- Endpoint spec is the source of truth: `/home/renaud/projects/pokenini-back/doc/endpoints.md`.

## Catch State Color Map

Used throughout fixture transformations:

| slug | color |
|------|-------|
| `no` | `#e57373` |
| `toevolve` | `#9575cd` |
| `tobreed` | `#4fc3f7` |
| `totransfer` | `#ffd54f` |
| `totrade` | `#ff9100` |
| `yes` | `#66bb6a` |

---

## File Structure

### PHP Source (Modify)

| File | Change |
|------|--------|
| `src/ResponseObject/Album/ReportDetail.php` | Replace 4 flat params with `CatchState $catchState` + `int $count`; add `getColor()` delegation |
| `src/DTO/ElectionMetrics.php` | Flat keys in `createFromArray()`; remove `int()` helper; update docblock |
| `src/ResponseObject/Election/ElectionIndex.php` | Docblock only — update `$metrics` array shape |

### Twig Templates (Modify)

| File | Change |
|------|--------|
| `templates/AlbumDexList/_macro.html.twig` | `dex.is_*` → `dex.flags.is_*`; `item.is_on_home` → `item.flags.is_on_home` |
| `templates/Trainer/Section/_dex.html.twig` | `dex.is_*` → `dex.flags.is_*`; `dex[attribute]` → `dex.flags[attribute]` |

### PHP Tests (Modify)

| File | Change |
|------|--------|
| `tests/src/Common/Traits/ResponseObjectTrait.php` | All 4 `ReportDetail` calls: pass `new CatchState(...)` as first arg |
| `tests/src/Integration/ResponseObject/Album/ReportDetailTest.php` | New JSON format; add `getColor()` assertion |
| `tests/src/Integration/ResponseObject/Album/ReportTest.php` | New JSON format for `detail` array |
| `tests/src/Unit/DTO/ElectionMetricsTest.php` | Flat format in all data providers; remove `testMissingSubKey` + `testBadSubKeyType` |

### Batch Migration Script (Create)

| File | Purpose |
|------|---------|
| `scripts/migrate_api_responses.py` | Transforms all ~60 fixture JSON files in one run |

### Fixtures (via batch script — do NOT edit by hand)

**report.detail transform** (flat → nested `catch_state`):
- All 27 files under `tests/resources/moco/Back/responses/album/**/*.json`
- `tests/resources/unit/service/back/album_lite.json`
- `tests/resources/unit/service/back/pokedex_lite.json`
- `tests/resources/moco/Back/responses/election/index_*.json` (pokedex section)
- `tests/resources/integration/back/election_index.json` (pokedex section)

**metrics transform** (nested → flat):
- All 9 `tests/resources/moco/Back/responses/election/index_*.json`
- `tests/resources/integration/back/election_index.json`
- `tests/resources/unit/service/back/election_metrics_demo_pref.json`
- `tests/resources/unit/service/back/election_metrics_home_fav.json`

**dex list flags nesting** (flat → `flags: {}`):
- 6 files under `tests/resources/moco/Back/responses/dex/`
- `tests/resources/moco/Back/responses/election/election_dex_list.json`
- `tests/resources/moco/Back/responses/election/election_dex_list_admin.json`
- 8 files `tests/resources/unit/service/back/dex*.json`
- 3 files `tests/resources/unit/service/back/election_dex_list*.json`

---

## Task 1: Refactor `ReportDetail` — PHP class

**Files:**
- Modify: `src/ResponseObject/Album/ReportDetail.php`

**Interfaces:**
- Produces: `ReportDetail(CatchState $catchState, int $count)` constructor; getters: `getSlug()`, `getName()`, `getFrenchName()`, `getColor()`, `getCount()` (delegation via `$catchState`)
- `CatchState` is `App\ResponseObject\Label\CatchState` with constructor `(string $name, string $frenchName, string $slug, string $color)`

- [ ] **Step 1: Write the new `ReportDetail.php`**

```php
<?php

declare(strict_types=1);

namespace App\ResponseObject\Album;

use App\ResponseObject\Label\CatchState;
use Symfony\Component\Serializer\Attribute\SerializedName;

final class ReportDetail
{
    public function __construct(
        #[SerializedName('catch_state')]
        private readonly CatchState $catchState,
        #[SerializedName('count')]
        private readonly int $count,
    ) {}

    public function getSlug(): string
    {
        return $this->catchState->getSlug();
    }

    public function getName(): string
    {
        return $this->catchState->getName();
    }

    public function getFrenchName(): string
    {
        return $this->catchState->getFrenchName();
    }

    public function getColor(): string
    {
        return $this->catchState->getColor();
    }

    public function getCount(): int
    {
        return $this->count;
    }
}
```

- [ ] **Step 2: Update `ResponseObjectTrait.php`** — 4 `ReportDetail` instantiations at lines 122-134 (report) and 141-153 (filteredReport)

Replace all 4 calls:

```php
// BEFORE — remove these 4 old-style calls:
new ReportDetail('no',  'No',  'Non', 1),
new ReportDetail('yes', 'Yes', 'Oui', 2),
new ReportDetail('no',  'No',  'Non', 1),
new ReportDetail('yes', 'Yes', 'Oui', 0),

// AFTER — add CatchState as first argument:
new ReportDetail(new CatchState('No',  'Non', 'no',  '#e57373'), 1),
new ReportDetail(new CatchState('Yes', 'Oui', 'yes', '#66bb6a'), 2),
new ReportDetail(new CatchState('No',  'Non', 'no',  '#e57373'), 1),
new ReportDetail(new CatchState('Yes', 'Oui', 'yes', '#66bb6a'), 0),
```

`CatchState` constructor is `(string $name, string $frenchName, string $slug, string $color)`.

Also add import at the top of the trait file (it's already imported at line 26 but verify):
```php
use App\ResponseObject\Label\CatchState;
```

---

## Task 2: Update `ReportDetail` integration tests

**Files:**
- Modify: `tests/src/Integration/ResponseObject/Album/ReportDetailTest.php`
- Modify: `tests/src/Integration/ResponseObject/Album/ReportTest.php`

**Interfaces:**
- Consumes: `ReportDetail` from Task 1

- [ ] **Step 1: Update `ReportDetailTest.php`** — replace inline JSON and add `getColor()` assertion

```php
$json = <<<'JSON'
    {
        "catch_state": {
            "name": "Yes",
            "french_name": "Oui",
            "slug": "yes",
            "color": "#66bb6a"
        },
        "count": 20
    }
    JSON;

$object = $serializer->deserialize($json, ReportDetail::class, 'json');

$this->assertInstanceOf(ReportDetail::class, $object);
$this->assertSame('yes', $object->getSlug());
$this->assertSame('Yes', $object->getName());
$this->assertSame('Oui', $object->getFrenchName());
$this->assertSame('#66bb6a', $object->getColor());
$this->assertSame(20, $object->getCount());
```

- [ ] **Step 2: Update `ReportTest.php`** — replace the inline JSON `detail` array (lines 32-44) with the new nested format

```php
$json = <<<'JSON'
    {
        "total": 37,
        "total_caught": 20,
        "total_uncaught": 17,
        "detail": [
            {
                "catch_state": {
                    "name": "No",
                    "french_name": "Non",
                    "slug": "no",
                    "color": "#e57373"
                },
                "count": 1
            },
            {
                "catch_state": {
                    "name": "Yes",
                    "french_name": "Oui",
                    "slug": "yes",
                    "color": "#66bb6a"
                },
                "count": 20
            }
        ]
    }
    JSON;
```

All assertions below the `deserialize()` call stay unchanged.

- [ ] **Step 3: Verify test commands (do not run yet)**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Integration/ResponseObject/Album/ReportDetailTest.php
docker compose exec php php vendor/bin/phpunit tests/src/Integration/ResponseObject/Album/ReportTest.php
```

Expected after Task 1+2 complete: PASS. (Before Task 1: FAIL with Symfony Serializer error — missing constructor arg `slug`.)

---

## Task 3: Update `ElectionMetrics` DTO — PHP + test

**Files:**
- Modify: `src/DTO/ElectionMetrics.php`
- Modify: `src/ResponseObject/Election/ElectionIndex.php`
- Modify: `tests/src/Unit/DTO/ElectionMetricsTest.php`

**Interfaces:**
- Consumes: flat metrics array from API — keys: `view_count_sum`, `view_count_max`, `win_count_sum`, `win_count_max`, `under_max_view_count`, `max_view_count`, `dex_total_count`, `round_count`, `winner_average`, `total_round_count`
- Produces: same `ElectionMetrics` object as before (getters unchanged); `ElectionMetricsCompletion(underMaxCount, atMaxCount)` maps `under_max_view_count` → `underMaxCount`, `max_view_count` → `atMaxCount`

- [ ] **Step 1: Update `ElectionMetricsTest.php`**

Replace `validData()` and update all test methods:

```php
private static function validData(): array
{
    return [
        'view_count_sum'       => 82,
        'view_count_max'       => 42,
        'win_count_sum'        => 54,
        'win_count_max'        => 52,
        'under_max_view_count' => 62,
        'max_view_count'       => 27,
        'dex_total_count'      => 50,
        'round_count'          => 7,
        'winner_average'       => 7.71,
        'total_round_count'    => 13,
    ];
}
```

Update `testOk()` input:
```php
$object = ElectionMetrics::createFromArray(
    [
        'view_count_sum'       => 82,
        'view_count_max'       => 42,
        'win_count_sum'        => 54,
        'win_count_max'        => 52,
        'under_max_view_count' => 62,
        'max_view_count'       => 27,
        'dex_total_count'      => 50,
        'round_count'          => 7,
        'winner_average'       => 7.71,
        'total_round_count'    => 13,
    ],
);
// Assertions stay the same
```

Update `testWinnerAverageAcceptsInt()` input:
```php
$object = ElectionMetrics::createFromArray(
    [
        'view_count_sum'       => 5,
        'view_count_max'       => 1,
        'win_count_sum'        => 10,
        'win_count_max'        => 1,
        'under_max_view_count' => 15,
        'max_view_count'       => 15,
        'dex_total_count'      => 21,
        'round_count'          => 3,
        'winner_average'       => 2,
        'total_round_count'    => 7,
    ],
);
```

Update `providerMissingTopLevelProperty()` and `providerBadTopLevelType()` — replace `$topLevelKeys`:
```php
$topLevelKeys = [
    'view_count_sum',
    'view_count_max',
    'win_count_sum',
    'win_count_max',
    'under_max_view_count',
    'max_view_count',
    'dex_total_count',
    'round_count',
    'winner_average',
    'total_round_count',
];
```

For `providerBadTopLevelType()`, `'view_count_sum' => 'not-valid'` throws `InvalidOptionsException` (OptionsResolver validates `int` type). All 10 keys get the same `'not-valid'` treatment.

**Delete entirely** these methods (they test sub-key validation that no longer exists):
- `testMissingSubKey()`
- `providerMissingSubKey()`
- `testBadSubKeyType()`
- `providerBadSubKeyType()`

Also remove the `@psalm-suppress ArgumentTypeCoercion` and `@phpstan-ignore` blocks from `testMissingTopLevelProperty` and `testBadTopLevelType` — the flat keys are all scalar types, no coercion needed for `'not-valid'` strings.

Actually, keep the `@psalm-suppress`/`@phpstan-ignore` annotations — they suppress the intentional type mismatch in the bad-type test cases.

- [ ] **Step 2: Update `ElectionMetrics.php`**

Replace the entire `createFromArray()` method and remove the `int()` helper:

```php
/**
 * @param array{
 *   view_count_sum: int,
 *   view_count_max: int,
 *   win_count_sum: int,
 *   win_count_max: int,
 *   under_max_view_count: int,
 *   max_view_count: int,
 *   dex_total_count: int,
 *   round_count: int,
 *   winner_average: float|int,
 *   total_round_count: int
 * } $data
 */
public static function createFromArray(array $data): self
{
    $resolver = new OptionsResolver();
    $resolver->setRequired([
        'view_count_sum', 'view_count_max',
        'win_count_sum', 'win_count_max',
        'under_max_view_count', 'max_view_count',
        'dex_total_count', 'round_count', 'winner_average', 'total_round_count',
    ]);
    $resolver->setAllowedTypes('view_count_sum', 'int');
    $resolver->setAllowedTypes('view_count_max', 'int');
    $resolver->setAllowedTypes('win_count_sum', 'int');
    $resolver->setAllowedTypes('win_count_max', 'int');
    $resolver->setAllowedTypes('under_max_view_count', 'int');
    $resolver->setAllowedTypes('max_view_count', 'int');
    $resolver->setAllowedTypes('dex_total_count', 'int');
    $resolver->setAllowedTypes('round_count', 'int');
    $resolver->setAllowedTypes('winner_average', ['int', 'float']);
    $resolver->setAllowedTypes('total_round_count', 'int');

    /** @var array{view_count_sum: int, view_count_max: int, win_count_sum: int, win_count_max: int, under_max_view_count: int, max_view_count: int, dex_total_count: int, round_count: int, winner_average: float|int, total_round_count: int} $resolved */
    $resolved = $resolver->resolve($data);

    return new self(
        new ElectionMetricsCounts($resolved['view_count_sum'], $resolved['view_count_max']),
        new ElectionMetricsCounts($resolved['win_count_sum'], $resolved['win_count_max']),
        new ElectionMetricsCompletion($resolved['under_max_view_count'], $resolved['max_view_count']),
        $resolved['dex_total_count'],
        $resolved['round_count'],
        (float) $resolved['winner_average'],
        $resolved['total_round_count'],
    );
}
```

Remove the `private static function int(array $sub, string $key): int` method entirely (it has no more callers).

- [ ] **Step 3: Update `ElectionIndex.php` docblock**

Replace the `@param` and `@return` docblocks for `$metrics`:

```php
/**
 * @param Pokemon[]    $pokemons
 * @param TopPokemon[] $electionTop
 * @param array{
 *      view_count_sum: int,
 *      view_count_max: int,
 *      win_count_sum: int,
 *      win_count_max: int,
 *      under_max_view_count: int,
 *      max_view_count: int,
 *      dex_total_count: int,
 *      round_count: int,
 *      winner_average: float,
 *      total_round_count: int,
 * } $metrics
 */
```

And the `getMetrics()` return docblock:
```php
/**
 * @return array{
 *  view_count_sum: int,
 *  view_count_max: int,
 *  win_count_sum: int,
 *  win_count_max: int,
 *  under_max_view_count: int,
 *  max_view_count: int,
 *  dex_total_count: int,
 *  round_count: int,
 *  winner_average: float,
 *  total_round_count: int,
 * }
 */
```

- [ ] **Step 4: Verify test command (do not run yet)**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Unit/DTO/ElectionMetricsTest.php
```

Expected after Tasks 3 complete: PASS. (Before Task 3 step 2: FAIL with `MissingOptionsException` for the new keys.)

---

## Task 4: Update dex list Twig templates — flags nesting

**Files:**
- Modify: `templates/AlbumDexList/_macro.html.twig`
- Modify: `templates/Trainer/Section/_dex.html.twig`

**Context:** The dex list endpoints (`/album/dex`, `/election/dex`, `/trainer/dex`) now return flags under a nested `flags: {}` object. The raw JSON arrays decoded by `JsonDecoder::decode()` are passed directly to Twig; no PHP class wraps them. Twig resolves `dex.flags.is_premium` as `$dex['flags']['is_premium']` and `dex.flags[attribute]` as `$dex['flags'][$attribute]`.

- [ ] **Step 1: Update `templates/AlbumDexList/_macro.html.twig`**

Five changes (all accesses of flat flags on raw array items):

Line 19: `{% if dex.is_premium %}`
→ `{% if dex.flags.is_premium %}`

Line 24: `{% if not dex.is_released %}`
→ `{% if not dex.flags.is_released %}`

Line 29: `{% if dex.is_custom %}`
→ `{% if dex.flags.is_custom %}`

Line 78: `{% for item in dex|filter(item => item.is_on_home is same as(true)) %}`
→ `{% for item in dex|filter(item => item.flags.is_on_home is same as(true)) %}`

Line 88: `{% for item in dex|filter(item => item.is_on_home is same as(true)) %}`
→ `{% for item in dex|filter(item => item.flags.is_on_home is same as(true)) %}`

- [ ] **Step 2: Update `templates/Trainer/Section/_dex.html.twig`**

Six changes:

Line 17: `{% set canEdit = dex.is_premium == false or is_granted('ROLE_COLLECTOR') %}`
→ `{% set canEdit = dex.flags.is_premium == false or is_granted('ROLE_COLLECTOR') %}`

Line 20: `{% if dex.is_shiny %}`
→ `{% if dex.flags.is_shiny %}`

Line 25: `{% if dex.is_premium %}`
→ `{% if dex.flags.is_premium %}`

Line 30: `{% if not dex.is_released %}`
→ `{% if not dex.flags.is_released %}`

Line 35: `{% if dex.is_custom %}`
→ `{% if dex.flags.is_custom %}`

Line 56: `{{ dex[attribute] ? 'checked' : '' }}`
→ `{{ dex.flags[attribute] ? 'checked' : '' }}`

---

## Task 5: Create and run the batch fixture migration script

**Files:**
- Create: `scripts/migrate_api_responses.py`

This single Python 3 script (run from the host, in the project root) transforms all fixture files. Run it **after** Tasks 1-4 are complete so the new PHP and Twig code matches the new fixture shapes.

- [ ] **Step 1: Create `scripts/migrate_api_responses.py`**

```python
#!/usr/bin/env python3
"""
Migrate pokenini-web test fixtures to new pokenini-back API shapes.
Run from project root: python3 scripts/migrate_api_responses.py

Changes applied:
  A) report.detail: flat {slug,name,french_name,count} → nested {catch_state:{...},count}
  B) metrics: nested {view_count:{sum,max},...} → flat {view_count_sum,...}
  C) dex list flags: flat is_* keys → nested "flags": { is_*: ... }
"""
import json, os, glob, sys

BASE = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))

CATCH_COLORS = {
    'no':         '#e57373',
    'toevolve':   '#9575cd',
    'tobreed':    '#4fc3f7',
    'totransfer': '#ffd54f',
    'totrade':    '#ff9100',
    'yes':        '#66bb6a',
}

FLAG_KEYS = [
    'is_shiny', 'is_private', 'is_on_home',
    'is_display_form', 'is_released', 'is_premium', 'is_custom',
]


# ─── Transformation helpers ────────────────────────────────────────────────────

def transform_detail_entry(entry: dict) -> dict:
    """{ slug, name, french_name, count } → { catch_state: {..., color}, count }"""
    slug = entry['slug']
    if slug not in CATCH_COLORS:
        raise ValueError(f"Unknown catch_state slug '{slug}' — add it to CATCH_COLORS")
    return {
        'catch_state': {
            'name': entry['name'],
            'french_name': entry['french_name'],
            'slug': slug,
            'color': CATCH_COLORS[slug],
        },
        'count': entry['count'],
    }


def transform_report(report) -> object:
    """Recursively update detail[] in a report dict. Returns [] unchanged."""
    if not isinstance(report, dict):
        return report
    if 'detail' in report and isinstance(report['detail'], list) and report['detail']:
        # Only transform if first entry has old flat format
        if 'slug' in report['detail'][0]:
            report['detail'] = [transform_detail_entry(e) for e in report['detail']]
    return report


def transform_pokedex_section(pokedex: dict) -> dict:
    """Update report and filtered_report inside a Pokedex-shaped dict."""
    if 'report' in pokedex:
        pokedex['report'] = transform_report(pokedex['report'])
    if 'filtered_report' in pokedex:
        fr = pokedex['filtered_report']
        if isinstance(fr, dict) and fr:       # non-empty {} — apply report transform
            pokedex['filtered_report'] = transform_report(fr)
        elif not fr:                           # empty {} or [] — normalise to []
            pokedex['filtered_report'] = []
    return pokedex


def transform_metrics(m: dict) -> dict:
    """Nested metrics dict → flat metrics dict."""
    result: dict = {
        'view_count_sum':       m['view_count']['sum'],
        'view_count_max':       m['view_count']['max'],
        'win_count_sum':        m['win_count']['sum'],
        'win_count_max':        m['win_count']['max'],
        'under_max_view_count': m['completion']['under_max_count'],
        'max_view_count':       m['completion']['at_max_count'],
        'dex_total_count':      m['dex_total_count'],
    }
    for optional in ('round_count', 'winner_average', 'total_round_count'):
        if optional in m:
            result[optional] = m[optional]
    return result


def nest_flags(item: dict) -> dict:
    """Move FLAG_KEYS from top-level into item['flags'] = {...}."""
    flags = {k: item.pop(k) for k in FLAG_KEYS if k in item}
    if not flags:
        return item
    # Rebuild with consistent key order: slug, original_slug, name, french_name, flags, rest
    new_item: dict = {}
    for lead_key in ('slug', 'original_slug', 'name', 'french_name'):
        if lead_key in item:
            new_item[lead_key] = item.pop(lead_key)
    new_item['flags'] = flags
    new_item.update(item)
    return new_item


# ─── File-level updaters ───────────────────────────────────────────────────────

def _read(path: str) -> object:
    with open(path, encoding='utf-8') as f:
        return json.load(f)


def _write(path: str, data: object) -> None:
    with open(path, 'w', encoding='utf-8') as f:
        json.dump(data, f, indent=2, ensure_ascii=False)
        f.write('\n')
    print(f'  ✓  {os.path.relpath(path, BASE)}')


def update_album_fixture(path: str) -> None:
    """Album format: { pokedex: { report, filtered_report, ... }, ... }"""
    data = _read(path)
    if isinstance(data, dict):
        if 'pokedex' in data and isinstance(data['pokedex'], dict):
            data['pokedex'] = transform_pokedex_section(data['pokedex'])
        elif 'report' in data:
            # Pokedex-only format (e.g. pokedex_lite.json)
            data = transform_pokedex_section(data)
    _write(path, data)


def update_election_index_fixture(path: str) -> None:
    """Election index: has metrics + optional pokedex section."""
    data = _read(path)
    if not isinstance(data, dict):
        return
    # Transform metrics if still in old nested format
    if 'metrics' in data and isinstance(data['metrics'], dict) and 'view_count' in data['metrics']:
        data['metrics'] = transform_metrics(data['metrics'])
    # Transform pokedex report sections
    if 'pokedex' in data and isinstance(data['pokedex'], dict):
        data['pokedex'] = transform_pokedex_section(data['pokedex'])
    _write(path, data)


def update_metrics_only_fixture(path: str) -> None:
    """Standalone metrics response: { view_count: {...}, win_count: {...}, ... }"""
    data = _read(path)
    if isinstance(data, dict) and 'view_count' in data:
        data = transform_metrics(data)
        _write(path, data)


def update_dex_list_fixture(path: str) -> None:
    """Array of dex-list items: [ { is_shiny, is_private, ..., slug, ... }, ... ]"""
    data = _read(path)
    if isinstance(data, list):
        # Only transform if first item has flat flags
        if data and any(k in data[0] for k in FLAG_KEYS):
            data = [nest_flags(item) for item in data]
            _write(path, data)
        else:
            print(f'  –  {os.path.relpath(path, BASE)} (already migrated, skipped)')
    else:
        print(f'  ?  {os.path.relpath(path, BASE)} (unexpected format, skipped)')


# ─── Main ──────────────────────────────────────────────────────────────────────

def main() -> None:
    print('=== A: report.detail → catch_state nesting ===')

    # Moco album fixtures (Album-format: { pokedex: { report, ... } })
    for path in sorted(glob.glob(
        BASE + '/tests/resources/moco/Back/responses/album/**/*.json',
        recursive=True,
    )):
        update_album_fixture(path)

    # Unit + integration fixtures with pokedex sections
    for path in [
        BASE + '/tests/resources/unit/service/back/album_lite.json',
        BASE + '/tests/resources/unit/service/back/pokedex_lite.json',
    ]:
        if os.path.exists(path):
            update_album_fixture(path)

    print('\n=== B: election index fixtures — metrics flat + pokedex.report ===')

    for path in sorted(glob.glob(
        BASE + '/tests/resources/moco/Back/responses/election/index_*.json',
    )):
        update_election_index_fixture(path)

    integration_election = BASE + '/tests/resources/integration/back/election_index.json'
    if os.path.exists(integration_election):
        update_election_index_fixture(integration_election)

    for path in sorted(glob.glob(
        BASE + '/tests/resources/unit/service/back/election_metrics_*.json',
    )):
        update_metrics_only_fixture(path)

    print('\n=== C: dex list flags → flags: {} nesting ===')

    for pattern in [
        BASE + '/tests/resources/moco/Back/responses/dex/*.json',
        BASE + '/tests/resources/moco/Back/responses/election/election_dex_list*.json',
        BASE + '/tests/resources/unit/service/back/dex*.json',
        BASE + '/tests/resources/unit/service/back/election_dex_list*.json',
    ]:
        for path in sorted(glob.glob(pattern)):
            update_dex_list_fixture(path)

    print('\nAll done.')


if __name__ == '__main__':
    main()
```

- [ ] **Step 2: Run the batch script from project root**

```bash
cd /home/renaud/projects/pokenini-web
python3 scripts/migrate_api_responses.py
```

Expected output: lines of `✓ tests/resources/...` for every file transformed. Any `?` (unexpected format) or `–` (already migrated) lines indicate files to inspect manually.

- [ ] **Step 3: Verify the JSON shape of one fixture from each group**

```bash
# A: album fixture — check report.detail
python3 -c "
import json
with open('tests/resources/moco/Back/responses/album/default/demo-lite.json') as f:
    d = json.load(f)
print(json.dumps(d['pokedex']['report']['detail'][0], indent=2))
"
# Expected: { "catch_state": { "name": "No", "french_name": "Non", "slug": "no", "color": "#e57373" }, "count": N }

# B: election fixture — check metrics
python3 -c "
import json
with open('tests/resources/moco/Back/responses/election/index_mega.json') as f:
    d = json.load(f)
print(json.dumps(d['metrics'], indent=2))
"
# Expected: { "view_count_sum": N, "view_count_max": N, ... } (no nested view_count/win_count/completion)

# C: dex list fixture — check flags
python3 -c "
import json
with open('tests/resources/moco/Back/responses/dex/trainer.json') as f:
    d = json.load(f)
print(json.dumps(d[0], indent=2))
"
# Expected: item has "flags": { "is_shiny": ..., ... } instead of top-level is_* keys
```

---

## Task 6: Verify complete integration test suite

After Tasks 1-5, the entire test suite (unit + integration) should be green. This is the validation step.

- [ ] **Step 1: Run unit tests**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Unit/ --testdox
```

Expected: all pass, including:
- `ElectionMetricsTest` with flat keys (Task 3)
- Any unit tests that use `ResponseObjectTrait` which constructs `ReportDetail` (Task 1)

- [ ] **Step 2: Run integration tests**

```bash
docker compose exec php php vendor/bin/phpunit --group api-mocked-testing --testdox
```

Expected: all pass. This exercises:
- `ReportDetailTest` and `ReportTest` (Task 2) — deserializes updated JSON
- Moco album + election + dex fixtures (Task 5) — new shapes pass through services + DTOs
- Twig templates (Task 4) — `dex.flags.is_*` paths hit the nested flags

- [ ] **Step 3: Run quality checks**

```bash
docker compose exec php php tools/phpstan/vendor/bin/phpstan analyse --memory-limit=-1
docker compose exec php php tools/psalm/vendor/bin/psalm --show-info=false --no-cache --taint-analysis
docker compose exec php php tools/phpmd/vendor/bin/phpmd src,tests text phpmd.ruleset.xml
```

PHPStan may flag the removed `int()` helper (no callers) — that's already deleted. If any stale `@psalm-suppress` needs removing, run with `--find-unused-psalm-suppress`.

- [ ] **Step 4: Run full measures**

```bash
make measures
```

Expected: 100% coverage, 100% MSI.

---

## Self-Review

### Spec coverage

| Spec requirement | Covered by |
|-----------------|------------|
| `report.detail[]`: `{ catch_state: { slug, name, french_name, color }, count }` | Task 1 (PHP), Task 2 (tests), Task 5A (fixtures) |
| `metrics`: flat `view_count_sum`, `win_count_sum`, etc. | Task 3 (PHP), Task 5B (fixtures) |
| `/album/dex` + `/trainer/dex`: `flags: {}` nested | Task 4 (Twig), Task 5C (fixtures) |
| `/election/dex`: `flags: {}` nested, no `display_template` | Task 4 (Twig), Task 5C (fixtures) |
| `filtered_report: []` when no active filter | Task 5 (script normalises `{}` → `[]`) |
| `election_top[].forms`: `null` or partial | `TopPokemonForms` already nullable-everything — no PHP change. Fixtures left as-is (behavior identical). |
| `ElectionIndex.php` docblock | Task 3 step 3 |

### Placeholder scan

None — all steps contain exact code or exact commands.

### Type consistency

- `CatchState(string $name, string $frenchName, string $slug, string $color)` — used correctly in Task 1 step 2 and Task 2 step 1.
- `ElectionMetricsCompletion($underMaxCount, $atMaxCount)` — maps `under_max_view_count` → `underMaxCount`, `max_view_count` → `atMaxCount` in Task 3 step 2. Consistent with the existing `ElectionMetricsCompletion` constructor.
- `getColor()` added to `ReportDetail` in Task 1 step 1 — tested in Task 2 step 1. No other callers; not required by templates.

---

**Plan saved.** Two execution options:

**1. Subagent-Driven (recommended)** — dispatch a fresh subagent per task, review between tasks, fast iteration.

**2. Inline Execution** — execute tasks in this session using executing-plans, batch execution with checkpoints.

**Which approach?**
