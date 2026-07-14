# Trigger the image-update pipeline from the admin page

## Context

Pokémon sprite images ("big" and "small") are produced by scripts in the
private `pokenini-icon` repository (a separate project, sibling to this
workspace):

- `make pdb_dl pdb_convert pdb_copy` — downloads "big" sprites from
  pokemondb.net for every entry in `src/download-from-pokemon-db/pokemons-list.txt`,
  converts them to WebP, and copies changed files into `images/big/{regular,shiny}`.
- `make mbc_prepare mbc_cut mbc_copy` — cuts "small" sprites out of a static,
  hand-sourced spritesheet (`resources/regulars.png` / `shinies.png`) via a
  dockerized Python/character-recognition tool, copying changed files into
  `images/small/{regular,shiny}`.
- `make placeholders` — fills in missing shiny placeholders.
- `make check_size` — reports (but does not fail on) images with the wrong
  dimensions.

Today this all runs by hand on the author's machine, followed by a manual
`make copy` in `pokenini-resources` (the public static-assets repo serving
images for pokénin.fr) and a manual commit/release to publish.

This is entirely separate from `AdminActionApiService` in `pokenini-back`,
which proxies admin buttons in `pokenini-web` to endpoints on `pokenini-api`
(update/calculate/invalidate). The image pipeline doesn't touch
`pokenini-api` at all — it's a GitHub Actions job in a different repo.

## Goal

Add a button on the pokenini-web admin page that triggers the full
`pokenini-icon` pipeline remotely (so it works from prod, not just a local
dev machine) and lands the result as a **pull request** on
`pokenini-resources` for review — it must not publish automatically, since
the pipeline has known manual-correction cases (mis-detected sprite
numbers, missing entries) that need a human look before going live.

## Non-goals (explicitly out of scope for this iteration)

- Auto-detecting which Pokémon/forms are new. `pokemons-list.txt` and the
  static spritesheets stay manually curated in `pokenini-icon`, as today.
  The button just re-runs the pipeline against whatever is currently
  committed there.
- Rewriting the bash/Python scripts. They run unmodified inside GitHub
  Actions; only a new workflow file wraps them.
- Live progress/completion feedback in pokenini-web. The button reports
  whether the *trigger* succeeded, not whether the pipeline finished — see
  "Fire-and-forget, deliberately" below.

## Design

### Overall flow

```
pokenini-web (admin button)
  → pokenini-back (POST /istration/action/trigger/update_images)
      → GitHub API: POST /repos/{owner}/pokenini-icon/actions/workflows/update-images.yml/dispatches
          → GitHub Actions runner, in pokenini-icon:
              make pdb_dl pdb_convert pdb_copy
              make mbc_prepare mbc_cut mbc_copy
              make placeholders
              make check_size            (output captured, non-blocking)
              checkout pokenini-resources as a sibling checkout
              make copy                  (existing Makefile target: cp -R ../pokenini-icon/images/* .)
              commit changed files to a new branch, push with a
              resources-scoped token, open a PR against pokenini-resources
              (PR body includes the check_size report)
```

Merging that PR and cutting a release in `pokenini-resources` stays exactly
as it is today (unchanged, manual).

### pokenini-icon

- New `.github/workflows/update-images.yml`, triggered only by
  `workflow_dispatch` (no push/schedule trigger — this is meant to be
  explicitly button-triggered).
- Steps: checkout `pokenini-icon`; checkout `pokenini-resources` into a
  sibling directory in the same job (so the existing relative path in
  `pokenini-resources`'s `make copy` — `../pokenini-icon/images/*` — keeps
  working unmodified); install `imagemagick`/`webp` via `apt-get`; run the
  Makefile targets above; capture `make check_size` output to a file; diff
  `pokenini-resources`; if there are changes, commit to a new branch
  (`update-images-<run-id>` or similar) and open a PR (e.g. via
  `peter-evans/create-pull-request`), with the check_size report pasted
  into the PR body; if there are no changes, the job ends without opening
  a PR.
- New repository secret `RESOURCES_PUSH_TOKEN`: a fine-grained PAT scoped
  **only** to `pokenini-resources`, with `contents: write` +
  `pull-requests: write`. The default `GITHUB_TOKEN` can't push to a
  different repo, hence the dedicated token.

### pokenini-back

New, separate from the existing `AdminActionApiService` (which is
hard-wired to pokenini-api's host/auth scheme — Basic auth, `apiUrl`,
`apiCafilePath` — none of which apply to the GitHub API).

- `src/Service/Api/GithubActionsApiService.php` — small dedicated HTTP
  client. One method, `dispatchWorkflow(): void`, `POST`s to
  `https://api.github.com/repos/{repo}/actions/workflows/{workflow}/dispatches`
  with `Authorization: Bearer <token>` and
  `Accept: application/vnd.github+json`. Wraps transport/HTTP exceptions
  into the existing `App\Exception\ModifyFailedException`, same as
  `AdminActionApiService`.
- New config: `GITHUB_IMAGES_WORKFLOW_TOKEN` (env secret — a fine-grained
  PAT scoped only to `pokenini-icon`, `actions: write`), plus
  non-secret config for the repo (`douzeensemble/pokenini-icon`) and
  workflow filename (`update-images.yml`).
