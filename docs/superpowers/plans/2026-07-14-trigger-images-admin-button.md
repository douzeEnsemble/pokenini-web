# Trigger Images Admin Button Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a button on `/istration/actions` that calls `pokenini-back`'s new `update_images` trigger endpoint, following the existing update/calculate/invalidate button pattern.

**Architecture:** A 4th verb (`trigger`) alongside the existing `update`/`calculate`/`invalidate` methods on `AdminActionController`, reusing the already-generic `AdminActionService::execute()` (no change needed there) and the existing `admin.action(...)` Twig macro (no change needed there either — it already degrades gracefully when `actionLogsData` has no matching entry, which is always true for this action since `pokenini-api` never logs it).

**Tech Stack:** Symfony 8.0 / PHP ≥ 8.5, Twig, PHPUnit, Moco (HTTP mock server) for integration tests.

## Global Constraints

- `declare(strict_types=1)` in every new/modified PHP file (workspace-wide convention). `AdminActionController` is already `final`.
- 100% line coverage and 100% Mutation Score Index are required (`make measures`); `make quality` must be green.
- The response means "GitHub accepted the dispatch", not "images are updated" — any user-facing copy must not imply completion (see companion spec, "Fire-and-forget, deliberately"). This plan does not add custom flash-message copy beyond what the existing macro already renders (the existing `admin.actions.*.cta` / `.title` translation strings are short button labels, not status copy — see Task 2).
- Companion spec: `docs/superpowers/specs/2026-07-14-update-images-pipeline-trigger-design.md` (this repo).
- Companion plan this depends on for the endpoint contract (must exist first — this task only needs its documented contract, not the running code, to write and test against): `../pokenini-back/docs/superpowers/plans/2026-07-14-trigger-images-pipeline-endpoint.md` — route `POST /istration/action/trigger/{name}` (condition `name in ['update_images']`), response `{"action":"trigger","item":"update_images","state":"ok"|"ko","content":"","error":"..."}`, status `202`/`500`.

---

### Task 1: `AdminActionController::trigger()` — the new admin action verb

**Files:**
- Modify: `src/Controller/AdminActionController.php`
- Modify: `tests/src/Unit/Controller/AdminActionControllerTest.php`

**Interfaces:**
- Consumes: `AdminActionService::execute(string $action, string $item, string $method): AdminAction` (existing, unchanged).
- Produces: `AdminActionController::trigger(string $name, Request $request): RedirectResponse`, route `POST /{_locale}/istration/action/trigger/{name}` (condition `name in ['update_images']`), CSRF token id `admin_trigger`.

- [ ] **Step 1: Write the failing tests**

Add these three test methods to `tests/src/Unit/Controller/AdminActionControllerTest.php`, right after `testInvalidateReportsRedirectsToReportsPage` and before the `private function assertFailActionLogs` method:

```php
    public function testTriggerAction(): void
    {
        $adminActionService = $this->createMock(AdminActionService::class);
        $adminActionService
            ->expects($this->once())
            ->method('execute')
            ->with('trigger', 'update_images')
            ->willReturn(new AdminAction('trigger', 'update_images', 'ok', '', ''))
        ;

        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($this->once())
            ->method('set')
        ;

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->once())
            ->method('getSession')
            ->willReturn($session)
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->never())
            ->method('critical')
        ;

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(AdminActionSucceededEvent::class))
        ;

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->with('app_admin_actions', ['_fragment' => 'trigger_update_images'])
            ->willReturn('/admin')
        ;

        $csrfManager = $this->createStub(CsrfTokenManagerInterface::class);
        $csrfManager->method('isTokenValid')->willReturn(true);

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('has')
            ->with('security.csrf.token_manager')
            ->willReturn(true)
        ;
        $container
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['security.csrf.token_manager', $csrfManager],
                ['router', $router],
            ])
        ;

        $controller = new AdminActionController(
            $adminActionService,
            $requestStack,
            $logger,
            $eventDispatcher,
        );
        $controller->setContainer($container);

        $response = $controller->trigger('update_images', new Request([], ['_token' => 'valid_token']));

        $this->assertSame('/admin', $response->getTargetUrl());
    }

    public function testFailTriggerLogs(): void
    {
        $controller = $this->assertFailActionLogs('trigger');

        $controller->trigger('update_images', new Request([], ['_token' => 'valid_token']));
    }

    public function testTriggerInvalidCsrfToken(): void
    {
        $csrfManager = $this->createMock(CsrfTokenManagerInterface::class);
        $csrfManager
            ->expects($this->once())
            ->method('isTokenValid')
            ->willReturn(false)
        ;

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('has')
            ->with('security.csrf.token_manager')
            ->willReturn(true)
        ;
        $container
            ->expects($this->once())
            ->method('get')
            ->willReturnMap([['security.csrf.token_manager', $csrfManager]])
        ;

        $adminActionService = $this->createMock(AdminActionService::class);
        $adminActionService
            ->expects($this->never())
            ->method('execute')
        ;

        $controller = new AdminActionController(
            $adminActionService,
            $this->createStub(RequestStack::class),
            $this->createStub(LoggerInterface::class),
            $this->createStub(EventDispatcherInterface::class),
        );
        $controller->setContainer($container);

        $this->expectException(AccessDeniedException::class);
        $controller->trigger('update_images', new Request([], ['_token' => 'bad_token']));
    }
```

