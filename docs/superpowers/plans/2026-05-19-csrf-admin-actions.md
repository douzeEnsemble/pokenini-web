# CSRF Protection for Admin Actions — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [x]`) syntax for tracking.

**Goal:** Protect admin action routes against CSRF by switching from GET to POST with Symfony CSRF token validation.

**Architecture:** Three controller methods (`update`, `calculate`, `invalidate`) gain a `Request` parameter; routes change to `methods: ['POST']`; each validates `isCsrfTokenValid()` before executing. The Twig macro `actionButton` replaces `<a href>` with `<form method="post">` + hidden `_token` field.

**Tech Stack:** Symfony 8.0, PHP 8.4, Twig, PHPUnit, Symfony Panther (browser tests).

---

## Files

| File | Change |
|------|--------|
| `src/Controller/AdminActionController.php` | Routes GET→POST, add `Request` param, CSRF validation |
| `templates/Admin/_macros.html.twig` | `<a href>` → `<form><button>` + hidden `_token` |
| `tests/src/Unit/Controller/AdminActionControllerTest.php` | Add 3 new CSRF rejection tests; update all existing tests |
| `tests/src/Integration/Controller/Admin/AdminPageTest.php` | CSS selectors `a.admin-item-cta` → `button.admin-item-cta` |
| `tests/src/Browser/Admin/RedirectActionsTest.php` | GET → form submission via admin page |

---

## Task 1 — Add 3 failing unit tests for CSRF rejection

**Files:**
- Modify: `tests/src/Unit/Controller/AdminActionControllerTest.php`

- [x] **Step 1.1 — Add use statements**

At the top of `AdminActionControllerTest.php`, add alongside existing uses:

```php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
```

- [x] **Step 1.2 — Add 3 new test methods**

Append these three methods before the closing `}` of the class:

```php
public function testUpdateInvalidCsrfToken(): void
{
    $csrfManager = $this->createMock(CsrfTokenManagerInterface::class);
    $csrfManager->method('isTokenValid')->willReturn(false);

    $container = $this->createMock(ContainerInterface::class);
    $container->method('has')->with('security.csrf.token_manager')->willReturn(true);
    $container->method('get')->willReturnMap([['security.csrf.token_manager', $csrfManager]]);

    $controller = new AdminActionController(
        $this->createStub(AdminActionService::class),
        $this->createStub(RequestStack::class),
        $this->createStub(LoggerInterface::class),
        $this->createStub(EventDispatcherInterface::class),
    );
    $controller->setContainer($container);

    $this->expectException(AccessDeniedException::class);
    $controller->update('labels', new Request([], ['_token' => 'bad_token']));
}

public function testCalculateInvalidCsrfToken(): void
{
    $csrfManager = $this->createMock(CsrfTokenManagerInterface::class);
    $csrfManager->method('isTokenValid')->willReturn(false);

    $container = $this->createMock(ContainerInterface::class);
    $container->method('has')->with('security.csrf.token_manager')->willReturn(true);
    $container->method('get')->willReturnMap([['security.csrf.token_manager', $csrfManager]]);

    $controller = new AdminActionController(
        $this->createStub(AdminActionService::class),
        $this->createStub(RequestStack::class),
        $this->createStub(LoggerInterface::class),
        $this->createStub(EventDispatcherInterface::class),
    );
    $controller->setContainer($container);

    $this->expectException(AccessDeniedException::class);
    $controller->calculate('game_bundles_availabilities', new Request([], ['_token' => 'bad_token']));
}

public function testInvalidateInvalidCsrfToken(): void
{
    $csrfManager = $this->createMock(CsrfTokenManagerInterface::class);
    $csrfManager->method('isTokenValid')->willReturn(false);

    $container = $this->createMock(ContainerInterface::class);
    $container->method('has')->with('security.csrf.token_manager')->willReturn(true);
    $container->method('get')->willReturnMap([['security.csrf.token_manager', $csrfManager]]);

    $controller = new AdminActionController(
        $this->createStub(AdminActionService::class),
        $this->createStub(RequestStack::class),
        $this->createStub(LoggerInterface::class),
        $this->createStub(EventDispatcherInterface::class),
    );
    $controller->setContainer($container);

    $this->expectException(AccessDeniedException::class);
    $controller->invalidate('labels', new Request([], ['_token' => 'bad_token']));
}
```

