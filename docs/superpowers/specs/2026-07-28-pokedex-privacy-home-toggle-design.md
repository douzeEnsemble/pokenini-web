# Design — Toggle privacy & "add to home" from the Pokédex page

**Date:** 2026-07-28
**Branch:** dev-20260728

## Context

The trainer-owned flags `is_private` and `is_on_home` (see `DexFlags.php`) can today only be toggled from the "my albums" management page (`/trainer`, `templates/Trainer/Section/_dex.html.twig`), via a `PUT /trainer/dex/{dexSlug}` request (`TrainerUpsertController` → `ModifyTrainerDexService` → `Service/Back/ModifyDexService`). The Pokédex/album page itself (`/album/{dexSlug}`) only *displays* `is_private` read-only, as a lock badge in the offcanvas informations panel (`Album/_offcanvas.html.twig`); `is_on_home` isn't shown there at all.

The user wants to add an album to the home page, and toggle its public/private status, directly from the Pokédex page instead of having to go to `/trainer`.

Both flags are already exposed on the Album page's `Dex` response object (`isPrivate()`, `isOnHome()`), and the page already computes `allowedToEdit` (`AlbumIndexController::editDexIsGranted()`), which is the exact same ownership + premium/`ROLE_COLLECTOR` rule as the Trainer page's `canEdit`. No backend or BFF change is needed — this is a frontend-only addition that reuses an existing, already-tested endpoint.

## Chosen approach

Add a `form-switch` pair (`is_private`, `is_on_home`) to the offcanvas "informations" panel, shown only when `allowedToEdit` is true, reusing:
- the exact markup/icon pattern already used on the Trainer page (`bi-incognito`, `bi-house-check`),
- the exact same JS (`public/js/trainer_dex.js`, already generic — keys off `data-dex` on the closest `<form>`, listens on any checkbox, PUTs to `/trainer/dex/{slug}`, shows a toast by id) — no JS file changes needed, just include the script and call `watchAttributes()`,
- the exact same translation keys already defined under `trainer.dex.attributes.is_private.*` / `is_on_home.*` and `trainer.dex.update.success` / `error` (the latter already parameterized with `dexName`, not tied to the Trainer page semantically) — no new translation keys needed.

The existing read-only lock badge (`.album-private`, driven by `dex.isPrivate`) is left untouched and keeps rendering unconditionally (for every visitor, owner or not) exactly as today — it is already asserted on by five tests in `tests/src/Integration/Controller/Album/Display/OffcanvasTest.php`, all of which exercise the owner's own album (where `allowedToEdit` is true). Gating that badge on `not allowedToEdit` would hide it in exactly the cases those tests cover, forcing changes to well-established, unrelated assertions. Showing both a read-only status badge *and* an editable switch to the owner is not a new pattern either — the Trainer page already does exactly that (position-absolute badges for `is_shiny`/`is_premium`/`is_custom`/`not_released`, alongside editable switches for `is_private`/`is_on_home` in the same card).

Two alternatives considered and rejected:
- **Replace the lock badge with the switch when `allowedToEdit`** — more "correct" looking (no redundancy) but forces edits to 5 existing, unrelated integration test assertions for no functional gain, and diverges from the Trainer page's own established pattern of badge + switch coexisting.
- **Icon buttons in `_intro.html.twig`'s action bar instead of the offcanvas** — considered during brainstorming; rejected by the user in favor of the offcanvas, which is also where the read-only privacy info already lives.

## Behavior

In `Album/_offcanvas.html.twig`, inside the "informations" block, right after the `<h2>{{ 'album.offcanvas.informations.title'|trans }}</h2>` heading and before the existing icons list:

