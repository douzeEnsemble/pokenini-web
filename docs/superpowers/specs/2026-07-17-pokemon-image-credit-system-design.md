# Pokémon image credit/attribution system — design

## Context

Pokémon sprite images (small/big × regular/shiny) are produced by sibling repos (`pokenini-icon`, `pokenini-resources`, outside this workspace) from external sources — big sprites from pokemondb.net, small sprites from a hand-sourced spritesheet. There is currently no mechanism to record or display where a given image came from. This feature adds a per-Pokémon, per-image-slot credit (source name + URL) so the site can properly attribute image sources, both next to each image and on a dedicated credits page.

Today `pokenini-api` stores only `Pokemon.iconName` (the filename fragment used to build image URLs); it does not model images as first-class data. The Google Sheet that feeds `PokemonsUpdater` already has `Sprites url` / `Shiny Sprites url` columns that are read but silently discarded.

## Scope

Source can differ **per Pokémon and per image slot** — up to 4 independent credits per Pokémon: small×regular, small×shiny, big×regular, big×shiny. Each credit is a `{name, url}` pair (no license field). Sources are populated manually in the Google Sheet over time; the system must degrade gracefully when a credit is missing (no badge shown, entry simply absent from the dedup list).

## 1. Data model — `pokenini-api`

New dedicated entity `PokemonImageCredit` (final, `BaseEntityTrait`), one row per `(pokemon, size, isShiny)`:

- `pokemon` (FK → `Pokemon`)
- `size` (`small` | `big`)
- `isShiny` (bool)
- `sourceName` (string, nullable)
- `sourceUrl` (string, nullable)
- Unique constraint on `(pokemon_id, size, is_shiny)`

Rationale: mirrors the existing convention of adding a small, focused entity for a new image-related concern (as was done recently for `ImagePipelineRun`) rather than widening the `Pokemon` entity with 8 flat columns. It also makes the deduplicated credits list a one-line repository query (`SELECT DISTINCT sourceName, sourceUrl`) instead of application-level dedup across 8 column-pairs.

Migration lands under `migrations/2026/07/`.

## 2. Google Sheets sync extension — `pokenini-api`

`PokemonsUpdater`'s expected header gains new columns, reusing the two that already exist:

| Sheet column | Maps to |
|---|---|
| `Icon Source` / `Icon Source Url` | small × regular credit |
| `Shiny Icon Source` / `Shiny Icon Source Url` | small × shiny credit |
| `Sprites Source` / `Sprites url` *(existing column, now used)* | big × regular credit |
| `Shiny Sprites Source` / `Shiny Sprites url` *(existing column, now used)* | big × shiny credit |

`transformRecord()` parses up to 4 `(size, isShiny, name, url)` tuples per row. `upsertRecord()` upserts matching `PokemonImageCredit` rows. Empty cells are tolerated: a missing name/url for a slot leaves that credit absent (row not created, or existing row's fields left null) rather than erroring — the sheet will be backfilled manually over time, so partial data is the expected steady state, not an edge case.

## 3. API exposure — `pokenini-api` → `pokenini-back`

Two surfaces:

1. **Embedded per-image credit.** `PokemonDataResponse` gains 4 optional nested objects: `smallRegularCredit`, `smallShinyCredit`, `bigRegularCredit`, `bigShinyCredit`, each `{name, url}` or `null`. This rides on the existing Pokémon list/detail endpoints already used everywhere in `pokenini-web`, so no extra round-trip is needed to show a tooltip next to an image already on screen.

  `PokemonDataResponse` is not built from one shared query — it's assembled inline, from a raw SQL row, in **four separate places**: `AlbumPokemonResponseFactory::buildPokemon()` (fed by the Album SQL query behind `AlbumPokemonService`), `ElectionPokemonResponseFactory::buildPokemon()` (fed by `resources/sql/pokemons-get_n_to_pick.sql` and `pokemons-get_n_to_vote.sql`, both routed through the same factory method), and `ElectionEloResponseFactory` (fed by `resources/sql/trainer_pokemon_elo-get_top_n.sql`). All three factories' `buildPokemon()`-equivalent methods, and both/all backing SQL queries, need a `LEFT JOIN pokemon_image_credit` (×4, one per size/shiny combination, aliased) and the 8 extra selected columns (`name`+`url` × 4 slots). This was confirmed against the actual codebase during planning and accepted as the chosen approach despite the wider surface, in preference to a separate batch endpoint.
2. **New endpoint** `GET /credits` — returns the deduplicated list `[{name, url}]` via the repository's `DISTINCT` query, for the global credits page.

`pokenini-back` passes both through: its own Pokémon DTO gains the same 4 nested credit fields, and a thin proxy/cache is added for `GET /credits`, following the existing caching pattern used for other API passthroughs. Moco fixtures in both `pokenini-back` and `pokenini-web` are updated accordingly.

## 4. Display — `pokenini-web`

**Per-image badge.** `_image_macros.html.twig` (`pokemonIcon()`/`pokemonImage()` and their `regular`/`shiny` wrappers) accept the relevant credit and, when non-null, overlay a small "ⓘ" badge on the image; hover/click reveals the source name linking to the URL. When credit is `null`, no badge renders — no layout shift, no placeholder. All macro call sites are updated: `Album/_album_macros.html.twig`, `Election/_candidates.html.twig`, and `Election/_top.html.twig` (which currently builds its image URL manually, bypassing the shared macro — this is fixed as part of this change so it also gets the badge for free, removing a pre-existing duplication).

**Global credits page.** New route (e.g. `/{_locale}/credits`) → `Controller` → `Service` → `Service/Api` calling `pokenini-back`'s `/credits` passthrough, rendered as a simple deduplicated list (`name` linking to `url`), linked from the site footer.

## 5. Testing

Standard project quality bar applies, no new tooling:

- `pokenini-api`: unit tests for the extended `PokemonsUpdater` parsing/upsert logic, the `PokemonImageCredit` entity/repository dedup query, the extended `ResponseFactory`; integration tests for the new `/credits` endpoint and the extended Pokémon response. Full coverage + 100% MSI.
- `pokenini-back`: integration tests (Moco fixtures) for the extended pass-through fields and the new `/credits` proxy/cache.
- `pokenini-web`: unit/integration tests for the macro's conditional badge rendering (credit present vs. `null`) across all call sites, and a controller test for the new `/credits` page.

## Out of scope

- Backfilling real credit data into the Google Sheet for existing Pokémon (content task, not a code change).
- License/rights metadata (deliberately excluded — name + URL only).
- Editing credits via an admin UI (Sheet remains the single source of truth, consistent with the rest of Pokémon data).
