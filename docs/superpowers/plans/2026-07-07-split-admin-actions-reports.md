# Split Admin Actions/Reports Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Serve the Admin "Actions" and "Reports" sections from two separate routes (`/istration/actions`, `/istration/reports`) instead of one combined `/istration` page, so each page only loads the data it needs.

**Architecture:** `AdminController` keeps the `/istration` prefix but becomes the Actions-page controller (plus a thin redirect from the bare `/istration` path); a new `AdminReportsController` owns the Reports page. `AdminActionController`'s POST handlers redirect to whichever page owns the button that triggered them. Both pages share a small `_tabs.html.twig` partial for cross-navigation; the existing `_actions.html.twig` / `_reports.html.twig` partials are reused unchanged.

**Tech Stack:** Symfony 8 / PHP ≥ 8.5, Twig, PHPUnit (unit + integration/WebTestCase + Panther browser tests), Moco HTTP mocks.

## Global Constraints

- `declare(strict_types=1)` at the top of every PHP file.
- Controller and test classes are `final`; test classes carry `/** @internal */` and `#[CoversClass(TargetClass::class)]`.
- No PHP/Composer/PHPUnit on the host — every command in this plan's "Run" steps executes inside the `php` container, e.g. `docker compose exec php php vendor/bin/phpunit ...` (see project `CLAUDE.md`, section "Tests").
- Integration tests use Moco fixtures, never HTTP-client mocks — this plan does not touch Moco fixtures because no Back API request shape changes.
- Controllers must not reach into `Service\Api`/`Service\Back` internals beyond calling the injected service — Deptrac (`AppController` → `AppService`) already allows `Controller` → `Service\Back\*`, unchanged by this plan.
- 100% line coverage and 100% Mutation Score Index are required project-wide (`make measures`) — every new branch (the `'reports' === $name` conditional, the `index()` redirect) needs a dedicated test case, not just incidental coverage.

---

## File Structure

| File | Change |
|---|---|
| `src/Controller/AdminController.php` | Rewrite: `index()` becomes a redirect to `app_admin_actions`; new `actions()` method (formerly `index()`) renders the Actions page, drops `GetReportsService`. |
| `src/Controller/AdminReportsController.php` | **New.** Renders the Reports page (`GetReportsService` only), mirrors `AdminController::actions()`'s session-flash handling for the `invalidate_reports` action banner. |
| `src/Controller/AdminActionController.php` | `execute()`'s redirect target becomes conditional: `app_admin_reports` when `$name === 'reports'`, `app_admin_actions` otherwise. |
| `templates/Admin/_tabs.html.twig` | **New.** Shared Bootstrap tab bar linking `app_admin_actions` / `app_admin_reports`. |
| `templates/Admin/actions.html.twig` | **New.** Page shell for the Actions page (replaces `Admin/index.html.twig` as the render target of `AdminController::actions()`). |
| `templates/Admin/reports.html.twig` | **New.** Page shell for the Reports page. |
| `templates/Admin/index.html.twig` | **Deleted** — superseded by the two page shells above. |
| `templates/_nav.html.twig` | Admin nav-bar link points at `app_admin_actions`; its "active" check matches either `app_admin_actions` or `app_admin_reports`. |
| `tests/src/Unit/Controller/AdminControllerTest.php` | Rewrite for the new constructor/method signature; add a redirect test. |
| `tests/src/Unit/Controller/AdminReportsControllerTest.php` | **New**, mirrors the rewritten `AdminControllerTest`. |
| `tests/src/Unit/Controller/AdminActionControllerTest.php` | Update expected redirect route names; add a `reports`-specific case. |
| `tests/src/Integration/Controller/Admin/AdminPageTest.php` | Repoint to `/fr/istration/actions`; drop report-only assertions; update element counts; add a redirect test. |
| `tests/src/Integration/Controller/Admin/AdminReportsTest.php` | Repoint to `/fr/istration/reports`; fix its pre-existing wrong `CoversClass`. |
| `tests/src/Integration/Controller/Admin/ActionInvalidateTest.php` | GET the page that owns the submitted button (`reports` → Reports page, everything else → Actions page). |
| `tests/src/Integration/Controller/Admin/ActionUpdateTest.php` | GET/assert against `/fr/istration/actions` instead of `/fr/istration`. |
| `tests/src/Integration/Controller/Admin/ActionCalculateTest.php` | GET/assert against `/fr/istration/actions` instead of `/fr/istration`. |
| `tests/src/Browser/Admin/RedirectActionsTest.php` | Request `/fr/istration/actions`; expect the post-submit fragment URL on `/fr/istration/actions`. |
| `tests/src/Browser/Admin/ToggleActionsTest.php` | Request `/fr/istration/actions` directly. |

---

### Task 1: Split the Admin page into Actions and Reports routes/controllers/templates

**Files:**
- Create: `src/Controller/AdminReportsController.php`
- Modify: `src/Controller/AdminController.php` (full rewrite)
- Create: `templates/Admin/_tabs.html.twig`
- Create: `templates/Admin/actions.html.twig`
- Create: `templates/Admin/reports.html.twig`
- Delete: `templates/Admin/index.html.twig`
- Modify: `templates/_nav.html.twig:73-84`
- Create: `tests/src/Unit/Controller/AdminReportsControllerTest.php`
- Modify: `tests/src/Unit/Controller/AdminControllerTest.php` (full rewrite)
- Modify: `tests/src/Integration/Controller/Admin/AdminPageTest.php` (full rewrite)
- Modify: `tests/src/Integration/Controller/Admin/AdminReportsTest.php`

**Interfaces:**
- Produces: route `app_admin_index` (`GET /{_locale}/istration`, redirects to `app_admin_actions`), route `app_admin_actions` (`GET /{_locale}/istration/actions`, renders `Admin/actions.html.twig`), route `app_admin_reports` (`GET /{_locale}/istration/reports`, renders `Admin/reports.html.twig`). `AdminActionController::SESSION_ACTION_DATA` (existing constant) is read by both `AdminController::actions()` and the new `AdminReportsController::reports()`.
- Consumes: `App\Service\Back\GetActionLogsService::get(): array<int, ActionLogData>` (existing), `App\Service\Back\GetReportsService::get(): array<string, list<array<string,mixed>>>` (existing), `App\DTO\AdminAction` (existing, unchanged).

- [ ] **Step 1: Write the failing unit test for `AdminReportsController`**