- [ ] **Step 2: Run the tests and confirm they fail**

Run: `docker compose exec php php vendor/bin/phpunit --filter 'testTriggerAction|testFailTriggerLogs|testTriggerInvalidCsrfToken' tests/src/Unit/Controller/AdminActionControllerTest.php`
Expected: FAIL — `Call to undefined method App\Controller\AdminActionController::trigger()`.

- [ ] **Step 3: Add the `trigger()` method**

In `src/Controller/AdminActionController.php`, add this method right after `invalidate()` and before the `private function execute(...)` method:

```php
    #[Route(
        '/trigger/{name}',
        methods: ['POST'],
        condition: "params['name']
            in [
                'update_images',
            ]"
    )]
    public function trigger(
        string $name,
        Request $request,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('admin_trigger', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        return $this->execute($name, 'trigger', 'POST');
    }
```

No new `use` imports are needed — `Route`, `Request`, and `RedirectResponse` are already imported for the other three methods.

- [ ] **Step 4: Run the tests and confirm they pass**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Controller/AdminActionControllerTest.php`
Expected: `OK (11 tests, ...)` (8 existing + 3 new).

- [ ] **Step 5: Commit**

```bash
git add src/Controller/AdminActionController.php tests/src/Unit/Controller/AdminActionControllerTest.php
git commit -m "Add trigger() admin action verb for the update_images button"
```

---

### Task 2: Template and translations — render the button

**Files:**
- Modify: `templates/Admin/_actions.html.twig`
- Modify: `translations/messages+intl-icu.en.yaml`
- Modify: `translations/messages+intl-icu.fr.yaml`

**Interfaces:**
- Consumes: the existing `admin.action(action, actionPrefix, item, icon, updatedItem, updatedAction, updatedState, actionLogsData)` macro from `Admin/_macros.html.twig` (no changes to the macro itself — see Global Constraints / companion spec).

- [ ] **Step 1: Add the new section to `_actions.html.twig`**

Append this block at the end of the file (after the existing "invalidate_data" `<div class="row px-4 py-5">...</div>` block):

```twig

<div class="row px-4 py-5">
  <h2 class="mb-3 pb-2 border-bottom">{{ 'admin.actions.trigger_pipeline.title'|trans }}</h2>
  {% set triggerPipelineItems = {
    'update_images': 'images',
  } %}
  {% for item, icon in triggerPipelineItems %}
    {{ admin.action('trigger_pipeline', 'trigger', item, icon, updatedItem, updatedAction, updatedState, actionLogsData) }}
  {% endfor %}
</div>
```

- [ ] **Step 2: Add English translation keys**

In `translations/messages+intl-icu.en.yaml`, find the `invalidate_data:` block under `admin: actions:` (it ends with the `actions:` sub-key's `cta: "Invalidate!"` line, right before the `reports_data:` key). Insert this new block immediately after it, at the same 4-space indent as `update_data:`/`calculate_data:`/`invalidate_data:`:

```yaml
    trigger_pipeline:
      title: "Trigger pipelines"
      update_images:
        title: "Update Pokémon images"
        cta: "Trigger!"
```

- [ ] **Step 3: Add French translation keys**

In `translations/messages+intl-icu.fr.yaml`, in the equivalent spot (right after the `invalidate_data:` block's `actions:` sub-key `cta: "Invalider"` line, right before `reports_data:`):

```yaml
    trigger_pipeline:
      title: "Déclenchement de pipelines"
      update_images:
        title: "Mettre à jour les images des Pokémon"
        cta: "Déclencher"
