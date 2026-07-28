# Design — Prevent leaving the album page during a pending catch-state change

**Date:** 2026-07-28
**Branch:** dev-20260728

## Context

`public/js/album-edit.js` handles catch-state changes on the album display page (`templates/Album/index.html.twig`). Each `<select>` change fires an independent `fetch()` PATCH to `/{locale}/album/{dex}/{pokemon}` (`saveChange`, `album-edit.js:93-127`). The `<select>` is disabled while its own request is in flight and re-enabled with a success/error toast once it resolves — but there is no aggregate signal of "at least one request is still pending", and nothing stops the user from navigating away (closing the tab, reloading, clicking a link) while a PATCH is mid-flight.

The app is plain server-rendered Twig with vanilla JS — no bundler, no Stimulus/Turbo, no SPA routing. Every navigation is a real browser navigation, so the native `beforeunload` event is the only (and sufficient) hook needed to cover all ways of leaving the page.

## Chosen approach

Track in-flight catch-state PATCH requests with a simple module-level counter in `album-edit.js`, and register a `beforeunload` listener that blocks navigation (via the native confirmation dialog) only while that counter is greater than zero.

No debouncing, no request queue, no "unsaved changes" concept beyond in-flight requests — confirmed as out of scope: as soon as all responses come back (success or error, since error already rolls back optimistically), the page is free to leave again.

## Behavior

1. Module-level `let pendingChangesCount = 0;` in `album-edit.js`.
2. `onChangeCatchState` increments `pendingChangesCount` before calling `saveChange`.
3. `saveChange`'s `fetch(...)` chain gets a `.finally()` that decrements `pendingChangesCount` exactly once per request. (Decrementing in both `.then` and `.catch` would double-count: on a non-200 response, the existing code `throw`s *inside* `.then`, which then also falls through to `.catch` — so a single `.finally()` is required, not one decrement per branch.)
4. `watchCatchStates()` (already only invoked when `allowedToEdit` is true, `templates/Album/index.html.twig:93`) additionally registers:
   ```js
   window.addEventListener("beforeunload", onBeforeUnload);
   ```
5. `onBeforeUnload(event)`: if `pendingChangesCount > 0`, calls `event.preventDefault()` and sets `event.returnValue = ""` to trigger the browser's native confirmation dialog. Modern browsers ignore any custom message text and show their own generic wording — this is expected and not worked around.

No Twig changes needed: the listener piggybacks on the existing `watchCatchStates()` call, which is already gated correctly.

## Changes

### 1. `public/js/album-edit.js`

```js
let pendingChangesCount = 0;

function watchCatchStates() {
  document.querySelectorAll(".album-container select").forEach(function (element) {
    element.dataset.committedValue = element.value;
    element.addEventListener("change", onChangeCatchState);
  });

  window.addEventListener("beforeunload", onBeforeUnload);
}

function onBeforeUnload(event) {
  if (pendingChangesCount > 0) {
    event.preventDefault();
    event.returnValue = "";
  }
}

function onChangeCatchState(event) {
  const target = event.target;
  const previousValue = target.dataset.committedValue ?? target.value;

  target.disabled = true;
  pendingChangesCount++;

  saveChange(target, previousValue);
}
```

In `saveChange`, add `.finally(() => { pendingChangesCount--; })` after the existing `.then(...).catch(...)` chain.

## Testing

**Empirical finding (spiked against this project's actual Chrome and Firefox Selenium containers before writing the plan):** WebDriver-initiated navigation (`Client::request()`) bypasses the `beforeunload` prompt entirely on both browsers — no dialog appears, no exception is thrown, navigation just succeeds. This is standard WebDriver-spec behavior (automated navigation is defined to skip the prompt), not a bug in this app or this test setup. A test that navigates away and asserts a native dialog via `switchTo()->alert()` is therefore not viable here.

**Chosen test approach:** exercise the real handler logic through a real browser, but observe it via a synthetic `beforeunload` event dispatched with `executeScript`, instead of relying on WebDriver's navigation/dialog interception. This still runs the actual `pendingChangesCount` counter and `onBeforeUnload` function shipped in `album-edit.js` — only the "is a real dialog shown" step is replaced with reading `event.defaultPrevented` back from the page.

New tests in `tests/src/Browser/Album/SelectAndLabelTest.php`, alongside `testActionCatchStateChangeSuccess`/`testActionCatchStateChangeError`, reusing the `demo.json` Moco fixture:

1. **Moco fixture with latency**: add an `ivysaur`-specific PATCH stub in `tests/resources/moco/Back/moco.json` (same pattern as the existing `squirtle`/`blastoise` overrides, inserted right before the generic catch-state stub so it takes priority), with `"response": {"status": "200", "latency": 3000}` so the request stays in flight long enough for the test to check the pending state.
2. **Handler prevents unload while a request is pending**: login, open `/fr/album/demo`, activate edit mode on `#ivysaur`, change its catch-state select to `toevolve` (fires the slow PATCH), then immediately run `executeScript` to dispatch a `beforeunload` event on `window` and read back `.defaultPrevented`. Assert it is `true`.
3. **Handler does not prevent unload once the request has resolved**: same flow, but wait for `assertSelectorWillBeVisible('#successToast-ivysaur')` first, then dispatch the same synthetic event and assert `.defaultPrevented` is `false`.

`executeScript` runs arbitrary JS in the page and can return a value, so the dispatch + read-back happens in one call, e.g.:
```js
const event = new Event('beforeunload', {cancelable: true});
window.dispatchEvent(event);
return event.defaultPrevented;
```

## Files to create/modify

| File | Action |
|---|---|
| `public/js/album-edit.js` | Add `pendingChangesCount` counter, `onBeforeUnload`, wire increments/decrements |
| `tests/resources/moco/Back/moco.json` | Add a Pokemon-specific PATCH stub with `"latency"` |
| `tests/src/Browser/Album/SelectAndLabelTest.php` | Add the two new tests above |

## Out of scope

- No debouncing/queueing of catch-state changes.
- No "unsaved edit mode" tracking — only actual in-flight PATCH requests block navigation.
- No custom dialog text (browsers force their own generic wording).