Create `tests/src/Unit/Controller/AdminReportsControllerTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\AdminReportsController;
use App\DTO\AdminAction;
use App\Service\Back\GetReportsService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Twig\Environment;

/**
 * @internal
 */
#[CoversClass(AdminReportsController::class)]
final class AdminReportsControllerTest extends TestCase
{
    public function testReports(): void
    {
        $adminAction = new AdminAction(
            'truc',
            'machin',
            'ok',
            'content',
            '',
        );

        $flashBag = new FlashBag();

        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($this->once())
            ->method('get')
            ->with('admin.action.response.content')
            ->willReturn($adminAction)
        ;
        $session
            ->expects($this->once())
            ->method('remove')
            ->with('admin.action.response.content')
        ;

        $flashBagSession = $this->createMock(FlashBagAwareSessionInterface::class);
        $flashBagSession
            ->expects($this->exactly(3))
            ->method('getFlashBag')
            ->willReturn($flashBag)
        ;

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->exactly(4))
            ->method('getSession')
            ->willReturnOnConsecutiveCalls(
                $session,
                $flashBagSession,
                $flashBagSession,
                $flashBagSession,
            )
        ;

        $twig = $this->createMock(Environment::class);
        $twig
            ->expects($this->once())
            ->method('render')
            ->with(
                'Admin/reports.html.twig',
                [
                    'reportsData' => [],
                ]
            )
            ->willReturn('<html></html>')
        ;

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->exactly(4))
            ->method('get')
            ->willReturnOnConsecutiveCalls(
                $requestStack,
                $requestStack,
                $requestStack,
                $twig,
            )
        ;
        $container
            ->expects($this->once())
            ->method('has')
            ->with('twig')
            ->willReturn(true)
        ;

        $controller = $this->getController();
        $controller->setContainer($container);

        $controller->reports($requestStack);

        $this->assertEquals(
            [
                'action' => ['truc'],
                'item' => ['machin'],
                'state' => ['ok'],
            ],
            $flashBag->all()
        );
    }

    public function testReportsActionError(): void
    {
        $adminAction = new AdminAction(
            'truc',
            'machin',
            'ok',
            'content',
            'error',
        );

        $flashBag = new FlashBag();

        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($this->once())
            ->method('get')
            ->with('admin.action.response.content')
            ->willReturn($adminAction)
        ;
        $session
            ->expects($this->once())
            ->method('remove')
            ->with('admin.action.response.content')
        ;

        $flashBagSession = $this->createMock(FlashBagAwareSessionInterface::class);
        $flashBagSession
            ->expects($this->exactly(4))
            ->method('getFlashBag')
            ->willReturn($flashBag)
        ;

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->exactly(5))
            ->method('getSession')
            ->willReturnOnConsecutiveCalls(
                $session,
                $flashBagSession,
                $flashBagSession,
                $flashBagSession,
                $flashBagSession,
            )
        ;

        $twig = $this->createMock(Environment::class);
        $twig
            ->expects($this->once())
            ->method('render')
            ->with(
                'Admin/reports.html.twig',
                [
                    'reportsData' => [],
                ]
            )
            ->willReturn('<html></html>')
        ;

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->exactly(5))
            ->method('get')
            ->willReturnOnConsecutiveCalls(
                $requestStack,
                $requestStack,
                $requestStack,
                $requestStack,
                $twig,
            )
        ;
        $container
            ->expects($this->once())
            ->method('has')
            ->with('twig')
            ->willReturn(true)
        ;

        $controller = $this->getController();
        $controller->setContainer($container);

        $controller->reports($requestStack);

        $this->assertEquals(
            [
                'action' => ['truc'],
                'item' => ['machin'],
                'state' => ['ok'],
                'error' => ['error'],
            ],
            $flashBag->all()
        );
    }

    private function getController(): AdminReportsController
    {
        $getReportsService = $this->createMock(GetReportsService::class);
        $getReportsService
            ->expects($this->once())
            ->method('get')
            ->willReturn([])
        ;

        return new AdminReportsController($getReportsService);
    }
}
```

- [ ] **Step 2: Run it to confirm it fails**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Controller/AdminReportsControllerTest.php`
Expected: FAIL — `Class "App\Controller\AdminReportsController" not found`.

- [ ] **Step 3: Implement `AdminReportsController`**

Create `src/Controller/AdminReportsController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\AdminAction;
use App\Service\Back\GetReportsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/istration')]
final class AdminReportsController extends AbstractController
{
    public function __construct(
        private readonly GetReportsService $getReportsService,
    ) {}

    #[Route('/reports', methods: ['GET'], name: 'app_admin_reports')]
    public function reports(RequestStack $requestStack): Response
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

        $reportsData = $this->getReportsService->get();

        return $this->render(
            'Admin/reports.html.twig',
            [
                'reportsData' => $reportsData,
            ]
        );
    }
}
```

- [ ] **Step 4: Run the test to confirm it passes**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Controller/AdminReportsControllerTest.php`
Expected: PASS (2 tests).

- [ ] **Step 5: Write the failing unit test for the rewritten `AdminController`**

Replace `tests/src/Unit/Controller/AdminControllerTest.php` entirely with:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\AdminController;
use App\DTO\AdminAction;
use App\Service\Back\GetActionLogsService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

/**
 * @internal
 */
#[CoversClass(AdminController::class)]
final class AdminControllerTest extends TestCase
{
    public function testIndexRedirectsToActions(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->with('app_admin_actions', [])
            ->willReturn('/fr/istration/actions')
        ;

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('get')
            ->with('router')
            ->willReturn($router)
        ;

        $controller = new AdminController($this->createStub(GetActionLogsService::class));
        $controller->setContainer($container);

        $response = $controller->index();

        $this->assertSame('/fr/istration/actions', $response->getTargetUrl());
        $this->assertSame(302, $response->getStatusCode());
    }

    public function testAdminAction(): void
    {
        $adminAction = new AdminAction(
            'truc',
            'machin',
            'ok',
            'content',
            '',
        );

        $flashBag = new FlashBag();

        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($this->once())
            ->method('get')
            ->with('admin.action.response.content')
            ->willReturn($adminAction)
        ;
        $session
            ->expects($this->once())
            ->method('remove')
            ->with('admin.action.response.content')
        ;

        $flashBagSession = $this->createMock(FlashBagAwareSessionInterface::class);
        $flashBagSession
            ->expects($this->exactly(3))
            ->method('getFlashBag')
            ->willReturn($flashBag)
        ;

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->exactly(4))
            ->method('getSession')
            ->willReturnOnConsecutiveCalls(
                $session,
                $flashBagSession,
                $flashBagSession,
                $flashBagSession,
            )
        ;