```

- [ ] **Step 4: Validate the YAML and Twig**

Run: `make jsonlint` (also lints YAML in this project's Makefile target) — if that target only covers JSON, instead run:
```bash
docker compose exec php php -r "Symfony\Component\Yaml\Yaml::parseFile('translations/messages+intl-icu.en.yaml'); Symfony\Component\Yaml\Yaml::parseFile('translations/messages+intl-icu.fr.yaml'); echo 'VALID';"
```
Expected: `VALID` printed, no exception.

- [ ] **Step 5: Manually verify the button renders**

Run: `make start` (if not already running), then visit `http://localhost/fr/connect/f/c?t=admin` followed by `http://localhost/fr/istration/actions`.
Expected: a new "Déclenchement de pipelines" section appears with one button, "Mettre à jour les images des Pokémon" / "Déclencher".

- [ ] **Step 6: Commit**

```bash
git add templates/Admin/_actions.html.twig translations/messages+intl-icu.en.yaml translations/messages+intl-icu.fr.yaml
git commit -m "Render the update_images trigger button on the admin actions page"
```

---

### Task 3: Integration test against a Moco fixture

**Files:**
- Modify: `tests/resources/moco/Back/moco.json`
- Create: `tests/src/Integration/Controller/Admin/ActionTriggerTest.php`

**Interfaces:**
- Consumes: `GetUserToken::getFakeUserToken()`, `TestNavTrait` (existing test utilities, same as `ActionUpdateTest.php`).

- [ ] **Step 1: Add the Moco fixture entry**

In `tests/resources/moco/Back/moco.json`, insert this entry right after the `/istration/action/invalidate/reports` entry and before the `/istration/reports` entry (matching the existing entries' exact shape):

```json
  {
    "request": {
      "uri": {
        "match": "/istration/action/trigger/update_images"
      },
      "method": "post",
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
      "status": "202",
      "json": {
        "action": "trigger",
        "item": "update_images",
        "state": "ok",
        "content": "",
        "error": ""
      }
    }
  },
```

- [ ] **Step 2: Write the failing integration test**

Create `tests/src/Integration/Controller/Admin/ActionTriggerTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Admin;

use App\Controller\AdminActionController;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(AdminActionController::class)]
#[Group('api-mocked-testing')]
final class ActionTriggerTest extends WebTestCase
{
    use TestNavTrait;

    public function testAdminTriggerUpdateImages(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/istration/actions');
        $form = $crawler->filter('#trigger_update_images form')->form();
        $client->submit($form);

        $this->assertResponseStatusCodeSame(302);
        $crawler = $client->followRedirect();
        $this->assertSame('http://localhost/fr/istration/actions', $client->getRequest()->getUri());

        $this->assertCountFilter($crawler, 1, '.icon-square.bg-success');

        $this->assertConnectedNavBar($crawler);
        $this->assertFrenchLangSwitch($crawler);
    }
}
```

- [ ] **Step 3: Run the test and confirm it fails**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Admin/ActionTriggerTest.php`
Expected: FAIL — either a `Crawler` filter finding no `#trigger_update_images form` element (if Task 1/2 weren't done yet) or a Moco 404/mismatch if the fixture doesn't match yet. Given Tasks 1–2 in this plan are already done by this point, this should actually fail only if Step 1 (the Moco fixture) is missing or malformed — restore step ordering if you're executing tasks out of order.

- [ ] **Step 4: Run the test and confirm it passes**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Admin/ActionTriggerTest.php`
Expected: `OK (1 test, ...)`

- [ ] **Step 5: Commit**

```bash
git add tests/resources/moco/Back/moco.json tests/src/Integration/Controller/Admin/ActionTriggerTest.php
git commit -m "Add integration test for the update_images trigger button"
```

---

### Task 4: Full quality and measures gate

**Files:** none (verification only).

- [ ] **Step 1: Run the full test suite**

Run: `make tests`
Expected: all unit, integration, and browser tests pass, including the new tests from Tasks 1 and 3.

- [ ] **Step 2: Run quality checks**

Run: `make quality`
Expected: editorconfig, jsonlint, PHP CS Fixer, PHPMD, Psalm, PHPStan, Deptrac, and w3c all pass. If PHP CS Fixer reports formatting differences, run `make phpcsfixer-fix` and re-run `make quality`.

- [ ] **Step 3: Run coverage and mutation testing**

Run: `make measures`
Expected: coverage 100%, mutation score (Infection) 100% MSI. If a mutant survives (e.g. the CSRF token id `'admin_trigger'`, the route condition list, or the `'trigger'`/`'update_images'` literals), add an assertion in the relevant Task 1/3 test that pins down that exact value, then re-run.

- [ ] **Step 4: Commit any fixes from Steps 2–3**

```bash
git add -A
git commit -m "Fix quality/coverage findings for the update_images trigger button"
```

(Skip this step if Steps 2–3 already passed cleanly with nothing to fix.)
