# Mock data coherence (home ↔ pokedex)

## Problem

The Moco fixtures under `tests/resources/moco/Back/responses/` model the flow from
the home dex list (`GET /album/dex`, rendered as clickable tiles filtered on
`flags.is_on_home`) to the individual pokedex page (`GET /album/{dexSlug}`). Two
kinds of drift have accumulated between these fixtures, since they were authored
by hand and independently over time:

1. **Dead links**: a dex tile is flagged `is_on_home: true` in a `dex/*.json`
    fixture, but no moco route (and no `album/**/*.json` fixture) exists for the
    slug it links to. Clicking the tile 404s.
2. **Numeric drift**: where a route *does* exist, the home tile's `report`
    (caught/uncaught counts) is hand-typed independently from the album
    fixture's real `pokemons[]` list, so the two pages show different numbers
    for what's supposed to be the same trainer+dex progress.

## Principle

The album fixture's real `pokemons[]` list becomes the single source of truth.
Each home-tile row's `report` block is *derived* from it, not hand-authored
separately. This is enforced mechanically by a one-off script rather than by
hand, because two of the album fixtures (`swordshield.json`, `home.json`) have
935 and 1477 rows respectively — hand-computing sums at that scale is
error-prone and won't scale to future fixture changes.

### Report formula

Reverse-engineered from the one fixture that was already internally
consistent (`trainer.json`'s `swordshield` entry, whose `detail[]` buckets
summed exactly to `total`). Given a pokedex's `pokemons[]` list and the six
catch states from `labels.json` (`yes`, `totrade`, `totransfer`, `tobreed`,
`toevolve`, `no`):

- `total_caught` = count of entries where `catch_state.slug == "yes"`
- `total_uncaught` = count of entries where `catch_state` is `null` or
  `catch_state.slug == "no"`
- `detail[]` = one entry per catch state label, with the count of pokemons
  carrying that exact `catch_state.slug` (entries with `catch_state: null`
  are not counted in any specific `detail[]` bucket, only in
  `total_uncaught`)
- `total` = `total_caught` + `total_uncaught` + sum of the four intermediate
  bucket counts (`totrade` + `totransfer` + `tobreed` + `toevolve`)

Rows where `report` is currently `null` (e.g. `da4b9237…json`'s
`homepokemongo` row) stay `null` — the home template already treats a null
report as "no progress bar shown", which is a valid, self-consistent state,
not a bug.

## Fixes

### 1. Missing routes / fixtures (four broken links)

| Slug | Referenced by (dex/*.json) | Fix |
|---|---|---|
| `homeshiny` | `trainer.json`, `admin.json`, `159bb9b6…json` | New `album/default/homeshiny.json`, catch-all `Bearer .*` moco route (same pattern as `mega.json`). Flags: `is_shiny: true`. |
| `alpha` | `trainer.json`, `admin.json`, `159bb9b6…json` | New `album/default/alpha.json`, catch-all route. Flags: `is_premium: true`. |
| `homepogo` | `trainer.json` | New `album/default/homepogo.json`, catch-all route. Flags: `is_custom: true`. Kept distinct from the existing `homepokemongo` dex per explicit decision — this is a different, custom dex. |
| `home-shiny-custom` | `dex/77de68…json` (trainer `"3"`) | New trainer-scoped moco route (`Bearer 77de68daecd823babbb58edb1c8e14d7106e83bb`) + `album/77de68daecd823babbb58edb1c8e14d7106e83bb/home-shiny-custom.json`. This is the slug the existing `AlbumDexListTest::testAlbumDexListCustomDexLinksToUniqueSettingsSlug` already asserts is linked to, but the test never follows the link. |

Each new album fixture is cloned from the shape of an existing small fixture
(`album/default/mega.json`, 50 rows) as a starting template, then trimmed/
adjusted (dex slug, name, flags, a handful of representative Pokémon) rather
than authored from scratch. All Pokémon entries start with `catch_state:
null` (matches the current state of `swordshield.json`/`home.json` — no
fixture currently has catch-state variety; that's Phase 2 scope).

### 2. Report resync (numeric drift)

For every row with `is_on_home: true` and a non-null `report` across
`dex/trainer.json`, `dex/admin.json`, `dex/159bb9b6…json`, and
`dex/77de68…json`, recompute `report` from the real pokemon count of the
album fixture it links to, using the formula above. This replaces the
existing hand-typed numbers (e.g. `swordshield` moves from the fictional
`400/50/140` to the real `935/0/935`).

Newly added fixtures (`homeshiny`, `alpha`, `homepogo`, `home-shiny-custom`)
get their home-tile `report` computed the same way, from the moment they're
created.

### Out of scope for this pass

- **`flags` mismatches** between a dex-list row (`dex/*.json`) and the album
  fixture's own `pokedex.dex.flags` (e.g. `mega.json` has `is_on_home: false`
  internally, contradicting the dex-list row). These aren't rendered
  anywhere the user compares against the home tile, so fixing them is
  cosmetic, not a coherence bug the user experiences.
- **Phase 2 — diversity**: giving each trainer profile (admin, collector, the
  numbered `"0"`–`"3"` trainers, etc.) a distinct, meaningful catch-state
  distribution (early game / near-complete / shiny-focused / etc.) instead of
  the current "everything is `null`" state. This is explicitly deferred to a
  follow-up spec once coherence lands, per user instruction ("cohérence
  d'abord, puis diversité").

## Implementation approach

A one-off Python script, kept in the session scratchpad only (not committed
to the repo), reads each affected `dex/*.json` and its linked
`album/**/*.json` fixture, and rewrites the `report` block in place using the
formula above. New fixture files and new `moco.json` routes are added by
hand (structural JSON, not mechanically derivable). The script is then
re-run as a final consistency pass over the touched files.

## Verification

1. Run the integration tests that already cover these fixtures inside the
    container: `AlbumDexListTest`, and the `Album/Display` test suite
    (`docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Album/`).
2. A small consistency check (can reuse the same script in a "check" mode, or
    a short ad hoc script) confirming, for every `dex/*.json` file: every row
    with `is_on_home: true` resolves to an actual moco route, and every row
    with a non-null `report` has `report.total` equal to the pokemon count of
    the album fixture it links to.