        $twig = $this->createMock(Environment::class);
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

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->exactly(4))
            ->method('get')
            ->willReturnOnConsecutiveCalls(
                $requestStack,
                $requestStack,
                $requestStack,
                $twig,
            )
        ;
        $container
            ->expects($this->once())
            ->method('has')
            ->with('twig')
            ->willReturn(true)
        ;

        $controller = $this->getController();
        $controller->setContainer($container);

        $controller->actions($requestStack);

        $this->assertEquals(
            [
                'action' => ['truc'],
                'item' => ['machin'],
                'state' => ['ok'],
            ],
            $flashBag->all()
        );
    }

    public function testAdminActionError(): void
    {
        $adminAction = new AdminAction(
            'truc',
            'machin',
            'ok',
            'content',
            'error',
        );

        $flashBag = new FlashBag();

        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($this->once())
            ->method('get')
            ->with('admin.action.response.content')
            ->willReturn($adminAction)
        ;
        $session
            ->expects($this->once())
            ->method('remove')
            ->with('admin.action.response.content')
        ;

        $flashBagSession = $this->createMock(FlashBagAwareSessionInterface::class);
        $flashBagSession
            ->expects($this->exactly(4))
            ->method('getFlashBag')
            ->willReturn($flashBag)
        ;

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->exactly(5))
            ->method('getSession')
            ->willReturnOnConsecutiveCalls(
                $session,
                $flashBagSession,
                $flashBagSession,
                $flashBagSession,
                $flashBagSession,
            )
        ;

        $twig = $this->createMock(Environment::class);
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

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->exactly(5))
            ->method('get')
            ->willReturnOnConsecutiveCalls(
                $requestStack,
                $requestStack,
                $requestStack,
                $requestStack,
                $twig,
            )
        ;
        $container
            ->expects($this->once())
            ->method('has')
            ->with('twig')
            ->willReturn(true)
        ;

        $controller = $this->getController();
        $controller->setContainer($container);

        $controller->actions($requestStack);

        $this->assertEquals(
            [
                'action' => ['truc'],
                'item' => ['machin'],
                'state' => ['ok'],
                'error' => ['error'],
            ],
            $flashBag->all()
        );
    }

    private function getController(): AdminController
    {
        $getActionLogsService = $this->createMock(GetActionLogsService::class);
        $getActionLogsService
            ->expects($this->once())
            ->method('get')
            ->willReturn([])
        ;

        return new AdminController($getActionLogsService);
    }
}
```

- [ ] **Step 6: Run it to confirm it fails**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Controller/AdminControllerTest.php`
Expected: FAIL — `AdminController::__construct()` still requires a `GetReportsService` argument, and `index()`/`actions()` don't have the new shapes.

- [ ] **Step 7: Rewrite `AdminController`**

Replace `src/Controller/AdminController.php` entirely with:

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\AdminAction;
use App\Service\Back\GetActionLogsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/istration')]
final class AdminController extends AbstractController
{
    public function __construct(
        private readonly GetActionLogsService $getActionLogsService,
    ) {}

    #[Route('', methods: ['GET'], name: 'app_admin_index')]
    public function index(): RedirectResponse
    {
        return $this->redirectToRoute('app_admin_actions');
    }

    #[Route('/actions', methods: ['GET'], name: 'app_admin_actions')]
    public function actions(RequestStack $requestStack): Response
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

        return $this->render(
            'Admin/actions.html.twig',
            [
                'actionLogsData' => $actionLogsData,
            ]
        );
    }
}
```

- [ ] **Step 8: Run both controller unit tests to confirm they pass**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Controller/AdminControllerTest.php tests/src/Unit/Controller/AdminReportsControllerTest.php`
Expected: PASS (5 tests total).

- [ ] **Step 9: Create the templates**

Create `templates/Admin/_tabs.html.twig`:

```twig
{% set adminTabs = {
  'actions': {
    'route': 'app_admin_actions',
    'label': 'title.admin_actions',
  },
  'reports': {
    'route': 'app_admin_reports',
    'label': 'title.admin_reports',
  },
} %}

<ul class="nav nav-tabs mb-4">
  {% for key, tab in adminTabs %}
    <li class="nav-item">
      <a
        class="nav-link{{ key == active ? ' active' : '' }}"
        href="{{ path(tab.route) }}"
      >
        {{ tab.label|trans }}
      </a>
    </li>
  {% endfor %}
</ul>
```

Create `templates/Admin/actions.html.twig`:

```twig
{% set locale = app.request.locale %}

{% import "Admin/_macros.html.twig" as admin %}

{% set items = app.flashes('item') %}
{% set actions = app.flashes('action') %}
{% set states = app.flashes('state') %}

{% set updatedItem = items[0] is defined ? items[0] : null %}
{% set updatedAction = actions[0] is defined ? actions[0] : null %}
{% set updatedState = states[0] is defined ? states[0] : null %}

{% extends 'base.html.twig' %}
{% use '_nav.html.twig' %}

{% block title %}Pokénini {{ 'title.admin'|trans }}{% endblock %}

{% block stylesheets %}
  {{ parent() }}

  <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
{% endblock stylesheets %}

{% block container %}
<div id="admin" class="row">
  <div class="col-12">
    <h1 class="text-center">{{ 'title.admin'|trans }}</h1>
    {% include 'Admin/_tabs.html.twig' with {'active': 'actions'} %}
    {% include 'Admin/_actions.html.twig' %}
  </div>
</div>
{% endblock %}

{% block head_javascripts %}
  {{ parent() }}
  <script src="{{ asset('js/admin.js') }}"></script>
{% endblock head_javascripts %}

{% block foot_javascript %}
  {{ parent() }}

  <script>
    (function () {
      watchActionLogToggles();
    })();
  </script>
{% endblock %}
```

Create `templates/Admin/reports.html.twig`:

```twig
{% set locale = app.request.locale %}

{% import "Admin/_macros.html.twig" as admin %}

{% set items = app.flashes('item') %}
{% set actions = app.flashes('action') %}
{% set states = app.flashes('state') %}

{% set updatedItem = items[0] is defined ? items[0] : null %}
{% set updatedAction = actions[0] is defined ? actions[0] : null %}
{% set updatedState = states[0] is defined ? states[0] : null %}

{% extends 'base.html.twig' %}
{% use '_nav.html.twig' %}

{% block title %}Pokénini {{ 'title.admin'|trans }}{% endblock %}

{% block stylesheets %}
  {{ parent() }}

  <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
{% endblock stylesheets %}

{% block container %}
<div id="admin" class="row">
  <div class="col-12">
    <h1 class="text-center">{{ 'title.admin'|trans }}</h1>
    {% include 'Admin/_tabs.html.twig' with {'active': 'reports'} %}
    {% include 'Admin/_reports.html.twig' %}
  </div>
</div>
{% endblock %}

{% block foot_javascript %}
  {{ parent() }}

  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.1.2/dist/chart.umd.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/patternomaly@1.3.2/dist/patternomaly.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js" integrity="sha256-IMCPPZxtLvdt9tam8RJ8ABMzn+Mq3SQiInbDmMYwjDg=" crossorigin="anonymous"></script>

  {% include 'Admin/_reports_scripts.html.twig' %}
{% endblock %}
```

