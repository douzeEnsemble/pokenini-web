# Credits page — display by Pokémon instead of by source — design

## Context

The Credits page (`/{_locale}/credits`) currently groups image credits **by source**: each list item is a source (e.g. "PokéSprite"), expandable to show which Pokémon/slots use it (see `docs/superpowers/specs/2026-07-26-credits-grouped-by-source-design.md`, implemented shortly before this one). The user wants the inverse view: each list item is a **Pokémon**, expandable to show its own up to 4 credit slots (small/big sprite × regular/shiny), including Pokémon that have **no credit at all** (currently excluded entirely).

`pokenini-api` already has all the raw data needed (`PokemonImageCredit`: `pokemon` FK, `size`, `isShiny`, `source`) and already has the exact 4-way join pattern needed (`PokedexRepository`, trainer/dex-scoped) — this is a query + DTO + display change across all three repos, not a schema change.

## Scope

- List **every** Pokémon species, ordered by national dex number ascending — not just those with a populated credit (reverses today's `WHERE source IS NOT NULL` filter).
- Per Pokémon: an expandable "(N images)" control (reusing the existing `credit.detail.count` wording/logic) listing its populated slots (small/big × regular/shiny), each slot showing the source name (linked if a URL is present) via the existing `creditBadge()` macro, with the existing sprite-thumbnail tooltip kept.
- A Pokémon with zero populated slots shows a plain "Aucun crédit" / "No credit" label instead of the expandable control (new translation key `credit.detail.none`).
- `GET /credits` response shape changes in place (single caller: the Credits page — same as the prior redesign, no other consumer in `pokenini-back`/`pokenini-web`).
- Per-image credit badges shown elsewhere in the app (Album grid, Election cards, modal) are **out of scope** and unchanged.

## 1. `pokenini-api`

### Repository

Replace `PokemonImageCreditRepository::findAllWithPokemon()` (rooted on `pokemon_image_credit`, `WHERE source IS NOT NULL`) with a query rooted on `pokemon`, using the same 4-way `LEFT JOIN` pattern `PokedexRepository` already uses for trainer/dex-scoped queries — but unscoped, directly against every species:

```php
/**
 * @return array<array{
 *   pokemon_slug: string,
 *   pokemon_name: string,
 *   pokemon_french_name: string,
 *   pokemon_icon: string,
 *   small_regular_credit: ?string,
 *   small_shiny_credit: ?string,
 *   big_regular_credit: ?string,
 *   big_shiny_credit: ?string,
 * }>
 */
public function findAllPokemonWithCredits(): array
{
    $sql = <<<'SQL'
        SELECT      p.slug AS pokemon_slug,
                    p.name AS pokemon_name,
                    p.french_name AS pokemon_french_name,
                    p.icon_name AS pokemon_icon,
                    pic_sr.source AS small_regular_credit,
                    pic_ss.source AS small_shiny_credit,
                    pic_br.source AS big_regular_credit,
                    pic_bs.source AS big_shiny_credit
        FROM        pokemon AS p
            LEFT JOIN pokemon_image_credit AS pic_sr ON p.id = pic_sr.pokemon_id AND pic_sr.size = 'small' AND pic_sr.is_shiny = false
            LEFT JOIN pokemon_image_credit AS pic_ss ON p.id = pic_ss.pokemon_id AND pic_ss.size = 'small' AND pic_ss.is_shiny = true
            LEFT JOIN pokemon_image_credit AS pic_br ON p.id = pic_br.pokemon_id AND pic_br.size = 'big'   AND pic_br.is_shiny = false
            LEFT JOIN pokemon_image_credit AS pic_bs ON p.id = pic_bs.pokemon_id AND pic_bs.size = 'big'   AND pic_bs.is_shiny = true
        ORDER BY    p.national_dex_number
        SQL;

    return $this->getEntityManager()->getConnection()->fetchAllAssociative($sql);
}
```

Column names (`name`, `french_name`, `icon_name`, `national_dex_number`) confirmed against `findAllWithPokemon()`'s existing query and `PokedexRepository`'s join.

### Service

`ImageCreditsService::getAllGroupedBySource()` → `getAllByPokemon()`. No grouping logic needed in PHP anymore (the SQL already produces one row per Pokémon):

```php
public function getAllByPokemon(): array
{
    return $this->repository->findAllPokemonWithCredits();
}
```

### DTOs / Factory

- New `PokemonCreditResponse` (final, readonly): `pokemonSlug`, `pokemonName`, `pokemonFrenchName`, `pokemonIcon`, `smallRegularCredit`, `smallShinyCredit`, `bigRegularCredit`, `bigShinyCredit` — the last 4 typed `?ImageCreditResponse`, reusing the existing `ImageCreditResponse{credit: string}` DTO already used by `PokemonDataResponse` for the same 4 slots.
- New `PokemonCreditResponseFactory`: maps each SQL row to a `PokemonCreditResponse`, wrapping each non-null `*_credit` column in `new ImageCreditResponse($value)`.
- Remove (superseded, not kept for compatibility): `ImageCreditGroupResponse`, `ImageCreditImageResponse`, `ImageCreditGroupResponseFactory`, and their dedicated unit tests. `ImageCreditResponse` itself stays — it's shared with `PokemonDataResponse`.

### Controller

```php
#[Route(path: '', methods: ['GET'])]
#[Serialize]
public function get(ImageCreditsService $service): array
{
    return PokemonCreditResponseFactory::fromRows($service->getAllByPokemon());
}
```

`GET /credits` response shape becomes:

```json
[
  {
    "pokemon_slug": "bulbasaur",
    "pokemon_name": "Bulbasaur",
    "pokemon_french_name": "Bulbizarre",
    "pokemon_icon": "bulbasaur",
    "small_regular_credit": { "credit": "PokéSprite - https://github.com/msikma/pokesprite" },
    "small_shiny_credit": null,
    "big_regular_credit": { "credit": "Bulbapedia - https://bulbapedia.bulbagarden.net" },
    "big_shiny_credit": null
  }
]
```

### Testing

- Repository integration test: replace `PokemonImageCreditRepositoryTest`'s assertions to cover the unscoped, all-species join — including a species with zero credit rows (all 4 columns null) and one with a partial set (e.g. only `small_regular_credit`).
- Service unit test: trivial delegation to the repository.
- Factory unit test: mapping from raw rows (including all-null and partial rows) to `PokemonCreditResponse`.
- Controller integration test: full `GET /credits` shape against fixtures.
- Remove the now-superseded `ImageCreditGroupResponseFactoryTest`, `ImageCreditGroupResponseTest`, `ImageCreditImageResponseTest`.
- Standard bar applies: 100% coverage, 100% MSI, PHPStan level 9, Psalm strict, Deptrac clean.

## 2. `pokenini-back`

No structural code change — `CreditsController`/`GetCreditsApiService` are a raw pass-through, so the new shape flows through unchanged. Only:

- Update the PHPDoc array-shape annotation on `GetCreditsApiService::get()` to reflect the new per-Pokémon structure.
- Update Moco fixtures: `tests/resources/moco/Api/responses/credits.json`, functional expected fixture `tests/resources/functional/controller/Credits/all.json`, unit-test fixture `tests/resources/unit/service/api/credits.json`.
- Update assertions in `CreditsTest` (integration) — fixtures only, no logic changes.
- Cache key/pool/invalidation (`KeyMaker::getCreditsKey()`, `labels`-bucket invalidator) unaffected.

## 3. `pokenini-web`

### ResponseObject

- New `ResponseObject/Common/PokemonCreditRow.php` (replaces `CreditGroup`/`CreditImage`, both removed): `pokemonSlug`, `pokemonName`, `pokemonFrenchName`, `pokemonIcon`, and 4 `?PokemonCredit` fields (`smallRegularCredit`, `smallShinyCredit`, `bigRegularCredit`, `bigShinyCredit`). `PokemonCredit` itself (name/URL extraction from the free-text `credit` string) is unchanged and reused as-is.

### Service

- `Service/Back/GetCreditsService`: deserialization target changes from `CreditGroup[]` to `PokemonCreditRow[]`.
- `Service/GetCreditsService`: same return type change. Cache key bumped `credits_v2` → `credits_v3` (the cached payload's PHP class is changing; bumping avoids a Redis-cached `CreditGroup` instance failing to deserialize after deploy once that class is deleted).

### Template — `templates/Credits/index.html.twig`

For each `PokemonCreditRow`, in national-dex-number order (as returned by the API):

- Pokémon name (localized: French/English per `app.request.locale`, same pattern as today).
- If at least one of the 4 credit fields is non-null: an "(N images)" toggle (`N` = count of non-null slots, reusing `credit.detail.count`) expanding a Bootstrap collapse listing each populated slot as `{{ size label }}, {{ shiny/regular label }}` next to a `creditBadge()` (existing macro from `common/Pokemon/_image_macros.html.twig`, already used on Album/Election) showing the source name/link, plus the existing sprite-thumbnail tooltip.
- If all 4 are null: plain text, new translation key `credit.detail.none` ("Aucun crédit" / "No credit").

New translation keys in both `messages+intl-icu.fr.yaml` and `.en.yaml`:
```yaml
credit:
  detail:
    none: "Aucun crédit"   # en: "No credit"
```

### Testing

- Unit tests for `PokemonCreditRow` deserialization (mirroring the removed `CreditGroup`/`CreditImage` tests).
- Integration test for the Credits controller/page with an updated Moco fixture, covering: a Pokémon with all 4 slots populated, one with a partial set, and one with none (asserting the "Aucun crédit" label renders).
- Remove `CreditGroupTest`, `CreditImageTest`.
- Existing per-image badge tests (Album/Election macros, `CreditBadgeTooltipTest`) are unaffected — `creditBadge()` itself doesn't change.

## Out of scope

- Per-image credit badges on Album/Election pages (unchanged).
- License/rights metadata (still name + URL only, unchanged).
- Editing credits via an admin UI (Google Sheet remains the source of truth).
- Any change to how `PokemonImageCredit` is populated (Sheet sync logic untouched).
- Search/filter box on the Credits page (explicitly declined).
