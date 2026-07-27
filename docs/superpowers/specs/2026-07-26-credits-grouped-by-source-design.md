# Credits page — group by source with concerned images — design

## Context

The Credits page (`/{_locale}/credits`) currently shows a flat, deduplicated list of image-source strings (`credit.name` linking to `credit.url`), fed by `GET /credits` which is a plain `SELECT DISTINCT source FROM pokemon_image_credit`. This tells the visitor *which sources exist* but not *what each source is used for* — a source like "PokéSprite" covers dozens of small-sprite slots across many Pokémon, and there is no way to see that from the page.

This feature groups credits by source and, for each source, shows how many images use it plus an expandable detail listing the concerned Pokémon + image slot (size × shiny).

`pokenini-api` already models the pokemon ↔ credit association (`PokemonImageCredit`: `pokemon` FK, `size`, `isShiny`, `source`) — the current `/credits` endpoint just discards that association before responding. No schema/migration change is needed; this is a query + DTO + display change across all three repos.

## Scope

- Group image credits by `source`, each group carrying the list of `(pokemon, size, isShiny)` slots that use it.
- Sort groups by image count descending (ties broken alphabetically by source).
- Per-image detail: compact text list (`Pokémon name — small sprite, normal`, etc.), collapsed behind a click-to-expand "(N images)" control, with a hover tooltip showing the actual sprite thumbnail per line.
- `GET /credits` is replaced in place (single caller: the Credits page — no other consumer exists in `pokenini-back` or `pokenini-web`).
- The per-image credit badge shown next to individual sprites elsewhere in the app (Album grid, Election cards, modal) is **out of scope** and unchanged — it already has full context (which Pokémon/slot is on screen) and stays a simple `{credit}` string.

## 1. `pokenini-api`

### Repository

New method on `PokemonImageCreditRepository` (alongside the existing `findAllDistinctSources()`, which can be removed once the new endpoint replaces its only caller):

```php
/**
 * @return array<array{
 *   source: string,
 *   pokemon_slug: string,
 *   pokemon_name: string,
 *   pokemon_french_name: string,
 *   pokemon_icon: ?string,
 *   size: string,
 *   is_shiny: bool,
 * }>
 */
public function findAllWithPokemon(): array
{
    $sql = <<<'SQL'
        SELECT      pic.source AS source,
                    p.slug AS pokemon_slug,
                    p.name AS pokemon_name,
                    p.french_name AS pokemon_french_name,
                    p.icon_name AS pokemon_icon,
                    pic.size AS size,
                    pic.is_shiny AS is_shiny
        FROM        pokemon_image_credit AS pic
                JOIN pokemon AS p ON p.id = pic.pokemon_id
        WHERE       pic.source IS NOT NULL
        ORDER BY    p.national_dex_number, pic.size, pic.is_shiny
        SQL;

    return $this->getEntityManager()->getConnection()->fetchAllAssociative($sql);
}
```

Exact Pokémon column/property names (`name`, `french_name`, `icon_name`) to be confirmed against the entity during implementation — traits `NamedTrait`/`FrenchNamedTrait` and the `iconName` property observed during exploration are the source of truth.

### Service

`ImageCreditsService` gains the grouping + sorting step (kept in PHP, not SQL — consistent with the project's existing convention of doing any one-to-many aggregation in the application layer, e.g. `PokemonAvailabilitiesRepository`'s `string_agg` + `explode` pattern, rather than building nested arrays in SQL):

```php
/**
 * @return array<array{source: string, images: array<array{...}>}>
 */
public function getAllGroupedBySource(): array
{
    $rows = $this->repository->findAllWithPokemon();

    $grouped = [];
    foreach ($rows as $row) {
        $grouped[$row['source']]['source'] ??= $row['source'];
        $grouped[$row['source']]['images'][] = $row;
    }

    $groups = array_values($grouped);
    usort(
        $groups,
        static fn (array $a, array $b): int => (count($b['images']) <=> count($a['images']))
            ?: ($a['source'] <=> $b['source']),
    );

    return $groups;
}
```

### DTOs / Factory

- `ImageCreditImageResponse` (final): identity of one concerned image slot — Pokémon identity (reusing the existing `PokemonSlugResponse` shape/factory used elsewhere for lightweight Pokémon references) + `icon` (nullable string, for sprite URL construction) + `size` (`small`|`big`) + `isShiny` (bool).
- `ImageCreditGroupResponse` (final): `credit` (string, same free-text shape as today) + `images` (`ImageCreditImageResponse[]`).
- `ImageCreditGroupResponseFactory` builds these from the grouped array structure, reusing whatever existing factory builds `PokemonSlugResponse` from a raw SQL row (observed as a pattern used for `familyLead` in `AlbumPokemonResponseFactory`) for the Pokémon-identity sub-object.

### Controller

`ImageCreditsController::get()` now returns `ImageCreditGroupResponse[]` instead of `ImageCreditResponse[]`:

```php
#[Route(path: '', methods: ['GET'])]
#[Serialize]
public function get(ImageCreditsService $service): array
{
    return ImageCreditGroupResponseFactory::fromGroupedRows($service->getAllGroupedBySource());
}
```