Delete `templates/Admin/index.html.twig` — it's no longer rendered by any controller.

- [ ] **Step 10: Update the nav-bar admin link**

In `templates/_nav.html.twig`, replace:

```twig
                {% if is_granted("ROLE_ADMIN") %}
                <li class="nav-item admin-link">
                    <a
                        class="nav-link {{ 'app_admin_index' == currentRoute ? ' active' : '' }}"
                        aria-current="page"
                        href="{{ path('app_admin_index') }}"
                    >
                        <i class="bi bi-wrench-adjustable-circle"></i>
                        {{ 'nav.admin'|trans }}
                    </a>
                </li>
                {% endif %}
```

with:

```twig
                {% if is_granted("ROLE_ADMIN") %}
                <li class="nav-item admin-link">
                    <a
                        class="nav-link {{ currentRoute in ['app_admin_actions', 'app_admin_reports'] ? ' active' : '' }}"
                        aria-current="page"
                        href="{{ path('app_admin_actions') }}"
                    >
                        <i class="bi bi-wrench-adjustable-circle"></i>
                        {{ 'nav.admin'|trans }}
                    </a>
                </li>
                {% endif %}
```

- [ ] **Step 11: Rewrite the Actions-page integration test**

Replace `tests/src/Integration/Controller/Admin/AdminPageTest.php` entirely with:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Admin;

use App\Controller\AdminController;
use App\Tests\Common\Traits\TestNavTrait;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @internal
 */
#[CoversClass(AdminController::class)]
#[Group('api-mocked-testing')]
final class AdminPageTest extends WebTestCase
{
    use TestNavTrait;

    public function testAdminHomeNotConnected(): void
    {
        $client = self::createClient();

        $client->request('GET', '/fr/istration');

        $this->assertResponseStatusCodeSame(307);
    }