- [x] **Step 1.3 — Run new tests to verify they fail**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Unit/Controller/AdminActionControllerTest.php --filter "testUpdateInvalidCsrfToken|testCalculateInvalidCsrfToken|testInvalidateInvalidCsrfToken"
```

Expected: FAIL — PHP `ArgumentCountError` (controller doesn't accept `Request` yet). This confirms the test infrastructure is correct and the implementation is missing.

---

## Task 2 — Implement CSRF validation in controller + update existing unit tests

**Files:**
- Modify: `src/Controller/AdminActionController.php`
- Modify: `tests/src/Unit/Controller/AdminActionControllerTest.php`

- [x] **Step 2.1 — Add `Request` use statement to controller**

In `src/Controller/AdminActionController.php`, add alongside existing uses:

```php
use Symfony\Component\HttpFoundation\Request;
```

- [x] **Step 2.2 — Update `update()` method**

Replace:

```php
    #[Route(
        '/update/{name}',
        methods: ['GET'],
        condition: "params['name']
            in [
                'labels',
                'games_collections_and_dex',
                'pokemons',
                'games_availabilities',
                'games_shinies_availabilities',
                'regional_dex_numbers',
                'collections_availabilities',
            ]"
    )]
    public function update(
        string $name,
    ): RedirectResponse {
        return $this->execute($name, 'update', 'POST');
    }
```

With:

```php
    #[Route(
        '/update/{name}',
        methods: ['POST'],
        condition: "params['name']
            in [
                'labels',
                'games_collections_and_dex',
                'pokemons',
                'games_availabilities',
                'games_shinies_availabilities',
                'regional_dex_numbers',
                'collections_availabilities',
            ]"
    )]
    public function update(
        string $name,
        Request $request,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('admin_update', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        return $this->execute($name, 'update', 'POST');
    }
```

- [x] **Step 2.3 — Update `calculate()` method**

Replace:

```php
    #[Route(
        '/calculate/{name}',
        methods: ['GET'],
        condition: "params['name']
            in [
                'game_bundles_availabilities',
                'game_bundles_shinies_availabilities',
                'collections_availabilities',
                'dex_availabilities',
                'pokemon_availabilities',
            ]"
    )]
    public function calculate(
        string $name,
    ): RedirectResponse {
        return $this->execute($name, 'calculate', 'POST');
    }
```

With:

```php
    #[Route(
        '/calculate/{name}',
        methods: ['POST'],
        condition: "params['name']
            in [
                'game_bundles_availabilities',
                'game_bundles_shinies_availabilities',
                'collections_availabilities',
                'dex_availabilities',
                'pokemon_availabilities',
            ]"
    )]
    public function calculate(
        string $name,
        Request $request,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('admin_calculate', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        return $this->execute($name, 'calculate', 'POST');
    }
```

- [x] **Step 2.4 — Update `invalidate()` method**

Replace:

```php
    #[Route(
        '/invalidate/{name}',
        methods: ['GET'],
        condition: "params['name']
            in [
                'labels',
                'dex',
                'albums',
                'reports',
                'actions',
            ]"
    )]
    public function invalidate(
        string $name,
    ): RedirectResponse {
        return $this->execute($name, 'invalidate', 'DELETE');
    }
