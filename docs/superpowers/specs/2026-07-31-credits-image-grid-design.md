# Credits page — image grid instead of collapsible per-Pokémon list — design

## Context

The Credits page (`/{_locale}/credits`) was just redesigned (see `2026-07-31-credits-by-pokemon-design.md`, on the same in-flight branch `feature/credits-by-pokemon`, PR #389 not yet merged) to list one row per Pokémon species with a collapsible "(N images)" detail hidden behind a click. The user wants the credited images themselves to be immediately visible instead of hidden behind a toggle: a grid of tiles, one tile per credited image, each showing the actual sprite/image, the Pokémon name, the image type, and the credit — no click required to see any of it.

Because `pokenini-api` already returns every credit slot per species (`PokemonCreditResponse` → `PokemonCreditRow` on the web side, with `pokemonName`, `pokemonFrenchName`, `pokemonIcon`, and 4 `?PokemonCredit` slots), **this is a `pokenini-web`-only change** — no API or Back changes needed. It replaces the template introduced in the current in-flight PR before that PR merges.

## Scope

- Replace the collapsible `<ul class="list-group">` layout with a CSS grid of tiles.
- One tile per **credited image** (not per Pokémon): a species with 4 populated slots gets 4 tiles; a species with 0 credits gets exactly 1 "no credit" tile.
- Tiles for the same Pokémon stay adjacent in the grid, in fixed slot order (small regular → small shiny → big regular → big shiny); Pokémon order is national dex number, same as today.
- Each credited tile shows: the real image (small sprite for small slots, full image for big slots), the localized Pokémon name, the image type label ("petit sprite, Normal" / "grand sprite, Chromatique" — reusing existing `credit.detail.size.*` + `album.icon.title.*` translation keys), and the credit source name as visible text (linked if a URL is present) — no hover/tooltip needed.
- The "no credit" tile shows the Pokémon's regular-form icon, its name, and the `credit.detail.none` text in place of a type/credit line.
- Remove the now-dead collapse markup/CSS (`.credit-detail-toggle`, `.credit-detail-list`) and the `credit.detail.count` translation key if nothing else references it (grep both locale files' other pages/templates before removing — see Task 1 below).
- Credit rendering is plain visible text (name + optional link), **not** the `creditBadge()` macro from `common/Pokemon/_image_macros.html.twig` — that macro stays untouched and keeps being used elsewhere (Album/Election); this page stops importing/calling it.

## Implementation

### Template — `templates/Credits/index.html.twig`

Full rewrite. A local Twig macro (defined in the same file, since it's page-specific — not a candidate for the shared `_image_macros.html.twig`, which is about the compact hover-badge pattern this page is deliberately moving away from) renders one tile:

```twig
{% macro tile(imageUrl, pokemonName, typeLabel, credit) %}
  <div class="credit-tile">
    <img
      class="credit-tile-image img-fluid"
      src="{{ imageUrl }}"
      alt=""
      loading="lazy"
      onerror="this.onerror=null;this.src='/img/pokemon/default_icon.webp';"
    >
    <div class="credit-tile-name">{{ pokemonName }}</div>
    {% if typeLabel is not null %}
      <div class="credit-tile-type">{{ typeLabel }}</div>
    {% endif %}
    <div class="credit-tile-credit">
      {% if credit is null %}
        {{ 'credit.detail.none'|trans }}
      {% elseif credit.url is not null %}
        <a href="{{ credit.url }}" target="_blank" rel="noopener">{{ credit.name }}</a>
      {% else %}
        {{ credit.name }}
      {% endif %}
    </div>
  </div>
{% endmacro %}
```

Main loop, one `{% for row in credits %}` iterating `PokemonCreditRow[]` (dex order, unchanged from today), emitting up to 4 credited tiles or exactly 1 no-credit tile per row — tile adjacency-by-Pokémon falls out naturally from emitting them consecutively inside the same loop iteration, no extra grouping markup needed:

```twig
{% for row in credits %}
  {% set pokemonName = locale is same as('fr') ? row.pokemonFrenchName : row.pokemonName %}
  {% set regularIconUrl = pokemonIconUrl|format('regular', row.pokemonIcon) %}

  {% if row.hasAnyCredit %}
    {% if row.smallRegularCredit is not null %}
      {{ _self.tile(regularIconUrl, pokemonName, 'credit.detail.size.small'|trans ~ ', ' ~ 'album.icon.title.regular'|trans, row.smallRegularCredit) }}
    {% endif %}
    {% if row.smallShinyCredit is not null %}
      {{ _self.tile(pokemonIconUrl|format('shiny', row.pokemonIcon), pokemonName, 'credit.detail.size.small'|trans ~ ', ' ~ 'album.icon.title.shiny'|trans, row.smallShinyCredit) }}
    {% endif %}
    {% if row.bigRegularCredit is not null %}
      {{ _self.tile(pokemonImageUrl|format('regular', row.pokemonIcon), pokemonName, 'credit.detail.size.big'|trans ~ ', ' ~ 'album.icon.title.regular'|trans, row.bigRegularCredit) }}
    {% endif %}
    {% if row.bigShinyCredit is not null %}
      {{ _self.tile(pokemonImageUrl|format('shiny', row.pokemonIcon), pokemonName, 'credit.detail.size.big'|trans ~ ', ' ~ 'album.icon.title.shiny'|trans, row.bigShinyCredit) }}
    {% endif %}
  {% else %}
    {{ _self.tile(regularIconUrl, pokemonName, null, null) }}
  {% endif %}
{% endfor %}
```

Wrapped in a `<div class="credit-grid">` replacing today's `<ul class="list-group">`.

### CSS — `public/css/base.css`

Remove `.credit-detail-toggle` and `.credit-detail-list` (dead once the collapse markup is gone). Add:

```css
.credit-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 1rem;
}
.credit-tile {
    text-align: center;
}
.credit-tile-image {
    max-height: 96px;
    object-fit: contain;
}
.credit-tile-type {
    font-size: .8rem;
    color: var(--bs-secondary-color);
}
```

### Translations

Remove `credit.detail.count` from both `messages+intl-icu.fr.yaml` and `.en.yaml` — confirmed via repo-wide grep to be used only in `templates/Credits/index.html.twig:37` (the collapse-toggle "(N images)" label this redesign removes), nowhere else. `credit.detail.size.small/big`, `album.icon.title.regular/shiny`, `credit.detail.none` are all reused as-is, no changes needed.

### Testing

`tests/src/Integration/Controller/Credits/CreditsTest.php` rewritten against the same 3-Pokémon Moco fixture (bulbasaur 4/4, ivysaur 1/4, venusaur 0/4 — unchanged, no fixture edits needed): asserts 6 `.credit-tile` elements total (4 + 1 + 1), each tile's image `src`, name, type label, and credit text/link — replacing the old collapse-based assertions. The two assertion groups added in the just-completed final review (credit attribution content, sprite icon `src`) get folded into this rewrite rather than duplicated.

## Out of scope

- No change to `pokenini-api` or `pokenini-back` — `PokemonCreditRow`'s shape already has everything this template needs.
- `creditBadge()` macro and its use on Album/Election pages: unchanged.
- Search/filter, pagination: still not in scope (per the original design doc; not revisited here).
- Responsive breakpoint tuning beyond the `auto-fill`/`minmax` grid default: can be adjusted visually later, not blocking.