    public function testAdminHomeBadCredentials(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('34654656489621361987', 'TestProvider');

        $client->loginUser($user, 'web');

        $client->request('GET', '/fr/istration');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testAdminHomeNotAllowed(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $client->loginUser($user, 'web');

        $client->request('GET', '/fr/istration');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testAdminIndexRedirectsToActions(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $client->request('GET', '/fr/istration');

        $this->assertResponseStatusCodeSame(302);
        $this->assertResponseRedirects('/fr/istration/actions');
    }

    public function testAdminHomeConnected(): void
    {
        $this->getAdminHomeConnected();
    }

    public function testAdminHome(): void
    {
        $crawler = $this->getAdminHomeConnected();

        $this->assertCountFilter($crawler, 5, 'h2');
        $this->assertCountFilter($crawler, 14, 'h3');
        $this->assertCountFilter($crawler, 14, '.admin-item button.admin-item-cta');

        $this->assertCountFilter($crawler, 7, '.admin-item-update button.admin-item-cta');
        $this->assertCountFilter($crawler, 4, '.admin-item-calculate button.admin-item-cta');
        $this->assertCountFilter($crawler, 3, '.admin-item-invalidate button.admin-item-cta');

        $this->assertCountFilter($crawler, 3, '.admin-item-cta.disabled');
        $this->assertCountFilter($crawler, 1, '#update_games_collections_and_dex .admin-item-cta.disabled');
        $this->assertCountFilter($crawler, 1, '#calculate_game_bundles_availabilities .admin-item-cta.disabled');
        $this->assertCountFilter($crawler, 1, '#calculate_dex_availabilities .admin-item-cta.disabled');

        $this->assertCountFilter($crawler, 3, '.admin-item-refresh');

        $this->assertCountFilter($crawler, 1, '#update_games_collections_and_dex .admin-item-refresh');
        $updateGamesCollectionsAndDexHref = $crawler->filter('#update_games_collections_and_dex .admin-item-refresh')->attr('href') ?? '';
        $this->assertStringContainsString('/fr/istration/actions?refresh=', $updateGamesCollectionsAndDexHref);
        $this->assertStringContainsString('#update_games_collections_and_dex', $updateGamesCollectionsAndDexHref);

        $this->assertCountFilter($crawler, 1, '#calculate_game_bundles_availabilities .admin-item-refresh');
        $calculateGameBundlesAvailabilitiesHref = $crawler->filter('#calculate_game_bundles_availabilities .admin-item-refresh')->attr('href') ?? '';
        $this->assertStringContainsString('/fr/istration/actions?refresh=', $calculateGameBundlesAvailabilitiesHref);

        $this->assertCountFilter($crawler, 1, '#calculate_dex_availabilities .admin-item-refresh');
        $calculateGameBundlesAvailabilitiesHref = $crawler->filter('#calculate_game_bundles_availabilities .admin-item-refresh')->attr('href') ?? '';
        $this->assertStringContainsString('/fr/istration/actions?refresh=', $calculateGameBundlesAvailabilitiesHref);

        $this->assertCountFilter($crawler, 0, 'script[src="/js/album.js"]');

        $this->assertStringNotContainsString('const catchStates = JSON.parse', $crawler->outerHtml());
        $this->assertStringNotContainsString('watchCatchStates();', $crawler->outerHtml());

        $this->assertStringNotContainsString('const types = JSON.parse', $crawler->outerHtml());

        foreach ($this->getHomeReportData() as $slug => $data) {
            foreach ($data as $type => $report) {
                if (null === $report) {
                    $this->assertNoReport(
                        $crawler,
                        $slug,
                        $type,
                    );

                    continue;
                }

                $reportData = $report['data'] ?? [];

                $reportDatatime = $report['datatime'] ?? [];

                $reportExectime = $report['exectime'] ?? '';

                $reportError = $report['error'] ?? '';

                $reportProgress = $report['progress'] ?? false;

                $this->assertReport(
                    $crawler,
                    $slug,
                    $type,
                    $reportData,
                    $reportDatatime,
                    $reportExectime,
                    $reportError,
                    $reportProgress,
                );
            }
        }
    }

    private function getAdminHomeConnected(): Crawler
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/istration/actions');

        $this->assertResponseStatusCodeSame(200);

        $this->assertSame(
            'Pokénini Administration',
            $crawler->filter('title')->text()
        );

        $this->assertSame(
            'Administration',
            $crawler->filter('h1')->text()
        );

        $this->assertConnectedNavBar($crawler);
        $this->assertFrenchLangSwitch($crawler);

        return $crawler;
    }

    private function assertNoReport(
        Crawler $crawler,
        string $item,
        string $type,
    ): void {
        $this->assertCountFilter(
            $crawler,
            0,
            ".admin-item-{$item} .admin-item-{$type}"
        );

        $oppositeType = ('current' == $type) ? 'last' : 'current';

        $this->assertCountFilter(
            $crawler,
            0,
            ".admin-item-{$item} .admin-item-{$oppositeType} .admin-item-toggle"
        );
    }

    /**
     * @param array<string, string> $expectedReport
     * @param array<string, string> $expectedDateTime
     *
     * @SuppressWarnings("PHPMD.BooleanArgumentFlag")
     */
    private function assertReport(
        Crawler $crawler,
        string $item,
        string $type,
        array $expectedReport,
        array $expectedDateTime,
        string $executionTime,
        string $errorMessage = '',
        bool $hasProcessBar = false,
    ): void {
        $index = 0;

        $this->assertCountFilter(
            $crawler,
            !$expectedReport ? 0 : 1,
            ".admin-item-{$item} .admin-item-{$type} .admin-item-report"
        );

        foreach ($expectedReport as $label => $value) {
            $this->assertEquals(
                $label,
                $crawler->filter(".admin-item-{$item} .admin-item-{$type} .admin-item-report dt")->eq($index)->text()
            );
            $this->assertEquals(
                $value,
                $crawler->filter(".admin-item-{$item} .admin-item-{$type} .admin-item-report dd")->eq($index)->text()
            );

            ++$index;
        }

        if ($expectedDateTime) {
            $this->assertCountFilter($crawler, 1, ".admin-item-{$item} .admin-item-{$type} .admin-item-report-date");

            $this->assertEquals(
                $expectedDateTime['label'],
                $crawler->filter(".admin-item-{$item} .admin-item-{$type} .admin-item-report-date strong")->text()
            );
            $this->assertEquals(
                $expectedDateTime['value'],
                $crawler->filter(".admin-item-{$item} .admin-item-{$type} .admin-item-report-date em")->text()
            );
        }

        if ($executionTime) {
            $this->assertCountFilter($crawler, 1, ".admin-item-{$item} .admin-item-{$type} .admin-item-report-execution");

            $this->assertEquals(
                'Terminé en',
                $crawler->filter(".admin-item-{$item} .admin-item-{$type} .admin-item-report-execution strong")->text()
            );
            $this->assertEquals(
                $executionTime,
                $crawler->filter(".admin-item-{$item} .admin-item-{$type} .admin-item-report-execution em")->text()
            );
        }

        if ($errorMessage) {
            $this->assertCountFilter($crawler, 1, ".admin-item-{$item} .admin-item-{$type} .alert.alert-danger");

            $this->assertEquals(
                $errorMessage,
                $crawler->filter(".admin-item-{$item} .admin-item-{$type} .alert.alert-danger")->text()
            );
        }

        $this->assertCountFilter($crawler, $hasProcessBar ? 1 : 0, ".admin-item-{$item} .admin-item-{$type} .progress");
    }

    /**
     * @return array<string, array{
     *  current: null|array{
     *      data?: array<string, string>,
     *      datatime: array{
     *          label: string,
     *          value: string,
     *      },
     *      exectime?: string,
     *     progress?: bool,
     *      error?: string,
     *  },
     *  last?: null|array{
     *      data?: array<string, string>,
     *      datatime: array{
     *          label: string,
     *          value: string,
     *      },
     *      exectime?: string,
     *      progress?: bool,
     *      error?: string,
     *  },
     * }>
     *
     * @SuppressWarnings("PHPMD.ExcessiveMethodLength")
     */
    private function getHomeReportData(): array
    {
        return [
            'update_labels' => [
                'current' => [
                    'data' => [
                        'Statuts' => '6',
                        'Régions' => '0',
                        'Catégories' => '6',
                        'Formes régionales' => '4',
                        'Formes spéciales' => '7',
                        'Variantes' => '7',
                    ],
                    'datatime' => [
                        'label' => 'Terminé le',
                        'value' => '21/03/2023 13:53:07',
                    ],
                    'exectime' => '00:01:28',
                ],
                'last' => [
                    'data' => [
                        'Statuts' => '5',
                        'Régions' => '0',
                        'Catégories' => '5',
                        'Formes régionales' => '4',
                        'Formes spéciales' => '6',
                        'Variantes' => '6',
                    ],
                    'datatime' => [
                        'label' => 'Terminé le',
                        'value' => '20/03/2023 13:53:07',
                    ],
                    'exectime' => '00:00:08',
                ],
            ],
            'update_games_collections_and_dex' => [
                'current' => [
                    'datatime' => [
                        'label' => 'Démarré le',
                        'value' => '01/09/2023 10:00:20',
                    ],
                    'progress' => true,
                ],
                'last' => [
                    'datatime' => [
                        'label' => 'Terminé le',
                        'value' => '20/04/2023 02:52:59',
                    ],
                    'exectime' => '15:01:59',
                    'error' => 'Exception has been thrown for X reason',
                ],
            ],
            'update_pokemons' => [
                'current' => [
                    'data' => [
                        'Pokémons' => '1 934',
                    ],
                    'datatime' => [
                        'label' => 'Terminé le',
                        'value' => '21/03/2023 10:38:03',
                    ],
                    'exectime' => '00:01:28',
                ],
                'last' => [
                    'data' => [
                        'Pokémons' => '1 930',
                    ],
                    'datatime' => [
                        'label' => 'Terminé le',
                        'value' => '20/03/2023 10:38:03',
                    ],
                    'exectime' => '00:01:18',
                ],
            ],
            'update_regional_dex_numbers' => [
                'current' => null,
                'last' => null,
            ],
            'update_games_availabilities' => [
                'current' => [
                    'datatime' => [
                        'label' => 'Terminé le',
                        'value' => '21/03/2023 10:25:38',
                    ],
                    'exectime' => '00:34:38',
                ],
                'last' => [
                    'datatime' => [
                        'label' => 'Terminé le',
                        'value' => '20/03/2023 20:25:38',
                    ],
                    'exectime' => '00:33:32',
                ],
            ],
            'update_games_shinies_availabilities' => [
                'current' => [
                    'datatime' => [
                        'label' => 'Terminé le',
                        'value' => '22/04/2023 02:52:59',
                    ],
                    'exectime' => '15:01:59',
                    'error' => 'Exception has been thrown for X reason',
                ],
                'last' => [
                    'data' => [
                        'Disponibilités des jeux des chromatiques' => '41 691',
                    ],
                    'datatime' => [
                        'label' => 'Terminé le',
                        'value' => '20/03/2023 10:25:38',
                    ],
                    'exectime' => '00:34:38',
                ],
            ],
            'update_collections_availabilities' => [
                'current' => [
                    'data' => [
                        'Disponibilités des collections' => '1 234',
                    ],
                    'datatime' => [
                        'label' => 'Terminé le',
                        'value' => '21/09/2024 10:35:47',
                    ],
                    'exectime' => '00:01:00',
                ],
                'last' => [
                    'data' => [
                        'Disponibilités des collections' => '312',
                    ],
                    'datatime' => [
                        'label' => 'Terminé le',
                        'value' => '21/09/2024 10:01:00',
                    ],
                    'exectime' => '00:01:00',
                ],
            ],
            'calculate_game_bundles_availabilities' => [
                'current' => [
                    'datatime' => [
                        'label' => 'Démarré le',
                        'value' => '21/03/2023 08:15:04',
                    ],
                ],
                'last' => null,
            ],
            'calculate_game_bundles_shinies_availabilities' => [
                'current' => [
                    'data' => [
                        'Disponibilités des bundles des chromatiques' => '1 234',
                    ],
                    'datatime' => [
                        'label' => 'Terminé le',
                        'value' => '21/04/2023 17:27:18',
                    ],
                    'exectime' => '00:03:00',
                ],
                'last' => [
                    'data' => [
                        'Disponibilités des bundles des chromatiques' => '321',
                    ],
                    'datatime' => [
                        'label' => 'Terminé le',
                        'value' => '20/04/2023 17:28:18',
                    ],
                    'exectime' => '00:03:20',
                ],
            ],
            'calculate_dex_availabilities' => [
                'current' => [
                    'datatime' => [
                        'label' => 'Démarré le',
                        'value' => '21/03/2023 10:14:36',
                    ],
                    'progress' => true,
                ],
                'last' => [
                    'data' => [
                        'Disponibilités des dex' => '22 472',
                    ],
                    'datatime' => [
                        'label' => 'Terminé le',
                        'value' => '20/03/2023 11:05:08',
                    ],
                    'exectime' => '00:50:32',
                ],
            ],
            'calculate_pokemon_availabilities' => [
                'current' => [
                    'data' => [
                        'Disponibilités des packs de jeux par pokémons' => '1',
                        'Disponibilités des chromatiques des packs de jeux par pokémons' => '0',
                    ],
                    'datatime' => [
                        'label' => 'Terminé le',
                        'value' => '14/02/2024 10:14:36',
                    ],
                    'exectime' => '00:00:00',
                ],
                'last' => [
                    'data' => [
                        'Disponibilités des packs de jeux par pokémons' => '1',
                        'Disponibilités des chromatiques des packs de jeux par pokémons' => '0',
                    ],
                    'datatime' => [
                        'label' => 'Terminé le',
                        'value' => '14/02/2024 10:14:36',
                    ],
                    'exectime' => '00:00:00',
                ],
            ],
        ];
    }
}
```

- [ ] **Step 12: Repoint the Reports-page integration test**

In `tests/src/Integration/Controller/Admin/AdminReportsTest.php`:

Replace:
```php
use App\Controller\AlbumIndexController;
```
with:
```php
use App\Controller\AdminReportsController;
```

Replace:
```php
#[CoversClass(AlbumIndexController::class)]
```
with:
```php
#[CoversClass(AdminReportsController::class)]
```
(this test's `CoversClass` pointed at the wrong controller before this plan — it always exercised the Admin reports rendering, never `AlbumIndexController`.)

Replace:
```php
        $crawler = $client->request('GET', '/fr/istration');
```
with:
```php
        $crawler = $client->request('GET', '/fr/istration/reports');
```

- [ ] **Step 13: Run the integration tests for both pages**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Admin/AdminPageTest.php tests/src/Integration/Controller/Admin/AdminReportsTest.php`
Expected: PASS. (`ActionInvalidateTest`, `ActionUpdateTest`, `ActionCalculateTest` are expected to still FAIL at this point — they're fixed in Task 3, after `AdminActionController`'s redirect target is fixed in Task 2.)

- [ ] **Step 14: Commit**

```bash
git add src/Controller/AdminController.php src/Controller/AdminReportsController.php \
  templates/Admin/_tabs.html.twig templates/Admin/actions.html.twig templates/Admin/reports.html.twig \
  templates/_nav.html.twig \
  tests/src/Unit/Controller/AdminControllerTest.php tests/src/Unit/Controller/AdminReportsControllerTest.php \
  tests/src/Integration/Controller/Admin/AdminPageTest.php tests/src/Integration/Controller/Admin/AdminReportsTest.php
git rm templates/Admin/index.html.twig
git commit -m "feat: split Admin page into separate Actions and Reports routes"
```

---

### Task 2: Make `AdminActionController` redirect to the page that owns the action

**Files:**
- Modify: `src/Controller/AdminActionController.php:128-139`
- Modify: `tests/src/Unit/Controller/AdminActionControllerTest.php`

**Interfaces:**
- Consumes: `app_admin_actions`, `app_admin_reports` route names (produced in Task 1).
- Produces: `AdminActionController::execute()` now redirects to `app_admin_reports` when `$name === 'reports'`, `app_admin_actions` otherwise — this is what Task 3's integration tests rely on.

- [ ] **Step 1: Write the failing unit test for the `reports` redirect target**

In `tests/src/Unit/Controller/AdminActionControllerTest.php`, add this test method (e.g. right after `testAction()`):

```php
    public function testInvalidateReportsRedirectsToReportsPage(): void
    {
        $adminActionService = $this->createMock(AdminActionService::class);
        $adminActionService
            ->expects($this->once())
            ->method('execute')
            ->with('invalidate', 'reports')
            ->willReturn(new AdminAction('invalidate', 'reports', 'ok', '', ''))
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
            ->with('app_admin_reports', ['_fragment' => 'invalidate_reports'])
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

        $response = $controller->invalidate('reports', new Request([], ['_token' => 'valid_token']));

        $this->assertSame('/admin', $response->getTargetUrl());
    }
```

Then update the two existing `router->generate` expectations that assert the old `app_admin_index` name:

In `testAction()`, replace:
```php
            ->with('app_admin_index', ['_fragment' => 'invalidate_something'])
```
with:
```php
            ->with('app_admin_actions', ['_fragment' => 'invalidate_something'])
```

In `assertFailActionLogs()`, replace:
```php
            ->with('app_admin_index', ['_fragment' => $action.'_something'])
```
with:
```php
            ->with('app_admin_actions', ['_fragment' => $action.'_something'])
```

- [ ] **Step 2: Run the test suite to confirm it fails**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Controller/AdminActionControllerTest.php`
Expected: FAIL — `testAction`, `testFailUpdateLogs`, `testFailCalculateLogs`, and the new `testInvalidateReportsRedirectsToReportsPage` all fail because `execute()` still always generates `app_admin_index`.

- [ ] **Step 3: Make the redirect target conditional**

In `src/Controller/AdminActionController.php`, replace:

```php
        $this->requestStack->getSession()->set(self::SESSION_ACTION_DATA, $adminAction);

