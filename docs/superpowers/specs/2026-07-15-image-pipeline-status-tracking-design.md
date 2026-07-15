# Track the update_images pipeline's status end-to-end

## Context

The `update_images` admin button (shipped separately — see
`2026-07-14-update-images-pipeline-trigger-design.md`) only reports
whether GitHub *accepted* the dispatch, by design ("fire-and-forget,
deliberately"). Once triggered, the actual chain is:

```
Workflow A runs in pokenini-icon (workflow_dispatch)
  → opens a PR against pokenini-icon's main
[you review & merge]
  → Workflow B runs in pokenini-icon (push to main, paths: images/**)
      → opens a PR against pokenini-resources
[you review & merge — unchanged, still manual]
```

Today, checking on any of this means going to look at GitHub directly.
This feature adds visibility into all four stages (Workflow A run, icon
PR, Workflow B run, resources PR) from the pokenini-web admin page.

Neither `pokenini-web` nor `pokenini-back` has a database (only ephemeral
Redis/APCu cache). `pokenini-api` is the only one of the three original
repos with persistent storage, and this feature stores state there.

`pokenini-api`'s existing `ActionLog` system (used for update/calculate/
invalidate) does not fit: it models a single fire-and-forget job
(`createdAt` → one async Messenger handler → one `doneAt` + result), with
no concept of intermediate steps. This feature introduces a new,
separate entity rather than stretching `ActionLog` to cover something it
wasn't designed for.

## Goal

Show, on the pokenini-web admin page, the status of the most recent
`update_images` trigger: has Workflow A finished, is there a PR open (or
merged) on `pokenini-icon`, has Workflow B run, is there a PR open on
`pokenini-resources`. Persist this in `pokenini-api` so it survives
`pokenini-back` restarts/cache eviction.

## Non-goals (explicitly out of scope for this iteration)

- No GitHub webhook, no new secret on `pokenini-icon` for calling back
  into `pokenini-api`/`pokenini-back`, no publicly-reachable endpoint on
  `pokenini-api`. All status comes from `pokenini-back` polling GitHub's
  API on request (see "Mechanism" below) — the user explicitly chose
  this over a real-time callback/webhook design.
- No automatic background polling or live-updating UI (no JS timers,
  no auto-refresh). Checking status is an explicit action, matching the
  existing "click to refresh" pattern already used for pending admin
  actions elsewhere on this page.
- No history of past runs — only the latest trigger's status is shown
  (unlike `ActionLog`'s current/last pairing). If you click the button
  again before finishing reviewing the previous run, the previous run's
  status is simply overwritten.
- Workflow B needs no changes — it's matched purely by `head_sha`
  against the icon PR's merge commit, not by any correlation id of its
  own.

## Design

### Overall flow

```
pokenini-web (admin page, "Refresh status" link)
  → pokenini-back GET /istration/action/trigger/update_images/status?refresh=1
      → GET pokenini-api: latest ImagePipelineRun (or none)
      → for whichever stage isn't yet in a final state, poll GitHub:
          1. Workflow A run — matched by a correlation id embedded in
             the run's display title (see "Workflow A change" below)
          2. PR on pokenini-icon — branch name is deterministic once the
             run id is known (`update-images-<run id>`, from the
             existing Workflow A)
          3. Workflow B run — matched by `head_sha` equal to the icon
             PR's `merge_commit_sha`
          4. PR on pokenini-resources — branch name is deterministic
             once the Workflow B run's `head_sha` is known
             (`sync-images-<head_sha>`, from the existing Workflow B)
      → PATCH pokenini-api with whatever new information was found
      → return the merged snapshot to pokenini-web
  → pokenini-web GET .../status (no `refresh` param) just reads the last
    known snapshot from pokenini-api via pokenini-back, no GitHub calls —
    this is what renders by default when the admin page loads, so a
    normal page view never makes live GitHub API calls.
```

### Data model (new, in pokenini-api)

New entity `ImagePipelineRun` (`src/Entity/ImagePipelineRun.php`), following
this repo's existing convention (`BaseEntityTrait` for a UUID id, public
properties, no getters/setters beyond the id):

```php
#[ORM\Entity]
final class ImagePipelineRun
{
    use BaseEntityTrait;

    #[ORM\Column(unique: true)]
    public string $correlationId;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    public \DateTime $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    public \DateTime $updatedAt;

    #[ORM\Column(nullable: true)]
    public ?int $workflowARunId = null;
    #[ORM\Column(nullable: true)]
    public ?string $workflowAStatus = null;       // queued|in_progress|completed
    #[ORM\Column(nullable: true)]
    public ?string $workflowAConclusion = null;   // success|failure|cancelled
    #[ORM\Column(nullable: true)]
    public ?string $workflowAUrl = null;

    #[ORM\Column(nullable: true)]
    public ?int $iconPrNumber = null;
    #[ORM\Column(nullable: true)]
    public ?string $iconPrUrl = null;
    #[ORM\Column(nullable: true)]
    public ?string $iconPrState = null;           // open|merged|closed
    #[ORM\Column(nullable: true)]
    public ?string $iconPrMergeCommitSha = null;

    #[ORM\Column(nullable: true)]
    public ?int $workflowBRunId = null;
    #[ORM\Column(nullable: true)]
    public ?string $workflowBStatus = null;
    #[ORM\Column(nullable: true)]
    public ?string $workflowBConclusion = null;
    #[ORM\Column(nullable: true)]
    public ?string $workflowBUrl = null;

    #[ORM\Column(nullable: true)]
    public ?int $resourcesPrNumber = null;
    #[ORM\Column(nullable: true)]
    public ?string $resourcesPrUrl = null;
    #[ORM\Column(nullable: true)]
    public ?string $resourcesPrState = null;      // open|merged|closed

    public function __construct(string $correlationId)
    {
        $this->correlationId = $correlationId;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }
}
```

