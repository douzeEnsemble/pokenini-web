# Image Pipeline Status UI Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Show the `update_images` pipeline's current status (Workflow A, icon PR, Workflow B, resources PR) on the admin actions page, with an explicit "Refresh status" link that polls fresh data through `pokenini-back`.

**Architecture:** A new `Service\Back\GetImagePipelineStatusService` (mirroring `GetActionLogsService`) calls `pokenini-back`'s `GET /istration/action/trigger/update_images/status`, deserializing into new `ResponseObject` classes. `AdminController::actions()` gains a `Request` parameter to read the `refresh` query flag and passes the resulting status to the template. A new, dedicated Twig partial renders the 4 stages — the existing `admin.action(...)` macro only handles a single ok/ko/pending badge and can't represent this.

**Tech Stack:** Symfony 8.0 / PHP ≥ 8.5, Twig, PHPUnit, Moco.

## Global Constraints

- `declare(strict_types=1)` everywhere new. `ResponseObject` classes `final`, no logic (matching `ActionLog.php`'s convention — plain data, populated by the Serializer).
- No auto-polling JS — "Refresh status" is a plain link (`?refresh=1`), matching the existing manual-refresh pattern elsewhere on this page (see companion spec, "Non-goals").
- A page load *without* `refresh` in the query string must not cause `pokenini-back` to poll GitHub — `GetImagePipelineStatusService` just forwards whatever `refresh` flag it's given; the no-GitHub-calls-by-default guarantee lives in `pokenini-back`, not here.
- Companion spec: `docs/superpowers/specs/2026-07-15-image-pipeline-status-tracking-design.md` (this repo).
- Companion plan this depends on for the endpoint contract: `../pokenini-back/docs/superpowers/plans/2026-07-15-image-pipeline-status-endpoint.md` — `GET /istration/action/trigger/update_images/status`, returning `{}` (no run yet) or `{"correlationId":"...","workflowA":{"state":"idle|running|done|failed","url":"..."},"iconPr":{"state":"idle|open|merged","url":"..."},"workflowB":{...},"resourcesPr":{...}}`.

---

### Task 1: `ResponseObject`s and `GetImagePipelineStatusService`

**Files:**
- Create: `src/ResponseObject/ImagePipelineStageStatus.php`
- Create: `src/ResponseObject/ImagePipelineStatus.php`
- Create: `src/Service/Back/GetImagePipelineStatusService.php`
- Test: `tests/src/Unit/Service/Back/GetImagePipelineStatusServiceTest.php`

**Interfaces:**
- Produces: `GetImagePipelineStatusService::get(bool $refresh): ?ImagePipelineStatus` — `null` if `pokenini-back` returns `{}` (no run has ever been triggered). Consumed by `AdminController` in Task 2.

- [ ] **Step 1: Write the ResponseObjects**

Create `src/ResponseObject/ImagePipelineStageStatus.php`:

```php
<?php

declare(strict_types=1);

namespace App\ResponseObject;

final class ImagePipelineStageStatus
{
    public function __construct(
        public readonly string $state,
        public readonly ?string $url,
    ) {}
}
```

Create `src/ResponseObject/ImagePipelineStatus.php`:

```php
<?php

declare(strict_types=1);

namespace App\ResponseObject;

final class ImagePipelineStatus
{
    public function __construct(
        public readonly string $correlationId,
        public readonly ImagePipelineStageStatus $workflowA,
        public readonly ImagePipelineStageStatus $iconPr,
        public readonly ImagePipelineStageStatus $workflowB,
        public readonly ImagePipelineStageStatus $resourcesPr,
    ) {}
}
```

(No `#[SerializedName]` needed — `pokenini-back`'s JSON already uses these exact camelCase keys, since it's a direct `json_encode` of a PHP object with these same property names, not passed through the Serializer component on that side.)

- [ ] **Step 2: Write the failing test**

Create `tests/src/Unit/Service/Back/GetImagePipelineStatusServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\Security\UserTokenServiceInterface;
use App\Service\Back\GetImagePipelineStatusService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal
 */
#[CoversClass(GetImagePipelineStatusService::class)]
final class GetImagePipelineStatusServiceTest extends TestCase
{
    public function testGetReturnsNullWhenNoRunExists(): void
    {
        $service = $this->getService('{}', 'https://back.domain/istration/action/trigger/update_images/status');

        $this->assertNull($service->get(false));
    }

    public function testGetWithRefreshAppendsQueryParam(): void
    {
        $service = $this->getService('{}', 'https://back.domain/istration/action/trigger/update_images/status?refresh=1');

        $this->assertNull($service->get(true));
    }

    public function testGetDeserializesStatus(): void
    {
        $json = <<<'JSON'
            {
                "correlationId": "corr-1",
                "workflowA": {"state": "done", "url": "https://github.com/x/y/actions/runs/1"},
                "iconPr": {"state": "merged", "url": "https://github.com/x/y/pull/2"},
                "workflowB": {"state": "idle", "url": null},
                "resourcesPr": {"state": "idle", "url": null}
            }
            JSON;

        $service = $this->getService($json, 'https://back.domain/istration/action/trigger/update_images/status');

        $status = $service->get(false);

        $this->assertNotNull($status);
        $this->assertSame('corr-1', $status->correlationId);
        $this->assertSame('done', $status->workflowA->state);
        $this->assertSame('merged', $status->iconPr->state);
    }

    private function getService(string $responseBody, string $expectedUrl): GetImagePipelineStatusService
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getContent')->willReturn($responseBody);
        $response->method('getStatusCode')->willReturn(200);

        $client = $this->createMock(HttpClientInterface::class);
        $client
            ->expects($this->once())
            ->method('request')
            ->with('GET', $expectedUrl, $this->anything())
            ->willReturn($response)
        ;

        $userTokenService = $this->createStub(UserTokenServiceInterface::class);

        return new GetImagePipelineStatusService(
            $this->createStub(LoggerInterface::class),
            $client,
            'https://back.domain',
            './resources/certificates/cacert.pem',
            $userTokenService,
            self::getContainer()->get('serializer'),
        );
    }
}
```

Note: if this repo's other `Service\Back` unit tests build a real `SerializerInterface` a different way (rather than `self::getContainer()->get('serializer')`, which requires a kernel), check `tests/src/Unit/Service/Back/GetActionLogsServiceTest.php` (if it exists) for the exact pattern used there and mirror it instead — don't introduce a kernel-booting dependency into a `Unit` test if the existing convention avoids one.

- [ ] **Step 3: Run the test and confirm it fails**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/Back/GetImagePipelineStatusServiceTest.php`
Expected: FAIL — `Class "App\Service\Back\GetImagePipelineStatusService" not found`.

- [ ] **Step 4: Write the implementation**

Create `src/Service/Back/GetImagePipelineStatusService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\ResponseObject\ImagePipelineStatus;

class GetImagePipelineStatusService extends AbstractBackService
{
    public function get(bool $refresh): ?ImagePipelineStatus
    {
        $endpointUrl = '/istration/action/trigger/update_images/status';

        if ($refresh) {
            $endpointUrl .= '?refresh=1';
        }

        $content = $this->requestContent('GET', $endpointUrl);

        if ('{}' === trim($content)) {
            return null;
        }

        /** @var ImagePipelineStatus */
        return $this->serializer->deserialize($content, ImagePipelineStatus::class, 'json');
    }
}
```

- [ ] **Step 5: Run the test and confirm it passes**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/Back/GetImagePipelineStatusServiceTest.php`
Expected: `OK (3 tests, ...)`

- [ ] **Step 6: Commit**

```bash
git add src/ResponseObject/ImagePipelineStageStatus.php src/ResponseObject/ImagePipelineStatus.php src/Service/Back/GetImagePipelineStatusService.php tests/src/Unit/Service/Back/GetImagePipelineStatusServiceTest.php
git commit -m "Add GetImagePipelineStatusService and its ResponseObjects"
```

---

### Task 2: Wire the status into `AdminController::actions()`

**Files:**
- Modify: `src/Controller/AdminController.php`
- Modify: `tests/src/Unit/Controller/AdminControllerTest.php`

**Interfaces:**
- Modifies: `AdminController::actions(RequestStack $requestStack, Request $request): Response` — new `Request $request` parameter, reads `$request->query->has('refresh')`, passes a new `imagePipelineStatus` variable to the template.

- [ ] **Step 1: Update the existing tests first**

`AdminControllerTest`'s `testAdminAction()` and `testAdminActionError()` each build a controller via `getController()` and assert the exact `$twig->render()` call arguments and exact `container->get()`/`requestStack->getSession()` call counts. This change is purely additive (one new constructor dependency, one new template variable, no new session/container touches), so **all existing call-count numbers in these two tests stay exactly the same** — only these things change:

1. `getController()` gains a mocked `GetImagePipelineStatusService` and passes it to `new AdminController(...)`.
2. Both `$twig->method('render')->with('Admin/actions.html.twig', [...])` expectations gain `'imagePipelineStatus' => null` in the expected array (the mocked service returns `null` by default in these two tests, since they're not testing the status feature itself).
3. Both `$controller->actions($requestStack)` calls become `$controller->actions($requestStack, new Request())` (a plain, empty `Request` — no `refresh` query param).
4. `testIndexRedirectsToActions()` is untouched (it doesn't call `actions()`).

Update `private function getController(): AdminController` to:

```php
    private function getController(): AdminController
    {
        $getActionLogsService = $this->createMock(GetActionLogsService::class);
        $getActionLogsService
            ->expects($this->once())
            ->method('get')
            ->willReturn([])
        ;

        $getImagePipelineStatusService = $this->createMock(GetImagePipelineStatusService::class);
        $getImagePipelineStatusService
            ->expects($this->once())
            ->method('get')
            ->with(false)
            ->willReturn(null)
        ;

        return new AdminController($getActionLogsService, $getImagePipelineStatusService);
    }
```

Add `use App\Service\Back\GetImagePipelineStatusService;` and `use Symfony\Component\HttpFoundation\Request;` to the top of the test file.

In `testAdminAction()` and `testAdminActionError()`, change:
```php
        $twig
            ->expects($this->once())
            ->method('render')
            ->with(
                'Admin/actions.html.twig',
                [
                    'actionLogsData' => [],
                ]
            )
            ->willReturn('<html></html>')
        ;
```
to:
```php
        $twig
            ->expects($this->once())
            ->method('render')
            ->with(
                'Admin/actions.html.twig',
                [
                    'actionLogsData' => [],
                    'imagePipelineStatus' => null,
                ]
            )
            ->willReturn('<html></html>')
        ;
```
and change:
```php
        $controller->actions($requestStack);
```
to:
```php
        $controller->actions($requestStack, new Request());
```
in both test methods.

- [ ] **Step 2: Run the tests and confirm they fail**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Controller/AdminControllerTest.php`
Expected: FAIL — either a constructor-argument-count error (`getController()` now passes 2 args to a 1-arg constructor) or a missing-argument error on `actions()`, depending on which you changed first. Confirm the failure is exactly that (not something else), then implement.

- [ ] **Step 3: Update the controller**

In `src/Controller/AdminController.php`, add the import and constructor parameter:

```php
use App\Service\Back\GetImagePipelineStatusService;
```

```php
    public function __construct(
        private readonly GetActionLogsService $getActionLogsService,
        private readonly GetImagePipelineStatusService $getImagePipelineStatusService,
    ) {}
```

Update the `actions()` method:

```php
    #[Route('/actions', methods: ['GET'], name: 'app_admin_actions')]
    public function actions(RequestStack $requestStack, Request $request): Response
    {
        $session = $requestStack->getSession();

        /** @var ?AdminAction $adminAction */
        $adminAction = $session->get(AdminActionController::SESSION_ACTION_DATA);
        $session->remove(AdminActionController::SESSION_ACTION_DATA);

        if (null !== $adminAction) {
            if ('' !== $adminAction->error) {
                $this->addFlash('error', $adminAction->error);
            }

            $this->addFlash('action', $adminAction->action);
            $this->addFlash('item', $adminAction->item);
            $this->addFlash('state', $adminAction->state);
        }

        $actionLogsData = $this->getActionLogsService->get();
        $imagePipelineStatus = $this->getImagePipelineStatusService->get($request->query->has('refresh'));

        return $this->render(
            'Admin/actions.html.twig',
            [
                'actionLogsData' => $actionLogsData,
                'imagePipelineStatus' => $imagePipelineStatus,
            ]
        );
    }
```

Add `use Symfony\Component\HttpFoundation\Request;` to the top of the file.

- [ ] **Step 4: Run the tests and confirm they pass**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Controller/AdminControllerTest.php`
Expected: `OK (3 tests, ...)`

- [ ] **Step 5: Commit**

```bash
git add src/Controller/AdminController.php tests/src/Unit/Controller/AdminControllerTest.php
git commit -m "Pass image pipeline status to the admin actions template"
```

---

### Task 3: Twig partial and translations

**Files:**
- Create: `templates/Admin/_pipeline_status.html.twig`
- Modify: `templates/Admin/_actions.html.twig`
- Modify: `translations/messages+intl-icu.en.yaml`
- Modify: `translations/messages+intl-icu.fr.yaml`

**Interfaces:**
- Consumes: `imagePipelineStatus` (an `App\ResponseObject\ImagePipelineStatus|null`), passed from `AdminController::actions()` (Task 2), available in `actions.html.twig`'s context and therefore in the included `_actions.html.twig`.

- [ ] **Step 1: Write the partial**

Create `templates/Admin/_pipeline_status.html.twig`:

```twig
{% if imagePipelineStatus is not null %}
  <div class="row px-4 pb-4">
    <div class="col-12">
      {% set stageColor = {
        'idle': 'light',
        'running': 'info',
        'open': 'info',
        'done': 'success',
        'merged': 'success',
        'failed': 'danger',
      } %}
      {% set stages = [
        {'label': 'workflow_a', 'stage': imagePipelineStatus.workflowA},
        {'label': 'icon_pr', 'stage': imagePipelineStatus.iconPr},
        {'label': 'workflow_b', 'stage': imagePipelineStatus.workflowB},
        {'label': 'resources_pr', 'stage': imagePipelineStatus.resourcesPr},
      ] %}
      <dl class="row admin-pipeline-status">
        {% for item in stages %}
          <dt class="col-6 col-md-3">
            {{ ('admin.pipeline_status.'~item.label)|trans }}
          </dt>
          <dd class="col-6 col-md-3">
            <span class="badge text-bg-{{ stageColor[item.stage.state]|default('light') }}">
              {{ ('admin.pipeline_status.state.'~item.stage.state)|trans }}
            </span>
            {% if item.stage.url is not empty %}
              <a href="{{ item.stage.url }}" target="_blank" rel="noopener">
                <i class="bi bi-box-arrow-up-right"></i>
              </a>
            {% endif %}
          </dd>
        {% endfor %}
      </dl>
      <a href="{{ path(app.request.attributes.get('_route'), app.request.query.all|merge({'refresh': 1})) }}" class="btn btn-outline-info btn-sm">
        {{ 'admin.pipeline_status.refresh'|trans }}
      </a>
    </div>
  </div>
{% endif %}
```

- [ ] **Step 2: Include the partial**

Append to the end of `templates/Admin/_actions.html.twig`:

```twig

{% include 'Admin/_pipeline_status.html.twig' %}
```

- [ ] **Step 3: Add English translation keys**

In `translations/messages+intl-icu.en.yaml`, add a new top-level `admin.pipeline_status` block (place it near the existing `admin.action`/`admin.toggle` keys, same indent level as `action:` under `admin:`):

```yaml
  pipeline_status:
    workflow_a: "Pipeline run"
    icon_pr: "Review PR (pokenini-icon)"
    workflow_b: "Publish run"
    resources_pr: "Review PR (pokenini-resources)"
    refresh: "Refresh status"
    state:
      idle: "Not started"
      running: "Running"
      open: "Open"
      done: "Done"
      merged: "Merged"
      failed: "Failed"
```

- [ ] **Step 4: Add French translation keys**

In `translations/messages+intl-icu.fr.yaml`, same position:

```yaml
  pipeline_status:
    workflow_a: "Exécution du pipeline"
    icon_pr: "PR à relire (pokenini-icon)"
    workflow_b: "Exécution de la publication"
    resources_pr: "PR à relire (pokenini-resources)"
    refresh: "Rafraîchir le statut"
    state:
      idle: "Pas commencé"
      running: "En cours"
      open: "Ouverte"
      done: "Terminé"
      merged: "Mergée"
      failed: "Échoué"
```

- [ ] **Step 5: Validate**

Run:
```bash
docker compose exec php php bin/console lint:yaml translations/messages+intl-icu.en.yaml translations/messages+intl-icu.fr.yaml
docker compose exec php php bin/console lint:twig templates/Admin/_pipeline_status.html.twig templates/Admin/_actions.html.twig
```
Expected: both `[OK]`.

- [ ] **Step 6: Manual check**

If the docker stack is running, visit `http://localhost/fr/connect/f/c?t=admin` then `http://localhost/fr/istration/actions`. With no run ever triggered, the new section should not render at all (`imagePipelineStatus is null`). This can't be fully exercised manually until Task 4's Moco fixture exists to simulate a real status — do a visual smoke check now just to confirm no Twig error, and revisit after Task 4.

- [ ] **Step 7: Commit**

```bash
git add templates/Admin/_pipeline_status.html.twig templates/Admin/_actions.html.twig translations/messages+intl-icu.en.yaml translations/messages+intl-icu.fr.yaml
git commit -m "Render the image pipeline status section on the admin actions page"
```

---

### Task 4: Moco fixture and integration test

**Files:**
- Modify: `tests/resources/moco/Back/moco.json`
- Create: `tests/src/Integration/Controller/Admin/PipelineStatusTest.php`

**Interfaces:**
- None new — this proves Tasks 1-3 work end to end through the real Symfony kernel.

- [ ] **Step 1: Add the Moco fixture**

In `tests/resources/moco/Back/moco.json`, add an entry for the status endpoint (anywhere among the other `/istration/...` entries):

```json
  {
    "request": {
      "uri": {
        "match": "/istration/action/trigger/update_images/status"
      },
      "method": "get",
      "headers": {
        "X-Provider": {
          "match": ".*"
        },
        "authorization": {
          "match": "Bearer .*"
        }
      }
    },
    "response": {
      "status": "200",
      "json": {
        "correlationId": "corr-1",
        "workflowA": {"state": "done", "url": "https://github.com/douzeEnsemble/pokenini-icon/actions/runs/1"},
        "iconPr": {"state": "merged", "url": "https://github.com/douzeEnsemble/pokenini-icon/pull/2"},
        "workflowB": {"state": "done", "url": "https://github.com/douzeEnsemble/pokenini-icon/actions/runs/3"},
        "resourcesPr": {"state": "open", "url": "https://github.com/douzeEnsemble/pokenini-resources/pull/4"}
      }
    }
  },
```

- [ ] **Step 2: Write the integration test**

Create `tests/src/Integration/Controller/Admin/PipelineStatusTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Admin;

use App\Controller\AdminController;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(AdminController::class)]
#[Group('api-mocked-testing')]
final class PipelineStatusTest extends WebTestCase
{
    public function testPipelineStatusRenders(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/istration/actions');

        $this->assertResponseIsSuccessful();

        $this->assertStringContainsString('Mergée', $crawler->outerHtml());

        $refreshLink = $crawler->filter('.admin-pipeline-status')->siblings()->filter('a.btn-outline-info')->first();
        $this->assertStringContainsString('refresh=1', (string) $refreshLink->attr('href'));
    }
}
```

- [ ] **Step 3: Run the test**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Admin/PipelineStatusTest.php`
Expected: `OK (1 test, ...)`. If the `.admin-pipeline-status` selector or the refresh-link selector doesn't match what Task 3 actually rendered (Twig/CSS selectors are easy to get slightly wrong on the first pass), adjust the test's selectors to match the real markup — the important behavioral assertions are "the page renders successfully with the mocked status visible" and "a refresh link exists containing `refresh=1`", not the exact CSS selector used to find them.

- [ ] **Step 4: Commit**

```bash
git add tests/resources/moco/Back/moco.json tests/src/Integration/Controller/Admin/PipelineStatusTest.php
git commit -m "Add integration test for the image pipeline status section"
```

---

### Task 5: Full quality and measures gate

**Files:** none (verification only).

- [ ] **Step 1: Run the full test suite**

Run: `make tests`
Expected: all tests pass. Pre-existing browser-suite flakiness (documented in the trigger-button feature) is expected and unrelated.

- [ ] **Step 2: Run quality checks**

Run: `make quality`
Expected: clean. Fix formatting with `make phpcsfixer-fix` if needed.

- [ ] **Step 3: Run coverage and mutation testing**

Run: `make measures`
Expected: 100% coverage, 100% MSI. Likely spots for a surviving mutant: `_pipeline_status.html.twig`'s `stageColor` map fallback (`|default('light')`) and the `{% if item.stage.url is not empty %}` check — these render paths are only exercised by Task 4's single fixture (all 4 stages non-idle with URLs); if a mutant survives there, it's likely because no test exercises an `idle` stage with a `null` url — add a second Moco fixture entry / test case for that combination if so.

- [ ] **Step 4: Commit any fixes**

```bash
git add -A
git commit -m "Fix quality/coverage findings for the image pipeline status UI"
```

(Skip if nothing needed fixing.)
