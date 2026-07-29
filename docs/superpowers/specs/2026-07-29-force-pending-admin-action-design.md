# Force a pending admin action

## Problem

On `/{_locale}/istration/actions`, each admin action button (`update`, `calculate`, `invalidate`, `trigger`) is disabled while the last `ActionLog` for that action is in the `pending` state (has `createdAt` but no `doneAt` and no `errorTrace`). This state is reported by pokenini-back/pokenini-api and pokenini-web has no local knowledge of whether the job is actually still running.

In practice, jobs can get stuck in `pending` forever (crash, timeout on the API side) with no `doneAt` or `errorTrace` ever arriving. The admin then has no way to retrigger the action from the UI — the button stays disabled indefinitely, and the only path today is bypassing the browser entirely (e.g. curling the endpoint directly), since `AdminActionController` performs no server-side check on the action state at all — only CSRF.

## Goal

Let an admin force-trigger a pending action from the UI, with an explicit confirmation, instead of the button being permanently unclickable.

## Non-goals

- No server-side lock/guard is added in pokenini-web or requested from pokenini-back/pokenini-api. The backend already allows concurrent/duplicate triggers (confirmed: `AdminActionController` only checks CSRF), so "forcing" only removes a client-side restriction that never had a matching server-side guarantee.
- No change to how `pending`/`done`/`ko`/`idle` states are computed or displayed elsewhere (the "last action" panel, the progress bar, the refresh link all stay as-is).

## Design

### 1. Template — `templates/Admin/_macros.html.twig`, macro `actionButton`

- The button `admin-item-cta` is never rendered with the `disabled` attribute/class anymore — it stays clickable in every state.
- When `currentState == 'pending'`, compute elapsed time the same way the existing `progress` macro already does (`actionData.createdAt.diff(date('now'))` → days/h/i/s), and format it into a short human string (e.g. `3h 12min`, or `12 min` when under an hour, or `2j 4h` when over a day).
- Render that formatted duration into the translated confirmation string and put the result in a `data-confirm-message` attribute on the button. When not pending, the attribute is simply absent.
- The existing "refresh" link (`admin-item-refresh`, shown next to the button when pending) is unchanged.

### 2. Translations — `translations/messages+intl-icu.fr.yaml` / `messages+intl-icu.en.yaml`

New key under `admin.action`:

```yaml
admin:
  action:
    force_confirm: "Une exécution est en cours depuis {duration}. Voulez-vous quand même relancer cette action ?"
```

(English equivalent in the `.en.yaml` file.) `{duration}` is substituted with the string computed in step 1 before being placed in the `data-confirm-message` attribute — the translation itself only needs the ICU placeholder.

### 3. JS — `public/js/admin.js`

New function `watchForceConfirm()`, following the existing plain-function style already used by `watchActionLogToggles()`:

- Selects every `.admin-item-cta` button that has a `data-confirm-message` attribute.
- Attaches a `submit` listener on the button's parent `<form>`.
- On submit: reads `data-confirm-message`, calls `window.confirm(message)`. If the user cancels, `event.preventDefault()`. If confirmed, the form submits normally — identical request to a normal (non-pending) action trigger.
- Buttons without `data-confirm-message` (idle/done/ko states) are untouched and submit immediately, exactly as today.

Wired in `templates/Admin/actions.html.twig`'s existing inline `foot_javascript` block, alongside the current `watchActionLogToggles();` call.

### Error handling / edge cases

- No new error paths: the POST request and its handling in `AdminActionController` / `AdminActionService` are completely unchanged. A forced trigger looks identical to a normal trigger from the server's point of view.
- If `data-confirm-message` is somehow present but empty (shouldn't happen given the Twig logic above), `window.confirm('')` still opens a dialog — acceptable, not worth guarding against.

## Testing

- `tests/src/Integration/Controller/Admin/AdminPageTest.php`: replace the assertions on `.admin-item-cta.disabled` (currently lines ~99-102) with assertions that the same 3 actions expose `button.admin-item-cta[data-confirm-message]`, and add an assertion that `.admin-item-cta.disabled` no longer exists anywhere on the page.
- New browser test in `tests/src/Browser/Admin/` (Panther): trigger a pending action, intercept the native `confirm()` dialog (accept and dismiss cases), and assert the POST does/doesn't go through accordingly.