```

With:

```php
    #[Route(
        '/invalidate/{name}',
        methods: ['POST'],
        condition: "params['name']
            in [
                'labels',
                'dex',
                'albums',
                'reports',
                'actions',
            ]"
    )]
    public function invalidate(
        string $name,
        Request $request,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('admin_invalidate', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        return $this->execute($name, 'invalidate', 'DELETE');
    }
```

- [x] **Step 2.5 — Update `testAction()` in the test file**

Replace the entire `testAction()` method:

```php
public function testAction(): void
{
    $adminActionService = $this->createMock(AdminActionService::class);
    $adminActionService
        ->expects($this->once())
        ->method('execute')
        ->with('invalidate', 'something')
        ->willReturn(new AdminAction('invalidate', 'something', 'ok', '', ''))
    ;

    $session = $this->createMock(SessionInterface::class);
    $session->expects($this->once())->method('set');

    $requestStack = $this->createMock(RequestStack::class);
    $requestStack->expects($this->once())->method('getSession')->willReturn($session);

    $logger = $this->createMock(LoggerInterface::class);
    $logger->expects($this->never())->method('critical');

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
        ->with('app_admin_index', ['_fragment' => 'invalidate_something'])
        ->willReturn('/admin')
    ;

    $csrfManager = $this->createStub(CsrfTokenManagerInterface::class);
    $csrfManager->method('isTokenValid')->willReturn(true);

    $container = $this->createMock(ContainerInterface::class);
    $container->method('has')->with('security.csrf.token_manager')->willReturn(true);
    $container->method('get')->willReturnMap([
        ['security.csrf.token_manager', $csrfManager],
        ['router', $router],
    ]);

    $controller = new AdminActionController(
        $adminActionService,
        $requestStack,
        $logger,
        $eventDispatcher,
    );
    $controller->setContainer($container);

    $response = $controller->invalidate('something', new Request([], ['_token' => 'valid_token']));

    $this->assertSame('/admin', $response->getTargetUrl());
}
```

- [x] **Step 2.6 — Update `testFailUpdateLogs()` and `testFailCalculateLogs()`**

Replace the calls at the end of each method:

```php
public function testFailUpdateLogs(): void
{
    $controller = $this->assertFailActionLogs('update');

    $controller->update('something', new Request([], ['_token' => 'valid_token']));
}

public function testFailCalculateLogs(): void
{
    $controller = $this->assertFailActionLogs('calculate');

    $controller->calculate('something', new Request([], ['_token' => 'valid_token']));
}
```

- [x] **Step 2.7 — Update `testTransportExceptionIsLogged()`**

Replace the entire method:

```php
public function testTransportExceptionIsLogged(): void
{
    $adminActionService = $this->createMock(AdminActionService::class);
    $adminActionService
        ->expects($this->once())
        ->method('execute')
        ->willThrowException($this->createStub(TransportExceptionInterface::class))
    ;

    $session = $this->createMock(SessionInterface::class);
    $session->expects($this->once())->method('set');

    $requestStack = $this->createMock(RequestStack::class);
    $requestStack->expects($this->once())->method('getSession')->willReturn($session);

    $logger = $this->createMock(LoggerInterface::class);
    $logger->expects($this->once())->method('critical');

    $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
    $eventDispatcher->expects($this->never())->method('dispatch');

    $router = $this->createMock(RouterInterface::class);
    $router->expects($this->once())->method('generate')->willReturn('/admin');

    $csrfManager = $this->createStub(CsrfTokenManagerInterface::class);
    $csrfManager->method('isTokenValid')->willReturn(true);

    $container = $this->createMock(ContainerInterface::class);
    $container->method('has')->with('security.csrf.token_manager')->willReturn(true);
    $container->method('get')->willReturnMap([
        ['security.csrf.token_manager', $csrfManager],
        ['router', $router],
    ]);

    $controller = new AdminActionController(
        $adminActionService,
        $requestStack,
        $logger,
        $eventDispatcher,
    );
    $controller->setContainer($container);

    $controller->update('something', new Request([], ['_token' => 'valid_token']));
}
```

- [x] **Step 2.8 — Update `testLogicExceptionPropagates()`**

Replace the entire method:

```php
public function testLogicExceptionPropagates(): void
{
    $adminActionService = $this->createMock(AdminActionService::class);
    $adminActionService
        ->expects($this->once())
        ->method('execute')
        ->willThrowException(new \LogicException('Bug'))
    ;

    $session = $this->createStub(SessionInterface::class);

    $requestStack = $this->createStub(RequestStack::class);
    $requestStack->method('getSession')->willReturn($session);

    $logger = $this->createStub(LoggerInterface::class);
    $eventDispatcher = $this->createStub(EventDispatcherInterface::class);

    $csrfManager = $this->createStub(CsrfTokenManagerInterface::class);
    $csrfManager->method('isTokenValid')->willReturn(true);

    $container = $this->createStub(ContainerInterface::class);
    $container->method('has')->willReturn(true);
    $container->method('get')->willReturnMap([
        ['security.csrf.token_manager', $csrfManager],
    ]);

    $controller = new AdminActionController(
        $adminActionService,
        $requestStack,
        $logger,
        $eventDispatcher,
    );
    $controller->setContainer($container);

    $this->expectException(\LogicException::class);
    $controller->invalidate('something', new Request([], ['_token' => 'valid_token']));
}
```

- [x] **Step 2.9 — Update `assertFailActionLogs()` helper**

Replace the entire private method:

```php
private function assertFailActionLogs(string $action): AdminActionController
{
    $adminActionService = $this->createMock(AdminActionService::class);
    $adminActionService
        ->expects($this->once())
        ->method('execute')
        ->willThrowException($this->createStub(HttpExceptionInterface::class))
    ;

    $session = $this->createMock(SessionInterface::class);
    $session->expects($this->once())->method('set');

    $requestStack = $this->createMock(RequestStack::class);
    $requestStack->expects($this->once())->method('getSession')->willReturn($session);

    $logger = $this->createMock(LoggerInterface::class);
    $logger
        ->expects($this->once())
        ->method('critical')
        ->with(
            $this->isString(),
            $this->equalTo([
                'name' => 'something',
                'action' => $action,
            ])
        )
    ;

    $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
    $eventDispatcher->expects($this->never())->method('dispatch');

    $router = $this->createMock(RouterInterface::class);
    $router
        ->expects($this->once())
        ->method('generate')
        ->with('app_admin_index', ['_fragment' => $action.'_something'])
        ->willReturn('/admin')
    ;

    $csrfManager = $this->createStub(CsrfTokenManagerInterface::class);
    $csrfManager->method('isTokenValid')->willReturn(true);

    $container = $this->createMock(ContainerInterface::class);
    $container->method('has')->with('security.csrf.token_manager')->willReturn(true);
    $container->method('get')->willReturnMap([
        ['security.csrf.token_manager', $csrfManager],
        ['router', $router],
    ]);

    $controller = new AdminActionController(
        $adminActionService,
        $requestStack,
        $logger,
        $eventDispatcher,
    );
    $controller->setContainer($container);

    return $controller;
}
```

- [x] **Step 2.10 — Run all unit tests**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Unit/Controller/AdminActionControllerTest.php
```

Expected: all 8 tests PASS.

---

## Task 3 — Update Twig macro

**Files:**
- Modify: `templates/Admin/_macros.html.twig`

- [x] **Step 3.1 — Replace `actionButton` macro**

In `_macros.html.twig`, replace the entire `actionButton` macro (lines 111–134):

```twig
{% macro actionButton (
    action,
    actionPrefix,
    item,
    currentState,
    bgStyle
) %}
  {% set csrfId = 'admin_' ~ actionPrefix %}
  <div class="text-end border-top border-{{ bgStyle }} pt-3">
    <form method="post" action="{{ path('app_adminaction_'~actionPrefix, {'name': item}) }}">
      <input type="hidden" name="_token" value="{{ csrf_token(csrfId) }}">
      <button
        type="submit"
        class="btn btn-outline-primary admin-item-cta{{ currentState == 'pending' ? ' disabled' : '' }}"
        {% if currentState == 'pending' %}disabled{% endif %}
      >
        {{ ('admin.actions.'~action~'.'~item~'.cta')|trans }}
      </button>
    </form>

    {% if currentState == 'pending' %}
      {% set query = app.request.query.all %}
      {% set query = query|merge({
    'refresh': 'now'|date('U'),
    '_fragment': actionPrefix~'_'~item,
    }) %}
      <a href="{{ path(app.request.attributes.get('_route'), query) }}" class="btn btn-outline-info btn-sm admin-item-refresh">
        <i class="bi bi-arrow-clockwise"></i>
      </a>
    {% endif %}
  </div>
{% endmacro %}
```

Note: the refresh link is unchanged — it remains an `<a>` as it does not trigger an action.

---

## Task 4 — Update integration test CSS selectors

**Files:**
- Modify: `tests/src/Integration/Controller/Admin/AdminPageTest.php`

- [x] **Step 4.1 — Update 5 selector strings**

In `AdminPageTest::testAdminHome()`, apply these replacements (each is an independent `assertCountFilter` call):

| Old selector | New selector |
|---|---|
| `'.admin-item a.admin-item-cta'` | `'.admin-item button.admin-item-cta'` |
| `'.admin-item-update a.admin-item-cta'` | `'.admin-item-update button.admin-item-cta'` |
| `'.admin-item-calculate a.admin-item-cta'` | `'.admin-item-calculate button.admin-item-cta'` |
| `'.admin-item-invalidate a.admin-item-cta'` | `'.admin-item-invalidate button.admin-item-cta'` |
| `'.admin-item-invalidate_reports a.admin-item-cta'` | `'.admin-item-invalidate_reports button.admin-item-cta'` |

The disabled-class assertions (`.admin-item-cta.disabled`) are unchanged — the button keeps the CSS class.

- [x] **Step 4.2 — Run integration tests**

```bash
make tests-integration
```

Expected: all integration tests PASS.

---

## Task 5 — Update browser test

**Files:**
- Modify: `tests/src/Browser/Admin/RedirectActionsTest.php`

- [x] **Step 5.1 — Replace direct GET with form submission**

Replace the body of `testActionItems()`:

```php
public function testActionItems(string $action, string $item): void
{
    $client = $this->getNewClient();

    $user = GetUserToken::getFakeUserToken('109903422692691643666', 'TestProvider');
    $user->addAdminRole();
    $this->loginUser($client, $user);

    $client->request('GET', '/fr/istration');

    $form = $client->getCrawler()->filter("#{$action}_{$item} form")->form();
    $client->submit($form);

    $this->assertSame(
        "http://127.0.0.1:9080/fr/istration#{$action}_{$item}",
        $client->getCurrentURL()
    );
}
```

Explanation: navigating to `/fr/istration` renders the page with CSRF tokens embedded in each form's hidden `_token` field. `getCrawler()->filter("#update_labels form")->form()` extracts the form including the valid token. `submit($form)` sends the POST with the correct token.

- [x] **Step 5.2 — Run browser tests**

```bash
make tb
```

Expected: all browser tests PASS.

---

## Task 6 — Quality checks

- [x] **Step 6.1 — Run full test suite**

```bash
make tests
```

Expected: all tests (unit + integration + browser) PASS.

- [x] **Step 6.2 — Run code quality**

```bash
make code-quality
```

Expected: no errors. If PHPStan or Psalm report an issue with `getString()` on `ParameterBag`, confirm the method exists in Symfony 8.0 (`Symfony\Component\HttpFoundation\ParameterBag::getString()` was added in Symfony 6.4).

- [x] **Step 6.3 — Update improvement.md to mark point 9 as treated**

In `doc/improvement.md`, add after the `**Fichier**` line of point 9:

```markdown
**Traité** : routes passées en `methods: ['POST']`. Chaque méthode valide `isCsrfTokenValid('admin_{action}', ...)` et lève `AccessDeniedException` si le token est invalide. Le macro `actionButton` remplace `<a href>` par `<form method="post">` avec champ caché `_token`. 3 tests unitaires de rejet CSRF ajoutés. Tests d'intégration et browser mis à jour.
```
