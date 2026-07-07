# Design — Share link auto-copy + toast

**Date:** 2026-07-06
**Branch:** feature/modal_copypaste
**Issue:** https://github.com/douzeEnsemble/pokenini-web/issues/284

## Context

The album intro (`templates/Album/_intro.html.twig`) shows a "share" icon (`.share`, visible only `{% if not dex.isPrivate %}`) whose `href` is the current album view URL with `?t=<trainerId>` appended. Today clicking it just navigates to that same URL (which happens to render the shared/read-only view). There is no copy-to-clipboard affordance.

Issue #284 asks that clicking the share link copy it to the clipboard and confirm with a toast, instead of navigating.

## Chosen approach

Intercept the click in JS, copy the link via the Clipboard API, and confirm with a toast. No modal, no visible link/input, no copy button — the simplest option that satisfies the issue's core ask (auto-copy + toast confirmation).

Two other approaches were considered and rejected for now: (a) a modal showing the link in a readonly input with a manual copy button, closer to the issue's literal wording but more UI than requested after discussion; (b) leaving navigation as a fallback when the Clipboard API is unavailable — rejected because it would silently change page state instead of just informing the user, and the plain `<a href>` is still there for manual copy (right-click → copy link) regardless.

## Behavior

On click on `.share`:
1. `preventDefault()` — no navigation.
2. Read `event.currentTarget.href`. The DOM `.href` property is always browser-resolved to an absolute URL, even though the Twig-rendered attribute is a relative path — no template change needed to obtain the absolute URL.
3. If `navigator.clipboard` exists, call `writeText(shareUrl)`:
  - resolved → show `#shareToastSuccess`.
  - rejected (e.g. permission denied) → show `#shareToastError`.
4. If `navigator.clipboard` doesn't exist (non-secure context) → show `#shareToastError` directly, no exception thrown.

## Changes

### 1. `public/js/album.js`

Add, following the existing `watchScreenshotMode` / `onEnableScreenshotMode` style:

```js
function watchShareLink() {
  document.querySelectorAll(".share").forEach(function (element) {
    element.addEventListener("click", onShareLinkClick);
  });
}

function onShareLinkClick(event) {
  event.preventDefault();

  const shareUrl = event.currentTarget.href;

  if (!navigator.clipboard) {
    new bootstrap.Toast(document.getElementById("shareToastError")).show();
    return;
  }

  navigator.clipboard.writeText(shareUrl)
    .then(function () {
      new bootstrap.Toast(document.getElementById("shareToastSuccess")).show();
    })
    .catch(function () {
      new bootstrap.Toast(document.getElementById("shareToastError")).show();
    });
}
```

### 2. `templates/Album/index.html.twig`

Call `watchShareLink();` in the existing unconditional IIFE, next to `watchScreenshotMode();` (this script block already runs regardless of `allowedToEdit`, matching the share link's own visibility condition which only depends on `dex.isPrivate`).

### 3. `templates/Album/_album_macros.html.twig`

Add a `shareToasts()` macro (no params, unlike `toasts(item)` which is per-Pokémon) producing the two toast elements, mirroring the existing `toasts(item)` macro structure:

```twig
{% macro shareToasts() %}
  <div id="shareToastSuccess" class="toast text-bg-success" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body">{{ 'album.share.toast.success'|trans }}</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>

  <div id="shareToastError" class="toast text-bg-danger" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body">{{ 'album.share.toast.error'|trans }}</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
{% endmacro %}
```

### 4. `templates/Album/_toasts.html.twig`

Currently the whole container is gated by `allowedToEdit`. Widen the gate so the container renders whenever either condition applies, and render each toast group independently inside:

```twig
{% import "Album/_album_macros.html.twig" as macro %}

{% if allowedToEdit or not dex.isPrivate %}
    <div class="toast-container position-fixed bottom-0 mb-5 end-0 p-3">
        {% if allowedToEdit %}
            {% for item in list %}
                {{ macro.toasts(item) }}
            {% endfor %}
        {% endif %}

        {% if not dex.isPrivate %}
            {{ macro.shareToasts() }}
        {% endif %}
    </div>
{% endif %}
```

This avoids two overlapping fixed-position toast containers when both conditions are true.

### 5. Translations (`translations/messages+intl-icu.{en,fr}.yaml`)

Add under `album:`:

```yaml
  share:
    toast:
      success: "Link copied to the clipboard!"
      error: "Could not copy the link automatically, please copy it manually."
```

(French wording to be adapted equivalently, e.g. `"Lien copié dans le presse-papier !"` / `"Impossible de copier le lien automatiquement, merci de le copier manuellement."`)

## Testing

- `tests/src/Integration/Controller/Album/Display/IntroTest.php`: unaffected — the `.share` anchor's markup, class, and `href` are unchanged. No new assertions needed there (the toasts are a separate container not currently asserted on).
- New browser test (`tests/src/Browser/Album/...`, following the `ModalTest.php` pattern with `AbstractBrowserTestCase` + Panther): load an album page with a visible share link, click `.share`, then assert:
  - the browser did not navigate away (URL unchanged) — confirms `preventDefault()` ran;
  - one of `#shareToastSuccess` / `#shareToastError` becomes visible — not asserting which one, since Clipboard API availability depends on the test environment (the app is served over plain `http://web` in CI, a non-secure context, so the error path is the realistic CI outcome; asserting "a toast appeared" rather than which one keeps the test meaningful without being tied to that environment detail).

## Files to create/modify

| File | Action |
|---|---|
| `public/js/album.js` | Add `watchShareLink()` / `onShareLinkClick()` |
| `templates/Album/index.html.twig` | Call `watchShareLink();` |
| `templates/Album/_album_macros.html.twig` | Add `shareToasts()` macro |
| `templates/Album/_toasts.html.twig` | Widen gate, render both toast groups |
| `translations/messages+intl-icu.en.yaml` | Add `album.share.toast.*` |
| `translations/messages+intl-icu.fr.yaml` | Add `album.share.toast.*` |
| `tests/src/Browser/Album/ShareLinkTest.php` (new) | Click → no navigation + toast visible |

## Out of scope

- No modal, no visible link/input, no manual copy button (simplified per user decision).
- No change to the `.share` anchor's `href`/attributes/visibility condition.
- No change to `IntroTest.php` (existing assertions already cover `.share` presence/href correctly).