Migration generated the normal way
(`make sf c="doctrine:migration:diff --no-interaction"`), landing under
`migrations/2026/07/`.

**Endpoints** (`#[Route('/istration/image-pipeline-runs')]`, matching this
repo's `/istration/{verb}` admin-route convention — no extra auth beyond
the existing global `ROLE_API` Basic-auth firewall that already covers
every route here):

- `POST /istration/image-pipeline-runs` — body `{"correlationId": "..."}`,
  creates a new row. Called by `pokenini-back` right after a successful
  dispatch.
- `PATCH /istration/image-pipeline-runs/{correlationId}` — body: any
  subset of the nullable fields above, updates them and bumps
  `updatedAt`. Called by `pokenini-back` after each poll that learns
  something new.
- `GET /istration/image-pipeline-runs/latest` — returns the most
  recently created row as JSON (404/empty if none exist yet). This is
  the single source of truth `pokenini-back` reads on every status
  request, live-GitHub-poll or not.

`Repository` writes via `EntityManagerInterface::persist()/flush()`
(mirroring `PokedexRepository`'s role as the data-access layer for this
`Service`), matching this repo's existing "Controller → Service →
Repository" layering. `Controller` reads/writes plain JSON bodies (no
serializer/DTO needed for the write endpoints, matching
`AlbumUpsertController`'s precedent of reading a raw body); the `GET`
endpoint follows the existing `*ResponseFactory` + DTO pattern used by
`CatchStatesController` for consistency with other read endpoints.

### Workflow A change (pokenini-icon)

`update-images.yml` gains a `workflow_dispatch` input and a `run-name` so
a later poll can find the exact run this specific button click started
(GitHub's workflow-run list has no other way to correlate a
`workflow_dispatch` call with the resulting run):

```yaml
on:
  workflow_dispatch:
    inputs:
      correlation_id:
        required: true
        type: string

run-name: "Update images (${{ inputs.correlation_id }})"
```

No other change to this workflow. Workflow B (`publish-images-to-resources.yml`)
is unchanged — it's matched by `head_sha`, not by anything it declares
itself.

### pokenini-back changes

- `GithubActionsApiService::dispatchWorkflow()` gains a `string $correlationId`
  parameter, sent as the `inputs.correlation_id` value in the dispatch
  request body (GitHub's `workflow_dispatch` API accepts an `inputs`
  object alongside `ref`).
- New read methods (same class or a sibling — implementer's call, guided
  by single-responsibility): list workflow runs for a given workflow
  file (optionally filtered by `head_sha`), and search pull requests by
  exact head branch (`GET /repos/{repo}/pulls?head={owner}:{branch}&state=all`).
  Exact GitHub REST API field names (e.g. a run's `display_title`, a
  PR's `merge_commit_sha`) should be verified against GitHub's current
  API docs at implementation time.
- New service (e.g. `ImagePipelineStatusService`) implementing the
  reconciliation flow described above: read latest from pokenini-api →
  if `refresh` requested and a stage isn't final yet, poll GitHub for
  that stage → PATCH pokenini-api → return the merged view.
- `TriggerImagesPipelineService::triggerUpdateImages()` (or its caller)
  generates the `correlationId` (e.g. a UUID), passes it to
  `dispatchWorkflow()`, and calls the new `POST .../image-pipeline-runs`
  endpoint on pokenini-api to create the row — both only after the
  GitHub dispatch itself succeeds.
- New controller: `GET /istration/action/trigger/update_images/status`,
  query param `refresh` (presence triggers the live-poll path; its
  absence returns the last known pokenini-api snapshot untouched — this
  is what keeps a normal admin-page load free of GitHub API calls).

### pokenini-web UI

The existing `admin.action(...)` macro renders one ok/ko/pending/idle
badge — it cannot represent 4 sequential stages, and reusing/branching it
would ripple into the three unrelated existing action types. This gets a
small, dedicated Twig partial instead, rendered underneath the existing
"Trigger pipelines" section: one row per stage (Workflow A, icon PR,
Workflow B, resources PR), each showing idle/running/done/failed with a
link to the relevant GitHub run or PR once known, plus a "Refresh status"
link (`?refresh=1`-style, matching the existing manual-refresh convention
used elsewhere on this page — no auto-polling JS).

## Error handling

- If `pokenini-api` is unreachable when `pokenini-back` tries to persist
  a new run or a poll update, the trigger itself must still succeed (the
  dispatch already happened) — log the persistence failure, degrade to
  "status unknown," don't fail the button click over it.
- If GitHub's API rate-limits or errors during a poll, return whatever
  was already known from pokenini-api rather than failing the whole
  status request.
- A stage's absence (e.g. no PR found yet because Workflow A hasn't
  finished) is a normal "not there yet" state, not an error.

## Testing

- **pokenini-api**: unit-test the new `Service`/`Repository` with a real
  test database (matching this repo's existing conventions for
  entity-backed features); test the 3 endpoints.
- **pokenini-back**: unit-test the new GitHub-read methods (mocked
  `HttpClientInterface`, same pattern as `GithubActionsApiService`'s
  existing tests) and the reconciliation service (mocked pokenini-api
  client + mocked GitHub client, verifying the "poll only what's not yet
  final" branching); unit-test the new status controller.
- **pokenini-web**: unit-test the new template rendering with each
  combination of stage states; a Moco-backed integration test for the
  status endpoint's happy path (matching `ActionTriggerTest`'s
  conventions).
- **pokenini-icon**: validate the `run-name`/`inputs` YAML addition the
  same way the original workflow was validated (parse + structure
  check); confirm via one real end-to-end run that the run's
  `display_title` actually contains the correlation id as expected.