```twig
{% if allowedToEdit %}
  <form data-dex="{{ currentDexSlug }}">
    {% set attributes = ['is_private', 'is_on_home'] %}
    {% set attributesIcons = {'is_private': 'incognito', 'is_on_home': 'house-check'} %}
    {% set flagMap = {'is_private': dex.isPrivate, 'is_on_home': dex.isOnHome} %}
    {% for attribute in attributes %}
      <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" role="switch"
          name="{{ currentDexSlug }}-{{ attribute }}"
          id="offcanvas-{{ currentDexSlug }}-{{ attribute }}"
          {{ flagMap[attribute] ? 'checked' : '' }}>
        <label class="form-check-label" for="offcanvas-{{ currentDexSlug }}-{{ attribute }}">
          <i class="bi bi-{{ attributesIcons[attribute] }}"></i>
          {{ ('trainer.dex.attributes.'~attribute~'.label')|trans }}
        </label>
        <p class="form-text">
          {{ ('trainer.dex.attributes.'~attribute~'.help')|trans }}
        </p>
      </div>
    {% endfor %}
  </form>
{% endif %}
```

Note the input `id` is prefixed `offcanvas-` (unlike the Trainer page's bare `{{ dex.settings.slug }}-{{ attribute }}`) purely to avoid a DOM id clash if a user ever has both an offcanvas switch and — hypothetically — another element sharing the slug-based id; the `name` attribute (what `trainer_dex.js` actually reads via `FormData`) stays exactly `{{ currentDexSlug }}-{{ attribute }}` so the existing JS needs no changes.

On change of either checkbox: `trainer_dex.js`'s existing `onChangeAttributes` → `saveChange` fires, disables the checkbox, `PUT`s `{"is_private": true}` (or `is_on_home`) to `/trainer/dex/{dexSlug}`, re-enables it and shows `#successToast-{dexSlug}` on success, or reverts the checked state and shows `#errorToast-{dexSlug}` on failure (HTTP status ≠ 200, e.g. the controller's 404 when premium+non-collector, or a 500).

## Changes

### 1. `templates/Album/_offcanvas.html.twig`
Add the switches form shown above, gated on `allowedToEdit`. No change to the existing icons list, description, template text, or version block.

### 2. `templates/Album/_toasts.html.twig`
Currently: `{% if allowedToEdit %}` renders per-Pokémon catch-state toasts (`macro.toasts(item)`); `{% if not dex.isPrivate %}` renders the share toasts. Add, inside the existing `{% if allowedToEdit %}` branch, the two dex-flag toasts (success/error), reusing `trainer.dex.update.success` / `.error` (parameterized with `dexName`, taken from `locale is same as('fr') ? dex.frenchName : dex.name`):

```twig
{% if allowedToEdit %}
    {% for item in list %}
        {{ macro.toasts(item) }}
    {% endfor %}

    {% set dexName = locale is same as('fr') ? dex.frenchName : dex.name %}
    <div id="successToast-{{ currentDexSlug }}" class="toast text-bg-success" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body">
          {{ 'trainer.dex.update.success.prefix'|trans }}
          <strong>{{ 'trainer.dex.update.success.radical'|trans({'dexName': dexName}) }}</strong>
          {{ 'trainer.dex.update.success.suffix'|trans }}
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>

    <div id="errorToast-{{ currentDexSlug }}" class="toast text-bg-danger" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body">
          {{ 'trainer.dex.update.error.prefix'|trans }}
          <strong>{{ 'trainer.dex.update.error.radical'|trans({'dexName': dexName}) }}</strong>
          {{ 'trainer.dex.update.error.suffix'|trans }}
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>
{% endif %}
```

(`locale` is already set at the top of `Album/index.html.twig` as `{% set locale = app.request.locale %}` and passed down to includes.)

### 3. `templates/Album/index.html.twig`
Inside the existing `{% if allowedToEdit %}` block in `foot_javascript` (the one that already includes `album-edit.js` and defines `const locale`), add:

```twig
<script src="{{ asset('js/trainer_dex.js') }}"></script>
```

and add `watchAttributes();` to the existing IIFE, next to `watchToggleEditMode(); watchCatchStates(); ...`.