`GET /credits` response shape becomes:

```json
[
  {
    "credit": "PokéSprite - https://github.com/msikma/pokesprite",
    "images": [
      { "pokemon": { "slug": "bulbasaur", "name": "Bulbasaur", "french_name": "Bulbizarre" }, "icon": "bulbasaur", "size": "small", "is_shiny": false },
      { "pokemon": { "slug": "bulbasaur", "name": "Bulbasaur", "french_name": "Bulbizarre" }, "icon": "bulbasaur", "size": "small", "is_shiny": true }
    ]
  }
]
```

### Testing

- Repository integration test: extend/replace the existing `PokemonImageCreditRepositoryTest`, reusing `fixtures/pokemon_image_credits.yaml` (already has 4 distinct sources across 3 Pokémon + one null-source row to confirm exclusion).
- Service unit test: grouping + sort-by-count-desc-then-alphabetical logic, including a tie-count case.
- Factory unit test: mapping from grouped rows to nested DTOs.
- Controller integration test: full `GET /credits` shape against fixtures, replacing the existing dedup-list assertions.
- Standard bar applies: 100% coverage, 100% MSI, PHPStan level 9, Psalm strict, Deptrac clean.

## 2. `pokenini-back`

No structural code change — `CreditsController`/`GetCreditsApiService` are a raw pass-through (`JsonResponse($service->get())` / `JsonDecoder::decode($json)`), so the richer JSON shape flows through unchanged. Only:

- Update the PHPDoc array-shape annotation on `GetCreditsApiService::get()` to reflect the new nested structure.
- Update Moco fixtures: `tests/resources/moco/Api/responses/credits.json`, the functional expected fixture `tests/resources/functional/controller/Credits/all.json`, and the unit-test fixture `tests/resources/unit/service/api/credits.json`.
- Update assertions in `CreditsTest` (integration) — no logic changes needed, just expected-content fixtures.
- Cache key/pool/invalidation (`KeyMaker::getCreditsKey()`, the `labels`-bucket invalidator) are unaffected — same key, same trigger.

## 3. `pokenini-web`

### ResponseObject

- New `ResponseObject/Common/CreditImage.php`: Pokémon identity (slug + localized name, following the existing locale-name-picking convention already used by the sprite macros' `locale` parameter) + `icon` (nullable string) + `size` + `isShiny`.
- New `ResponseObject/Common/CreditGroup.php`: wraps the existing `PokemonCredit` value object (unchanged — still does name/URL extraction from the free-text `credit` string) + `images: CreditImage[]`.
- The existing `PokemonCredit` class and its 4 embedded usages on `PokemonData`/`TopPokemonInfo` (`smallRegularCredit` etc.) are **untouched** — those endpoints keep sending a bare `{credit}` string per slot, unrelated to this change.

### Service

`GetCreditsService` (cache wrapper) and `Service/Back/GetCreditsService` (HTTP call) keep their existing structure — same cache pool/tag/key (`'credits'`) — only the deserialization target type changes from `PokemonCredit[]` to `CreditGroup[]`.

### Template — `templates/Credits/index.html.twig`

For each `CreditGroup`, in the order returned by the API (already sorted by image count descending):

- Source name (linked if URL present, plain text otherwise) — same rendering as today, via the embedded `PokemonCredit`.
- A "(N images)" control (`N = credit.images|length`) that toggles a Bootstrap collapse.
- Inside the collapse: one line per `CreditImage`, text `{{ pokemon name }} — {{ size label }} sprite, {{ shiny/regular label }}`, each line carrying a Bootstrap tooltip (`data-bs-toggle="tooltip"`, `data-bs-html="true"`) whose content is an `<img>` built from the existing `pokemonIconUrl`/`pokemonImageUrl` Twig globals (`{{ pokemonIconUrl|format(dir, image.icon) }}` for size=small, `pokemonImageUrl` for size=big, `dir` = `'shiny'`/`'regular'` from `isShiny`) — same URL-construction mechanism already used by `_image_macros.html.twig`, just invoked directly in this new template since there's no existing standalone PHP service for it.
- New translation keys for the size/shiny labels (e.g. `credit.detail.small`/`credit.detail.big`, `credit.detail.regular`/`credit.detail.shiny`) in both `messages+intl-icu.en.yaml` and `.fr.yaml`.

### Testing

- Unit tests for `CreditGroup`/`CreditImage` deserialization (Symfony Serializer, mirroring existing `PokemonCredit` unit tests).
- Integration test for the Credits controller/page with an updated Moco fixture (`tests/resources/moco/Back/responses/credits.json`) reflecting the grouped shape, asserting the rendered page contains the expected source names, counts, and collapsed detail content.
- Existing per-image badge tests (Album/Election macros) are unaffected.

## Out of scope

- Per-image credit badges on Album/Election pages (unchanged).
- License/rights metadata (still name + URL only, unchanged).
- Editing credits via an admin UI (Google Sheet remains the source of truth).
- Any change to how `PokemonImageCredit` is populated (Sheet sync logic untouched).
