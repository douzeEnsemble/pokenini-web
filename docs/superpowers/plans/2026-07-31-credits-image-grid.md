# Credits Image Grid Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the Credits page's collapsible per-Pokémon list with a grid of visible tiles — one tile per credited image (small/big × regular/shiny), each showing the real sprite/image, the Pokémon name, the image type, and the credit as visible linked text.

**Architecture:** Pure `pokenini-web` template change. `PokemonCreditRow` (already shipped on this same branch) already carries everything needed — no `pokenini-api`/`pokenini-back` changes. The template gains a local `tile()` macro rendering one image + name + type + credit block; the main loop emits up to 4 tiles per Pokémon (one per non-null credit slot) or exactly 1 "no credit" tile, in the same national-dex order as today. The now-unused collapse markup/CSS and the `credit.detail.count` translation key are removed.

**Tech Stack:** Symfony 8 / PHP 8.4, Twig, Bootstrap 5 (grid via plain CSS, no JS component needed — no more collapse).

## Global Constraints

- `declare(strict_types=1)` in every PHP file.
- Test classes: `/** @internal */` + `#[CoversClass(...)]`, `test`-prefixed method names.
- This work continues on the existing `feature/credits-by-pokemon` branch (already forked from `main`, already has 3 approved PRs' worth of history on it) — do not create a new branch.
- Commit at the end of each task, one commit per task. Do not push — that remains the user's explicit decision at the end.
- No `pokenini-api`/`pokenini-back` changes — `PokemonCreditRow` (`src/ResponseObject/Common/PokemonCreditRow.php`) already exposes `getPokemonSlug()`, `getPokemonName()`, `getPokemonFrenchName()`, `getPokemonIcon()`, `getSmallRegularCredit()`/`getSmallShinyCredit()`/`getBigRegularCredit()`/`getBigShinyCredit()` (each `?PokemonCredit`, with `getName()`/`getUrl()`), `hasAnyCredit()`, `getCreditCount()` — all reused as-is, untouched by this plan.
- `templates/common/Pokemon/_image_macros.html.twig` (`creditBadge()` and friends) stays untouched — this page stops using it, but Album/Election keep using it unmodified.
- The Moco fixture `tests/resources/moco/Back/responses/credits.json` (3 Pokémon: bulbasaur 4/4 credited, ivysaur 1/4, venusaur 0/4) is unchanged — sufficient for the new grid's test coverage without edits.
- 100% coverage + 100% Mutation Score Index (Infection) required; PHPStan level 9 / Psalm strict via `make code-quality`; `make measures` for coverage+MSI. (This repo's actual Makefile targets are `code-quality` and `infra-quality`, not a single `quality` target — confirmed in the prior plan's execution.)

---

## Task 1: Rewrite the Credits template as an image grid

**Files:**
- Modify: `templates/Credits/index.html.twig`
- Modify: `public/css/base.css`
- Modify: `translations/messages+intl-icu.fr.yaml`
- Modify: `translations/messages+intl-icu.en.yaml`

**Interfaces:**
- Consumes: `credits` (`PokemonCreditRow[]`, unchanged — passed by `CreditsController`, national-dex order), translation keys `credit.detail.size.small`/`.big`, `album.icon.title.regular`/`.shiny`, `credit.detail.none` (all pre-existing, reused as-is), Twig globals `pokemonIconUrl`/`pokemonImageUrl` (format strings, invoked as `pokemonIconUrl|format(dir, icon)`).
- Produces: the rendered `/{_locale}/credits` page as a CSS grid of `.credit-tile` elements — verified by Task 2's integration test.
- Removes: the `credit.detail.count` translation key (confirmed via repo-wide grep to be used only in this template, nowhere else) and the `.credit-detail-toggle`/`.credit-detail-list` CSS rules (both dead once the collapse markup is gone).

- [ ] **Step 1: Replace the template**

Replace `templates/Credits/index.html.twig`:

```twig
{% set locale = app.request.locale %}

{% extends 'base.html.twig' %}
{% use '_nav.html.twig' %}

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

{% block title %}Pokénini {{ 'title.credits'|trans }}{% endblock title %}

{% block container %}
  <div class="row justify-content-center">
    <div class="col-12">
      <h1>{{ 'title.credits'|trans }}</h1>

      <div class="credit-grid">
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
      </div>
    </div>
  </div>
{% endblock container %}
```

- [ ] **Step 2: Replace the dead collapse CSS with grid/tile CSS**

In `public/css/base.css`, the current block (lines 86-92) is:

```css
.credit-detail-toggle {
    font-size: .875rem;
}
.credit-detail-list {
    margin-top: .5rem;
    margin-bottom: 0;
}
```

Replace it with:

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

- [ ] **Step 3: Remove the now-unused `credit.detail.count` translation key**

In `translations/messages+intl-icu.fr.yaml`, the current `credit:` block (around line 661) is:

```yaml
credit:
  tooltip: "Crédit image"
  detail:
    count: "{count, plural, one {# image} other {# images}}"
    none: "Aucun crédit"
    size:
      small: "petit sprite"
      big: "grand sprite"
```

Remove the `count:` line:

```yaml
credit:
  tooltip: "Crédit image"
  detail:
    none: "Aucun crédit"
    size:
      small: "petit sprite"
      big: "grand sprite"
```

In `translations/messages+intl-icu.en.yaml`, the current block (around line 652) is:

```yaml
credit:
  tooltip: "Image credit"
  detail:
    count: "{count, plural, one {# image} other {# images}}"
    none: "No credit"
    size:
      small: "small sprite"
      big: "big sprite"
```

Remove the `count:` line the same way:

```yaml
credit:
  tooltip: "Image credit"
  detail:
    none: "No credit"
    size:
      small: "small sprite"
      big: "big sprite"
```

- [ ] **Step 4: Verify the template compiles and translations stay valid YAML**

Run: `docker compose exec php php bin/console lint:twig templates/Credits/index.html.twig`
Expected: `[OK] All 1 Twig files contain valid syntax.`

Run: `docker compose exec php php bin/console lint:yaml translations/`
Expected: no syntax errors.

- [ ] **Step 5: Commit**

```bash
git add templates/Credits/index.html.twig public/css/base.css translations/messages+intl-icu.fr.yaml translations/messages+intl-icu.en.yaml
git commit -m "Rewrite Credits page as an image grid instead of a collapsible per-Pokemon list"
```

---

## Task 2: Rewrite the Credits integration test for the grid

**Files:**
- Modify: `tests/src/Integration/Controller/Credits/CreditsTest.php`

**Interfaces:**
- Consumes: the existing Moco fixture `tests/resources/moco/Back/responses/credits.json` (bulbasaur 4/4, ivysaur 1/4, venusaur 0/4 — unchanged, no edits needed) and the test env's icon/image URL format (`.env.test`: `POKEMON_ICON_URL='https://icon.pokenini.fr/small/%1$s/%2$s.png'`, `POKEMON_IMAGE_URL='https://icon.pokenini.fr/big/%1$s/%2$s.png'`, so e.g. bulbasaur's big-shiny image is `https://icon.pokenini.fr/big/shiny/bulbasaur.png`).
- Produces: nothing new — this is the acceptance test proving Task 1's template renders correctly end-to-end. 6 total tiles expected (4 for bulbasaur + 1 for ivysaur + 1 "no credit" for venusaur).

- [ ] **Step 1: Replace the test**

Replace `tests/src/Integration/Controller/Credits/CreditsTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Credits;

use App\Controller\CreditsController;
use App\Tests\Common\Traits\TestNavTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[Group('api-mocked-testing')]
#[CoversClass(CreditsController::class)]
final class CreditsTest extends WebTestCase
{
    use TestNavTrait;

    public function testIndex(): void
    {
        $client = self::createClient();

        $crawler = $client->request('GET', '/fr/credits');

        $this->assertResponseIsSuccessful();

        $this->assertSame('Pokénini Crédits', $crawler->filter('title')->text());
        $this->assertSame('Crédits', $crawler->filter('h1')->text());

        // Order and content come from tests/resources/moco/Back/responses/credits.json:
        // bulbasaur (4/4 slots credited), ivysaur (1/4), venusaur (0/4).
        // One tile per credited image, plus one "no credit" tile for venusaur: 4 + 1 + 1 = 6.
        $tiles = $crawler->filter('.credit-tile');
        $this->assertCount(6, $tiles);

        $bulbaSmallRegular = $tiles->eq(0);
        $this->assertStringContainsString('Bulbizarre', $bulbaSmallRegular->text());
        $this->assertStringContainsString(
            'petit sprite, Normal',
            $bulbaSmallRegular->filter('.credit-tile-type')->text(),
        );
        $this->assertStringContainsString(
            'PokéSprite',
            $bulbaSmallRegular->filter('.credit-tile-credit')->text(),
        );
        $this->assertStringContainsString(
            '/small/regular/bulbasaur.png',
            (string) $bulbaSmallRegular->filter('img.credit-tile-image')->attr('src'),
        );

        $bulbaSmallShiny = $tiles->eq(1);
        $this->assertStringContainsString(
            'petit sprite, Chromatique',
            $bulbaSmallShiny->filter('.credit-tile-type')->text(),
        );
        $this->assertStringContainsString(
            'PokéSprite',
            $bulbaSmallShiny->filter('.credit-tile-credit')->text(),
        );
        $this->assertStringContainsString(
            '/small/shiny/bulbasaur.png',
            (string) $bulbaSmallShiny->filter('img.credit-tile-image')->attr('src'),
        );

        $bulbaBigRegular = $tiles->eq(2);
        $this->assertStringContainsString(
            'grand sprite, Normal',
            $bulbaBigRegular->filter('.credit-tile-type')->text(),
        );
        // Distinct source from the small-slot tiles above - guards against
        // a slot mix-up (e.g. the big-regular tile silently reusing the
        // small-regular credit).
        $this->assertStringContainsString(
            'PokemonDB',
            $bulbaBigRegular->filter('.credit-tile-credit')->text(),
        );
        $this->assertStringContainsString(
            '/big/regular/bulbasaur.png',
            (string) $bulbaBigRegular->filter('img.credit-tile-image')->attr('src'),
        );

        $bulbaBigShiny = $tiles->eq(3);
        $this->assertStringContainsString(
            'grand sprite, Chromatique',
            $bulbaBigShiny->filter('.credit-tile-type')->text(),
        );
        $this->assertStringContainsString(
            'Bulbapedia',
            $bulbaBigShiny->filter('.credit-tile-credit')->text(),
        );
        $this->assertStringContainsString(
            '/big/shiny/bulbasaur.png',
            (string) $bulbaBigShiny->filter('img.credit-tile-image')->attr('src'),
        );

        $ivysaurTile = $tiles->eq(4);
        $this->assertStringContainsString('Herbizarre', $ivysaurTile->text());
        $this->assertStringContainsString(
            'petit sprite, Normal',
            $ivysaurTile->filter('.credit-tile-type')->text(),
        );
        $this->assertStringContainsString(
            'Serebii',
            $ivysaurTile->filter('.credit-tile-credit')->text(),
        );

        $venusaurTile = $tiles->eq(5);
        $this->assertStringContainsString('Florizarre', $venusaurTile->text());
        $this->assertCount(0, $venusaurTile->filter('.credit-tile-type'));
        $this->assertStringContainsString(
            'Aucun crédit',
            $venusaurTile->filter('.credit-tile-credit')->text(),
        );
    }

    public function testCreditLinkPointsToTheSourceUrl(): void
    {
        $client = self::createClient();

        $crawler = $client->request('GET', '/fr/credits');

        $link = $crawler->filter('.credit-tile')->eq(0)->filter('.credit-tile-credit a');

        $this->assertSame('https://github.com/msikma/pokesprite', $link->attr('href'));
        $this->assertSame('_blank', $link->attr('target'));
    }
}
```

- [ ] **Step 2: Run the test to verify it fails against the pre-Task-1 template**

This step only applies if Task 1 has NOT been applied yet in your working copy. Since Task 1 is expected to already be committed before this task starts (sequential execution), run the test directly and expect it to pass (Step 3). If for some reason you're running this against the old collapsible-list template, expect FAIL — no `.credit-tile` elements exist yet.

- [ ] **Step 3: Run the test to verify it passes**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Credits/CreditsTest.php`
Expected: PASS (2 tests).

- [ ] **Step 4: Commit**

```bash
git add tests/src/Integration/Controller/Credits/CreditsTest.php
git commit -m "Rewrite Credits integration test for the image-grid layout"
```

---

## Task 3: Full suite and quality gate

**Files:** none (verification-only task).

- [ ] **Step 1: Run the full test suite**

Run: `make tests` (unit + integration + browser: Chrome + Firefox)
Expected: all green.

- [ ] **Step 2: Run quality and measures gates**

Run: `make infra-quality && make code-quality && make measures`
Expected: all green (PHPStan level 9, Psalm strict — taint + standard, Deptrac, PHP CS Fixer, PHPMD, jsonlint, editorconfig, w3c, 100% coverage, 100% Mutation Score Index).

If a gate failure is a direct, mechanical consequence of Task 1/2's changes (e.g. a PHP CS Fixer nit, a Psalm/PHPStan complaint, a surviving mutant in the new Twig-adjacent PHP code), fix it, re-verify, and commit the fix. If it's unrelated/pre-existing, report it rather than fixing it.