No changes to `public/js/trainer_dex.js`, `public/js/album.js`, or `public/js/album-edit.js` — `trainer_dex.js`'s `watchAttributes()` binds to every `input[type="checkbox"]` on the page, and the Album page has no other checkboxes today, so this is safe to reuse verbatim.

### 4. Translations
None needed — reusing `trainer.dex.attributes.is_private.{label,help}`, `trainer.dex.attributes.is_on_home.{label,help}`, `trainer.dex.update.success.{prefix,radical,suffix}`, `trainer.dex.update.error.{prefix,radical,suffix}` as-is in both `messages+intl-icu.en.yaml` and `messages+intl-icu.fr.yaml`.

## Testing

- **`tests/src/Integration/Controller/Album/Display/OffcanvasTest.php`**: existing 6 tests are unaffected (no assertions on the new switches; the lock badge markup is untouched). Add new test methods:
  - Owner viewing own private album (`/fr/album/goldsilvercrystal` as trainer `12`, `allowedToEdit` true): assert `#offcanvas form[data-dex="goldsilvercrystal"] input#offcanvas-goldsilvercrystal-is_private` exists and is `checked`; assert the `is_on_home` switch exists too.
  - Another trainer's album (`testIntroDemoAnotherTrainer`'s scenario, `allowedToEdit` false): assert the switches form is absent (`assertCountFilter($crawler, 0, '#offcanvas form[data-dex]')`).
- **`tests/src/Browser/Album/OffcanvasTest.php`** (or a new `tests/src/Browser/Album/PrivacyHomeToggleTest.php`, mirroring `tests/src/Browser/Trainer/CustomAlbumTrainerTest.php` exactly): reuse the same Moco-mocked slugs, no new fixtures needed —
  - `goldsilvercrystal` (`PUT /trainer/dex/goldsilvercrystal` already mocked to succeed for the roles used in `GetUserToken::getFakeUserToken()`): tick/untick `is_private` and `is_on_home` on `/fr/album/goldsilvercrystal`, assert `#successToast-goldsilvercrystal` becomes visible then hides, `#errorToast-goldsilvercrystal` never shows.
  - `redgreenblueyellow` (`PUT /trainer/dex/redgreenblueyellow` already mocked to return 500): same interaction, assert `#errorToast-redgreenblueyellow` shows and the checkbox reverts to its previous state.
- No changes needed to `TrainerUpsertController`, `ModifyTrainerDexService`, `Service/Back/ModifyDexService`, or their existing unit tests — untouched code path.

## Files to create/modify

| File | Action |
|---|---|
| `templates/Album/_offcanvas.html.twig` | Add `is_private`/`is_on_home` switches form, gated on `allowedToEdit` |
| `templates/Album/_toasts.html.twig` | Add success/error toasts for the dex-flag update, inside existing `allowedToEdit` branch |
| `templates/Album/index.html.twig` | Include `trainer_dex.js`, call `watchAttributes();` inside existing `allowedToEdit` script block |
| `tests/src/Integration/Controller/Album/Display/OffcanvasTest.php` | Add tests for switches presence/checked-state, gated on `allowedToEdit` |
| `tests/src/Browser/Album/OffcanvasTest.php` or new `PrivacyHomeToggleTest.php` | Add tick/untick success + error browser tests, reusing existing Moco fixtures |

## Out of scope

- No "create a brand new album/dex" flow — confirmed with the user this feature is only about toggling `is_on_home` (and `is_private`) for the album currently being viewed, not creating a new dex entity (which has no existing creation endpoint in this frontend or, apparently, a dedicated one in the backend either — album "creation" today is implicit, triggered by the first `GET`).
- No change to the Trainer page (`/trainer`), `TrainerUpsertController`, or any backend/BFF code — the existing `PUT /trainer/dex/{dexSlug}` contract is reused unchanged.
- No new Moco fixtures — existing `goldsilvercrystal` (success) / `redgreenblueyellow` (error) mocks for `PUT /trainer/dex/{slug}`, already used by `CustomAlbumTrainerTest`, are reused as-is.
- No change to the read-only `.album-private` lock badge or its visibility rule.