        return $this->redirectToRoute(
            'app_admin_index',
            [
                '_fragment' => "{$action}_{$name}",
            ]
        );
    }
```

with:

```php
        $this->requestStack->getSession()->set(self::SESSION_ACTION_DATA, $adminAction);

        return $this->redirectToRoute(
            'reports' === $name ? 'app_admin_reports' : 'app_admin_actions',
            [
                '_fragment' => "{$action}_{$name}",
            ]
        );
    }
```

- [ ] **Step 4: Run the test to confirm it passes**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Controller/AdminActionControllerTest.php`
Expected: PASS (all tests, including the new one).

- [ ] **Step 5: Commit**

```bash
git add src/Controller/AdminActionController.php tests/src/Unit/Controller/AdminActionControllerTest.php
git commit -m "fix: redirect admin actions to the page that owns the triggered button"
```

---

### Task 3: Repoint the action-submission integration tests

**Files:**
- Modify: `tests/src/Integration/Controller/Admin/ActionInvalidateTest.php`
- Modify: `tests/src/Integration/Controller/Admin/ActionUpdateTest.php`
- Modify: `tests/src/Integration/Controller/Admin/ActionCalculateTest.php`

**Interfaces:**
- Consumes: `/{_locale}/istration/actions` and `/{_locale}/istration/reports` pages (Task 1), conditional redirect (Task 2).