- `src/Service/TriggerImagesPipelineService.php` — thin `Service` layer
  wrapping `GithubActionsApiService`, satisfying the Deptrac rule that
  controllers must not call `Service\Api` directly.
- `src/Controller/Admin/AdminActionTriggerController.php` — new sibling to
  `AdminActionUpdateController`/`AdminActionCalculateController`/
  `AdminActionInvalidateController`, route
  `POST /istration/action/trigger/{name}`, condition
  `name in ['update_images']`, same CSRF/rate-limit conventions as the
  others.
- `AbstractAdminActionController::doAction()` gets a new `case 'trigger'`
  that calls `TriggerImagesPipelineService::trigger($name)`. Unlike
  `update`/`calculate`, this case must **not** fall through to
  `$this->cacheInvalidatorService->invalidate($name)` — there's no cache
  invalidator registered for `update_images`, and `CacheInvalidatorService::invalidate()`
  throws `InvalidArgumentException` for unmatched types, which would
  incorrectly report the trigger as failed even when the GitHub dispatch
  succeeded. The invalidate call must be scoped to only the
  `update`/`calculate` cases.
- Response shape is unchanged: `AdminAction('trigger', 'update_images', 'ok'|'ko', '', $error)`.
  `ok` means "GitHub accepted the dispatch", not "images are updated" —
  this must not be reworded to imply completion.

### pokenini-web

- `src/Controller/AdminActionController.php`: new `trigger()` method,
  mirroring `update()`/`calculate()`/`invalidate()` — CSRF-checks token
  `admin_trigger`, condition `name in ['update_images']`, calls
  `$this->execute($name, 'trigger', 'POST')`. `AdminActionService::execute()`
  is already generic (`POST /istration/action/{action}/{item}`) and needs
  no change.
- `templates/Admin/_actions.html.twig`: new section using the existing
  `admin.action(...)` macro, e.g.:

  ```twig
  <div class="row px-4 py-5">
    <h2 class="mb-3 pb-2 border-bottom">{{ 'admin.actions.trigger_pipeline.title'|trans }}</h2>
    {% set triggerPipelineItems = { 'update_images': 'images' } %}
    {% for item, icon in triggerPipelineItems %}
      {{ admin.action('trigger_pipeline', 'trigger', item, icon, updatedItem, updatedAction, updatedState, actionLogsData) }}
    {% endfor %}
  </div>
  ```

  Since `pokenini-api` never logs a `trigger_update_images` entry in
  `actionLogsData`, the macro's existing fallback (`entry is null` →
  `idle` state) applies automatically — no template logic changes needed
  for that. This button will always render as a plain idle
  button-then-flash-message, never the pending/progress-bar UI the other
  buttons can show; that's intentional (see "Fire-and-forget,
  deliberately" below), not a bug to fix later.
- New translation keys (`messages+intl-icu.{en,fr}.yaml`):
  `admin.actions.trigger_pipeline.title`,
  `admin.actions.trigger_pipeline.update_images.title`,
  `admin.actions.trigger_pipeline.update_images.cta`.

### Fire-and-forget, deliberately

`workflow_dispatch` only confirms GitHub *accepted* the request — it
doesn't return a run ID or block until the workflow finishes. So:

- Success in the UI means "triggered", not "done". The button's
  surrounding copy/flash message must say so explicitly (e.g. "Pipeline
  déclenché — voir l'onglet Actions de pokenini-icon et la PR ouverte sur
  pokenini-resources").
- If the pipeline itself fails inside GitHub Actions (script error, Docker
  build failure), pokenini-web has no way to know — that's only visible in
  the GitHub Actions tab. No polling/webhook-back-channel is being built
  for this; if that gap turns out to matter in practice, it's a follow-up.

### Error handling

- Dispatch failures (bad/expired token, workflow renamed, GitHub API
  outage) surface as `ko` through the existing `AdminAction`/flash-message
  machinery — no new error-handling UI needed.
- `check_size.sh` never exits non-zero, so dimension mismatches never fail
  the GitHub Actions job; its output is only informational text in the PR
  body.
- If nothing changed since the last run (e.g. re-clicking the button with
  no new Pokémon added), the workflow completes without opening a PR —
  this is a normal, non-error outcome.

### Testing

- `pokenini-back`: unit-test `GithubActionsApiService` (mocked
  `HttpClientInterface`, matching the existing pattern for
  `AdminActionApiService`) and `TriggerImagesPipelineService`; unit-test
  the new controller and the modified `doAction()` branching (cover: the
  `trigger` case does *not* call `CacheInvalidatorService::invalidate()`).
  Must keep the project's 100% coverage / 100% MSI bar.
- `pokenini-web`: unit-test the new `trigger()` controller method the same
  way the existing `update()`/`calculate()`/`invalidate()` methods are
  tested (CSRF check, redirect target, session state).
- `pokenini-icon`: no existing automated-test convention for the
  Makefile/workflow layer. Validate the new workflow by actually running
  it once (`gh workflow run update-images.yml`) and checking the resulting
  PR, rather than writing CI-for-CI tests.
