# Design: automated Composer updates workflow (pokenini-web)

## Purpose

Automate what `make updates` does locally (bump every Composer dependency across the
main app and its 6 quality-tool sub-projects) via GitHub Actions, on a daily schedule,
opening/updating a pull request — a personal equivalent of Dependabot.

This is the same design already implemented and approved for `pokenini-api`
(see that repo's `docs/superpowers/specs/2026-07-12-composer-updates-automation-design.md`),
replicated here because `pokenini-web`'s `updates:` Makefile target, composite actions
(`./.github/actions/local-php`), and CI trigger conventions (`ci_codequality.yml`,
`ci_tests.yml`, `security.yml` all listen on `pull_request: ~`) are byte-for-byte
identical to `pokenini-api`'s on `main`. No new design decisions were needed; only the
cron offset differs (staggered against the other two repos' equivalent workflows to
avoid runner contention across the three repos at the same minute).

## Non-goals

- No per-tool/per-package PR splitting. One bundled PR.
- No Docker/DB stack involved — `composer update` needs only PHP + Composer.

## Trigger

New workflow file: `.github/workflows/composer_updates.yml`

- `schedule`: daily cron, `57 4 * * *` (04:57 UTC — offset from `pokenini-api`'s `17 4 * * *`).
- `workflow_dispatch`: manual run on demand.

## Job

Single job `composer-updates` on `ubuntu-latest`.

1. **Checkout** — plain `actions/checkout@v6` with the default `GITHUB_TOKEN` (no PAT).
   Kept off this step so the PAT isn't exposed as a persisted git credential during the
   8 `composer update` steps, each of which executes third-party package/plugin code.
2. **PHP setup** — reuse `./.github/actions/local-php`. No Docker compose stack.
3. **Composer updates** — one step per directory, same order as the `updates` Makefile
   target, each `composer update --bump-after-update --with-all-dependencies
   --optimize-autoloader`:
   - main app (`--working-dir=./`)
   - `tools/deptrac`, `tools/infection`, `tools/jsonlint`, `tools/php-cs-fixer`,
     `tools/phpmd`, `tools/phpstan`, `tools/psalm`
4. **Open/update PR** — `peter-evans/create-pull-request@v7`:
   - `token: ${{ secrets.PAT_TOKEN }}` (only step that needs it — authenticates the
     push/PR, which is what makes the PR trigger the downstream `pull_request`-gated
     workflows)
   - `branch: chore/composer-updates`, `delete-branch: true`
   - `commit-message` / `title`: `Composers updates`
   - `labels: dependencies` (confirmed this label exists in `pokenini-web`)

**Hardening:** top-level `permissions: contents: read` and a `concurrency` group
(`composer-updates`, `cancel-in-progress: true`).

## Secrets

- `PAT_TOKEN`: a fine-grained Personal Access Token scoped to `pokenini-web` with
  `contents: write` + `pull-requests: write`, added as a repo secret. GitHub secrets are
  repo-scoped, so this is a separate secret from `pokenini-api`'s `PAT_TOKEN` even though
  the name matches — must be created independently for this repo.

## Failure handling

Same as `pokenini-api`: a `composer update` failure fails the job (no PR created); no
diff means no PR (silent no-op).

## Testing / validation

YAML syntax check + Makefile parity check, same as `pokenini-api`. No application test
suite is affected by this change.