- [ ] **Step 1: Update `ActionInvalidateTest::testInvalidateSuccess`**

This test is parametrized over `labels`, `dex`, `albums`, `reports` — of these, only `reports`'s invalidate button lives on the Reports page; the rest are on the Actions page. Replace:

```php
    #[DataProvider('providerInvalidateSuccess')]
    public function testInvalidateSuccess(string $name): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/istration');
        $form = $crawler->filter("#invalidate_{$name} form")->form();
        $client->submit($form);
```

with:

```php
    #[DataProvider('providerInvalidateSuccess')]
    public function testInvalidateSuccess(string $name): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $page = 'reports' === $name ? '/fr/istration/reports' : '/fr/istration/actions';

        $crawler = $client->request('GET', $page);
        $form = $crawler->filter("#invalidate_{$name} form")->form();
        $client->submit($form);
```

The rest of the method (redirect assertion, title/h1/icon assertions) is unchanged — both pages share the `Admin/_macros.html.twig` `action()` macro that produces `.icon-square.bg-success`, and both share the "Pokénini Administration" title / "Administration" h1.

- [ ] **Step 2: Update `ActionUpdateTest`**

Replace, in `testAdminUpdateGamesShiniesAvailabilities`:
```php
        $crawler = $client->request('GET', '/fr/istration');
```
with:
```php
        $crawler = $client->request('GET', '/fr/istration/actions');
```

Replace, in `testAdminUpdateThenGoToIndex`:
```php
        $crawler = $client->request('GET', '/fr/istration');
        $form = $crawler->filter('#update_labels form')->form();
        $client->submit($form);

        $this->assertResponseStatusCodeSame(302);
        $crawler = $client->followRedirect();

        $this->assertCountFilter($crawler, 1, '.icon-square.bg-success');
        $this->assertCountFilter($crawler, 0, '.icon-square.bg-warning');

        $crawler = $client->request('GET', '/fr/istration');
```
with:
```php
        $crawler = $client->request('GET', '/fr/istration/actions');
        $form = $crawler->filter('#update_labels form')->form();
        $client->submit($form);

        $this->assertResponseStatusCodeSame(302);
        $crawler = $client->followRedirect();

        $this->assertCountFilter($crawler, 1, '.icon-square.bg-success');
        $this->assertCountFilter($crawler, 0, '.icon-square.bg-warning');

        $crawler = $client->request('GET', '/fr/istration/actions');
```

Replace, in the private `testAdminUpdate($name)` helper:
```php
        $crawler = $client->request('GET', '/fr/istration');
        $form = $crawler->filter("#update_{$name} form")->form();
        $client->submit($form);

        $this->assertResponseStatusCodeSame(302);
        $crawler = $client->followRedirect();
        $this->assertSame('http://localhost/fr/istration', $client->getRequest()->getUri());
```
with:
```php
        $crawler = $client->request('GET', '/fr/istration/actions');
        $form = $crawler->filter("#update_{$name} form")->form();
        $client->submit($form);

        $this->assertResponseStatusCodeSame(302);
        $crawler = $client->followRedirect();
        $this->assertSame('http://localhost/fr/istration/actions', $client->getRequest()->getUri());
```

(`testAdminUpdateUnknown` and `testAdminNonAdmin` hit `AdminActionController`'s own `/istration/action/update/...` route directly and are unaffected.)

- [ ] **Step 3: Update `ActionCalculateTest`**

Replace, in `testAdminCalculateDexAvailabilities`:
```php
        $crawler = $client->request('GET', '/fr/istration');
        $form = $crawler->filter('#calculate_dex_availabilities form')->form();
```
with:
```php
        $crawler = $client->request('GET', '/fr/istration/actions');
        $form = $crawler->filter('#calculate_dex_availabilities form')->form();
```

Replace, in `testAdminCalculateWithErrorsThenGoToIndex`:
```php
        $crawler = $client->request('GET', '/fr/istration');
        $form = $crawler->filter('#calculate_dex_availabilities form')->form();
        $client->submit($form);

        $this->assertResponseStatusCodeSame(302);
        $crawler = $client->followRedirect();

        $this->assertCountFilter($crawler, 0, '.icon-square.bg-success');
        $this->assertCountFilter($crawler, 2, '.icon-square.bg-danger');
        $this->assertCountFilter($crawler, 3, '.alert-danger');
        $this->assertCountFilter($crawler, 1, '.admin-item-calculate_dex_availabilities .alert');
        $this->assertSelectorTextSame(
            '.admin-item-calculate_dex_availabilities .alert',
            'HTTP/1.1 500 Internal Server Error returned for'
                .' "http://moco.back/istration/action/calculate/dex_availabilities".'
        );

        $crawler = $client->request('GET', '/fr/istration');
```
with:
```php
        $crawler = $client->request('GET', '/fr/istration/actions');
        $form = $crawler->filter('#calculate_dex_availabilities form')->form();
        $client->submit($form);

        $this->assertResponseStatusCodeSame(302);
        $crawler = $client->followRedirect();

        $this->assertCountFilter($crawler, 0, '.icon-square.bg-success');
        $this->assertCountFilter($crawler, 2, '.icon-square.bg-danger');
        $this->assertCountFilter($crawler, 3, '.alert-danger');
        $this->assertCountFilter($crawler, 1, '.admin-item-calculate_dex_availabilities .alert');
        $this->assertSelectorTextSame(
            '.admin-item-calculate_dex_availabilities .alert',
            'HTTP/1.1 500 Internal Server Error returned for'
                .' "http://moco.back/istration/action/calculate/dex_availabilities".'
        );

        $crawler = $client->request('GET', '/fr/istration/actions');
```

Replace, in the private `testAdminCalculate($name)` helper:
```php
        $crawler = $client->request('GET', '/fr/istration');
        $form = $crawler->filter("#calculate_{$name} form")->form();
```
with:
```php
        $crawler = $client->request('GET', '/fr/istration/actions');
        $form = $crawler->filter("#calculate_{$name} form")->form();
```

(`testAdminCalculateUnknown` and `testAdminNonAdmin` hit `AdminActionController`'s own route directly and are unaffected.)

- [ ] **Step 4: Run all three test files**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Admin/ActionInvalidateTest.php tests/src/Integration/Controller/Admin/ActionUpdateTest.php tests/src/Integration/Controller/Admin/ActionCalculateTest.php`
Expected: PASS (all tests in all three files).

- [ ] **Step 5: Commit**

```bash
git add tests/src/Integration/Controller/Admin/ActionInvalidateTest.php \
  tests/src/Integration/Controller/Admin/ActionUpdateTest.php \
  tests/src/Integration/Controller/Admin/ActionCalculateTest.php
git commit -m "test: repoint admin action integration tests to the split pages"
```

---

### Task 4: Repoint the browser tests

**Files:**
- Modify: `tests/src/Browser/Admin/RedirectActionsTest.php`
- Modify: `tests/src/Browser/Admin/ToggleActionsTest.php`

**Interfaces:**
- Consumes: `/{_locale}/istration/actions` page (Task 1), conditional redirect (Task 2). None of `RedirectActionsTest`'s data-provider entries include `reports`, so every case in this file targets the Actions page.

- [ ] **Step 1: Update `RedirectActionsTest`**

Replace:
```php
        $client->request('GET', '/fr/istration');

        $form = $client->getCrawler()->filter("#{$action}_{$item} form")->form();
        $client->submit($form);

        $rawUri = getenv('PANTHER_EXTERNAL_BASE_URI');
        $baseUri = rtrim(false !== $rawUri ? $rawUri : 'http://127.0.0.1:9080', '/');

        $this->assertSame(
            "{$baseUri}/fr/istration#{$action}_{$item}",
            $client->getCurrentURL()
        );
```
with:
```php
        $client->request('GET', '/fr/istration/actions');

        $form = $client->getCrawler()->filter("#{$action}_{$item} form")->form();
        $client->submit($form);

        $rawUri = getenv('PANTHER_EXTERNAL_BASE_URI');
        $baseUri = rtrim(false !== $rawUri ? $rawUri : 'http://127.0.0.1:9080', '/');

        $this->assertSame(
            "{$baseUri}/fr/istration/actions#{$action}_{$item}",
            $client->getCurrentURL()
        );
```

- [ ] **Step 2: Update `ToggleActionsTest`**

Replace:
```php
        $client->request('GET', '/fr/istration');
```
with:
```php
        $client->request('GET', '/fr/istration/actions');
```

- [ ] **Step 3: Run the browser tests**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Browser/Admin/RedirectActionsTest.php tests/src/Browser/Admin/ToggleActionsTest.php` (requires the `chrome`/`firefox` Selenium containers up — see `make tests-browser`).
Expected: PASS (all cases in both files).

- [ ] **Step 4: Commit**

```bash
git add tests/src/Browser/Admin/RedirectActionsTest.php tests/src/Browser/Admin/ToggleActionsTest.php
git commit -m "test: repoint admin browser tests to the split Actions page"
```

---

### Task 5: Full verification pass

**Files:** none (verification only).

- [ ] **Step 1: Run the full Admin test surface**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Controller/AdminControllerTest.php tests/src/Unit/Controller/AdminReportsControllerTest.php tests/src/Unit/Controller/AdminActionControllerTest.php tests/src/Integration/Controller/Admin`
Expected: PASS (every test under `tests/src/Integration/Controller/Admin/` plus the three unit files).

- [ ] **Step 2: Run the browser tests**

Run: `make tests-browser`
Expected: PASS.

- [ ] **Step 3: Run quality gates**

Run: `make quality`
Expected: PASS — this exercises PHPStan level 9, Psalm strict, PHP CS Fixer, PHPMD, and Deptrac against the new `AdminReportsController` and the rewritten templates. Pay particular attention to Deptrac (new controller, same layer as `AdminController`, should need no baseline changes) and PHPStan/Psalm (the `'reports' === $name ? 'app_admin_reports' : 'app_admin_actions'` ternary should type-check as `string` without a baseline entry).

- [ ] **Step 4: Run coverage and mutation measures**

Run: `make measures`
Expected: 100% line coverage and 100% MSI. The new `index()` redirect branch and the `'reports' === $name` conditional in `AdminActionController::execute()` are each covered by a dedicated test (Task 1 Step 5, Task 2 Step 1) — if Infection reports a surviving mutant on either, add a test asserting the specific route name generated for that branch.

- [ ] **Step 5: Manual smoke check (optional but recommended)**

With `make start` running, log in as admin via the fake authenticator (`http://localhost/fr/connect/f/c?t=admin`) and confirm:
- `http://localhost/fr/istration` redirects to `http://localhost/fr/istration/actions`.
- The Actions page shows only the update/calculate/invalidate buttons, no charts, and the "Rapports" tab link navigates to `http://localhost/fr/istration/reports`.
- The Reports page shows only the charts/tables and the cache-invalidation button, and the "Données et caches" tab link navigates back to `http://localhost/fr/istration/actions`.
- Submitting an update/calculate/invalidate button (other than the reports-cache one) lands back on the Actions page with the success/error banner; submitting the reports-cache invalidate button lands back on the Reports page with its banner.

No commit for this task (verification only).

---

## Self-Review Notes

- **Spec coverage:** every section of `docs/superpowers/specs/2026-07-07-split-admin-actions-reports-design.md` maps to a task — Routing/Controllers → Task 1 & 2, Templates → Task 1, Tests → Tasks 1, 2, 3, 4. The "Out of scope" items (Back API, `GetReportsService`/`GetActionLogsService` internals, `security.yaml`) are untouched by every task above.
- **Placeholder scan:** no TBD/TODO markers; every step shows the exact code or exact `old_string`/`new_string` diff.
- **Type consistency:** `AdminController::actions()`, `AdminReportsController::reports()`, and the `'reports' === $name` conditional in `AdminActionController::execute()` use the same route names (`app_admin_actions`, `app_admin_reports`) everywhere they're referenced — controllers, templates, and every test file.
- **Known pre-existing fix folded in:** `AdminReportsTest`'s `#[CoversClass(AlbumIndexController::class)]` was already wrong before this plan (the test has always exercised the Admin reports rendering); Task 1 Step 12 corrects it to `AdminReportsController::class` since that test now demonstrably covers the new class.
