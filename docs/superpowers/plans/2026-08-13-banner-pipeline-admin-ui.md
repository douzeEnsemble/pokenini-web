# Banner Pipeline Admin UI (pokenini-web) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add an `update_banners` button next to the existing `update_images` one on the admin "Trigger pipelines" page, with its own status panel, calling through to `pokenini-back`'s new `update_banners` trigger/status endpoints (from the `pokenini-back` plan).

**Architecture:** `AdminActionController::trigger()`'s allowed-name list gains `update_banners` (body unchanged — already generic). New `ResponseObject\BannerPipelineStageStatus`/`BannerPipelineStatus` (deserialization targets) and `Service\Back\GetBannerPipelineStatusService`, both duplicated from the `ImagePipeline*` equivalents. `AdminTriggerPipelineController` fetches both statuses and passes them to the template. `_trigger_pipeline.html.twig`'s existing data-driven button map gains an entry; a new `_banner_pipeline_status.html.twig` (duplicate of `_pipeline_status.html.twig`, its own fragment id) renders the second status panel.

**Tech Stack:** Symfony 8 / PHP ≥ 8.5, Twig, PHPUnit (`#[CoversClass]`, `#[Test]`/plain `testX` methods matching each file's existing convention), Panther-free WebTestCase integration tests with Moco fixtures.

## Global Constraints

- `declare(strict_types=1)` in every file. `ResponseObject` classes carry no logic (plain Serializer targets).
- 100% coverage and 100% MSI must stay green.
- Every unit test class: `/** @internal */` + `#[CoversClass(TargetClass::class)]`.
- No changes to `ImagePipelineStatus`/`ImagePipelineStageStatus`/`GetImagePipelineStatusService`/`_pipeline_status.html.twig` or their tests.
- `AdminActionController` and `AdminTriggerPipelineController` are the two existing files this plan modifies — update their existing tests in the same task, don't leave them broken.
- Moco fixtures for the integration test live under `tests/resources/moco/Back/` — the new banner-status endpoint needs its own fixture file there, following the existing `update_images`-status fixture's shape.

---

### Task 1: Allow `update_banners` through `AdminActionController::trigger()`

**Files:**
- Modify: `src/Controller/AdminActionController.php`
- Modify: `tests/src/Unit/Controller/AdminActionControllerTest.php`

**Interfaces:**
- Produces: `POST /istration/action/trigger/update_banners` now reaches `AdminActionService::execute('trigger', 'update_banners', 'POST')` (already fully generic — no other change needed).

- [ ] **Step 1: Write the failing test**

Add to `tests/src/Unit/Controller/AdminActionControllerTest.php` (after `triggerAction()`, mirroring it exactly with `update_banners`):

```php
    #[Test]
    public function triggerBannerAction(): void
    {
        $adminActionService = $this->createMock(AdminActionService::class);
        $adminActionService
            ->expects($this->once())
            ->method('execute')
            ->with('trigger', 'update_banners')
            ->willReturn(new AdminAction('trigger', 'update_banners', 'ok', '', ''))
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
            ->with('app_admin_trigger_pipeline', ['_fragment' => 'trigger_update_banners'])
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

        $response = $controller->trigger('update_banners', new Request([], ['_token' => 'valid_token']));

        $this->assertSame('/admin', $response->getTargetUrl());
    }
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `docker compose exec php php vendor/bin/phpunit --filter triggerBannerAction tests/src/Unit/Controller/AdminActionControllerTest.php`
Expected: FAIL — the route condition rejects `update_banners`, so `execute()` is never called as expected (or the test errors on an unmet expectation).

- [ ] **Step 3: Extend the route condition**

In `src/Controller/AdminActionController.php`, change the `trigger()` method's route condition (`src/Controller/AdminActionController.php:114-121`):

```php
    #[Route(
        '/trigger/{name}',
        methods: ['POST'],
        condition: "params['name']
            in [
                'update_images',
                'update_banners',
            ]"
    )]
```

No other change to `trigger()`'s body — `execute()` already forwards `$name` generically.

- [ ] **Step 4: Run the test to verify it passes**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Controller/AdminActionControllerTest.php`
Expected: PASS (all existing tests + the new one).

- [ ] **Step 5: Commit**

```bash
git add src/Controller/AdminActionController.php tests/src/Unit/Controller/AdminActionControllerTest.php
git commit -m "feat: allow update_banners through the admin trigger action"
```

---

### Task 2: `BannerPipelineStageStatus` / `BannerPipelineStatus` response objects

**Files:**
- Create: `src/ResponseObject/BannerPipelineStageStatus.php`
- Create: `src/ResponseObject/BannerPipelineStatus.php`
- Test: `tests/src/Integration/ResponseObject/BannerPipelineStageStatusTest.php`
- Test: `tests/src/Integration/ResponseObject/BannerPipelineStatusTest.php`

**Interfaces:**
- Produces: `App\ResponseObject\BannerPipelineStageStatus` (`state: string`, `url: ?string`), `App\ResponseObject\BannerPipelineStatus` (`correlationId`, `workflowA`/`iconPr`/`workflowB`/`resourcesPr`: `BannerPipelineStageStatus`) — Symfony Serializer deserialization targets for `GetBannerPipelineStatusService` (Task 3).

- [ ] **Step 1: Write the failing tests**

```php
<?php

declare(strict_types=1);

namespace App\Tests\Integration\ResponseObject;

use App\ResponseObject\BannerPipelineStageStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
#[CoversClass(BannerPipelineStageStatus::class)]
final class BannerPipelineStageStatusTest extends KernelTestCase
{
    #[Test]
    public function deserialize(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get(SerializerInterface::class);

        $json = <<<'JSON'
            {
                "state": "done",
                "url": "https://github.com/x/y/actions/runs/1"
            }
            JSON;

        $object = $serializer->deserialize($json, BannerPipelineStageStatus::class, 'json');

        $this->assertInstanceOf(BannerPipelineStageStatus::class, $object);
        $this->assertSame('done', $object->state);
        $this->assertSame('https://github.com/x/y/actions/runs/1', $object->url);
    }

    #[Test]
    public function deserializeWithNullUrl(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get(SerializerInterface::class);

        $json = <<<'JSON'
            {
                "state": "idle",
                "url": null
            }
            JSON;

        $object = $serializer->deserialize($json, BannerPipelineStageStatus::class, 'json');

        $this->assertInstanceOf(BannerPipelineStageStatus::class, $object);
        $this->assertSame('idle', $object->state);
        $this->assertNull($object->url);
    }
}
```

```php
<?php

declare(strict_types=1);

namespace App\Tests\Integration\ResponseObject;

use App\ResponseObject\BannerPipelineStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
#[CoversClass(BannerPipelineStatus::class)]
final class BannerPipelineStatusTest extends KernelTestCase
{
    #[Test]
    public function deserialize(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get(SerializerInterface::class);

        $json = <<<'JSON'
            {
                "correlation_id": "corr-1",
                "workflow_a": {"state": "done", "url": "https://github.com/x/y/actions/runs/1"},
                "icon_pr": {"state": "merged", "url": "https://github.com/x/y/pull/2"},
                "workflow_b": {"state": "idle", "url": null},
                "resources_pr": {"state": "idle", "url": null}
            }
            JSON;

        $object = $serializer->deserialize($json, BannerPipelineStatus::class, 'json');

        $this->assertInstanceOf(BannerPipelineStatus::class, $object);
        $this->assertSame('corr-1', $object->correlationId);
        $this->assertSame('done', $object->workflowA->state);
        $this->assertSame('https://github.com/x/y/actions/runs/1', $object->workflowA->url);
        $this->assertSame('merged', $object->iconPr->state);
        $this->assertSame('https://github.com/x/y/pull/2', $object->iconPr->url);
        $this->assertSame('idle', $object->workflowB->state);
        $this->assertNull($object->workflowB->url);
        $this->assertSame('idle', $object->resourcesPr->state);
        $this->assertNull($object->resourcesPr->url);
    }
}
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/ResponseObject/BannerPipelineStageStatusTest.php tests/src/Integration/ResponseObject/BannerPipelineStatusTest.php`
Expected: FAIL — classes not found.

- [ ] **Step 3: Write `BannerPipelineStageStatus`**

```php
<?php

declare(strict_types=1);

namespace App\ResponseObject;

/**
 * Same verified php-code-coverage false-negative already documented on
 * the sibling ImagePipelineStageStatus (identical property-promotion
 * shape) — see that class's docblock for how it was verified.
 *
 * @codeCoverageIgnore
 */
final class BannerPipelineStageStatus
{
    public function __construct(
        public readonly string $state,
        public readonly ?string $url,
    ) {}
}
```

- [ ] **Step 4: Write `BannerPipelineStatus`**

```php
<?php

declare(strict_types=1);

namespace App\ResponseObject;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class BannerPipelineStatus
{
    /**
     * @codeCoverageIgnore
     */
    public function __construct(
        #[SerializedName('correlation_id')]
        public readonly string $correlationId,
        #[SerializedName('workflow_a')]
        public readonly BannerPipelineStageStatus $workflowA,
        #[SerializedName('icon_pr')]
        public readonly BannerPipelineStageStatus $iconPr,
        #[SerializedName('workflow_b')]
        public readonly BannerPipelineStageStatus $workflowB,
        #[SerializedName('resources_pr')]
        public readonly BannerPipelineStageStatus $resourcesPr,
    ) {}
}
```

- [ ] **Step 5: Run the tests to verify they pass**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/ResponseObject/BannerPipelineStageStatusTest.php tests/src/Integration/ResponseObject/BannerPipelineStatusTest.php`
Expected: PASS (3 tests).

- [ ] **Step 6: Commit**

```bash
git add src/ResponseObject/BannerPipelineStageStatus.php src/ResponseObject/BannerPipelineStatus.php tests/src/Integration/ResponseObject/BannerPipelineStageStatusTest.php tests/src/Integration/ResponseObject/BannerPipelineStatusTest.php
git commit -m "feat: add BannerPipelineStatus response objects"
```

---

### Task 3: `GetBannerPipelineStatusService`

**Files:**
- Create: `src/Service/Back/GetBannerPipelineStatusService.php`
- Test: `tests/src/Unit/Service/Back/GetBannerPipelineStatusServiceTest.php`

**Interfaces:**
- Consumes: `AbstractBackService` (existing base class), `App\ResponseObject\BannerPipelineStatus` (Task 2).
- Produces: `App\Service\Back\GetBannerPipelineStatusService::get(bool $refresh): ?BannerPipelineStatus`. Consumed by `AdminTriggerPipelineController` (Task 4).

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\Exception\NoLoggedUserException;
use App\Security\UserTokenServiceInterface;
use App\Service\Back\GetBannerPipelineStatusService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\NameConverter\MetadataAwareNameConverter;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal
 *
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
#[CoversClass(GetBannerPipelineStatusService::class)]
final class GetBannerPipelineStatusServiceTest extends TestCase
{
    #[Test]
    public function getReturnsNullWhenNoRunExists(): void
    {
        $service = $this->getService('{}', 'https://back.domain/istration/action/trigger/update_banners/status');

        $this->assertNull($service->get(false));
    }

    #[Test]
    public function getWithRefreshAppendsQueryParam(): void
    {
        $service = $this->getService('{}', 'https://back.domain/istration/action/trigger/update_banners/status?refresh=1');

        $this->assertNull($service->get(true));
    }

    #[Test]
    public function getReturnsNullWhenNoRunExistsWithSurroundingWhitespace(): void
    {
        $service = $this->getService(" {}\n", 'https://back.domain/istration/action/trigger/update_banners/status');

        $this->assertNull($service->get(false));
    }

    #[Test]
    public function getDeserializesStatus(): void
    {
        $json = <<<'JSON'
            {
                "correlation_id": "corr-1",
                "workflow_a": {"state": "done", "url": "https://github.com/x/y/actions/runs/1"},
                "icon_pr": {"state": "merged", "url": "https://github.com/x/y/pull/2"},
                "workflow_b": {"state": "idle", "url": null},
                "resources_pr": {"state": "idle", "url": null}
            }
            JSON;

        $service = $this->getService($json, 'https://back.domain/istration/action/trigger/update_banners/status');

        $status = $service->get(false);

        $this->assertNotNull($status);
        $this->assertSame('corr-1', $status->correlationId);
        $this->assertSame('done', $status->workflowA->state);
        $this->assertSame('https://github.com/x/y/actions/runs/1', $status->workflowA->url);
        $this->assertSame('merged', $status->iconPr->state);
        $this->assertSame('https://github.com/x/y/pull/2', $status->iconPr->url);
        $this->assertSame('idle', $status->workflowB->state);
        $this->assertNull($status->workflowB->url);
        $this->assertSame('idle', $status->resourcesPr->state);
        $this->assertNull($status->resourcesPr->url);
    }

    private function getService(string $responseBody, string $expectedUrl): GetBannerPipelineStatusService
    {
        $response = $this->createStub(ResponseInterface::class);
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
        $userTokenService
            ->method('getLoggedUser')
            ->willThrowException(new NoLoggedUserException('No user logged'))
        ;

        return new GetBannerPipelineStatusService(
            $this->createStub(LoggerInterface::class),
            $client,
            'https://back.domain',
            './resources/certificates/cacert.pem',
            $userTokenService,
            $this->buildSerializer(),
        );
    }

    private function buildSerializer(): SerializerInterface
    {
        $classMetadataFactory = new ClassMetadataFactory(new AttributeLoader());
        $nameConverter = new MetadataAwareNameConverter($classMetadataFactory);

        return new Serializer(
            [
                new DateTimeNormalizer(),
                new ObjectNormalizer($classMetadataFactory, $nameConverter),
            ],
            [new JsonEncoder()]
        );
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/Back/GetBannerPipelineStatusServiceTest.php`
Expected: FAIL — `Class "App\Service\Back\GetBannerPipelineStatusService" not found`.

- [ ] **Step 3: Write the service**

```php
<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\ResponseObject\BannerPipelineStatus;

class GetBannerPipelineStatusService extends AbstractBackService
{
    public function get(bool $refresh): ?BannerPipelineStatus
    {
        $endpointUrl = '/istration/action/trigger/update_banners/status';

        if ($refresh) {
            $endpointUrl .= '?refresh=1';
        }

        $content = $this->requestContent('GET', $endpointUrl);

        if ('{}' === trim($content)) {
            return null;
        }

        /** @var BannerPipelineStatus */
        return $this->serializer->deserialize($content, BannerPipelineStatus::class, 'json');
    }
}
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/Back/GetBannerPipelineStatusServiceTest.php`
Expected: PASS (4 tests).

- [ ] **Step 5: Commit**

```bash
git add src/Service/Back/GetBannerPipelineStatusService.php tests/src/Unit/Service/Back/GetBannerPipelineStatusServiceTest.php
git commit -m "feat: add GetBannerPipelineStatusService"
```

---

### Task 4: Wire status into `AdminTriggerPipelineController`

**Files:**
- Modify: `src/Controller/AdminTriggerPipelineController.php`
- Modify: `tests/src/Unit/Controller/AdminTriggerPipelineControllerTest.php`
- Modify: `tests/resources/moco/Back/` (add a fixture for the new banner-status endpoint)

**Interfaces:**
- Consumes: `GetBannerPipelineStatusService` (Task 3).
- Produces: template context key `bannerPipelineStatus` (`?BannerPipelineStatus`), alongside the existing `actionLogsData`/`imagePipelineStatus`.

- [ ] **Step 1: Write the failing test**

Update `tests/src/Unit/Controller/AdminTriggerPipelineControllerTest.php`: both `testTriggerPipeline()` and `testTriggerPipelineError()` currently assert the Twig `render()` call receives exactly `['actionLogsData' => [], 'imagePipelineStatus' => null]`. Change both expected arrays to also include `'bannerPipelineStatus' => null`:

```php
                [
                    'actionLogsData' => [],
                    'imagePipelineStatus' => null,
                    'bannerPipelineStatus' => null,
                ]
```

(2 occurrences — one per test method.) And update `getController()` to also mock `GetBannerPipelineStatusService`:

```php
    private function getController(): AdminTriggerPipelineController
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

        $getBannerPipelineStatusService = $this->createMock(GetBannerPipelineStatusService::class);
        $getBannerPipelineStatusService
            ->expects($this->once())
            ->method('get')
            ->with(false)
            ->willReturn(null)
        ;

        return new AdminTriggerPipelineController(
            $getActionLogsService,
            $getImagePipelineStatusService,
            $getBannerPipelineStatusService,
        );
    }
```

Add `use App\Service\Back\GetBannerPipelineStatusService;` to the test file's imports.

- [ ] **Step 2: Run the test to verify it fails**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Controller/AdminTriggerPipelineControllerTest.php`
Expected: FAIL — constructor arity mismatch / unexpected `render()` argument.

- [ ] **Step 3: Update the controller**

Replace `src/Controller/AdminTriggerPipelineController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\AdminAction;
use App\Service\Back\GetActionLogsService;
use App\Service\Back\GetBannerPipelineStatusService;
use App\Service\Back\GetImagePipelineStatusService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/istration')]
final class AdminTriggerPipelineController extends AbstractController
{
    public function __construct(
        private readonly GetActionLogsService $getActionLogsService,
        private readonly GetImagePipelineStatusService $getImagePipelineStatusService,
        private readonly GetBannerPipelineStatusService $getBannerPipelineStatusService,
    ) {}

    #[Route('/trigger_pipeline', methods: ['GET'], name: 'app_admin_trigger_pipeline')]
    public function triggerPipeline(RequestStack $requestStack, Request $request): Response
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

        return $this->render(
            'Admin/trigger_pipeline.html.twig',
            [
                'actionLogsData' => $this->getActionLogsService->get(),
                'imagePipelineStatus' => $this->getImagePipelineStatusService->get($request->query->has('refresh')),
                'bannerPipelineStatus' => $this->getBannerPipelineStatusService->get($request->query->has('refresh')),
            ]
        );
    }
}
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Controller/AdminTriggerPipelineControllerTest.php`
Expected: PASS (2 tests).

- [ ] **Step 5: Add a Moco fixture for the banner-status endpoint**

Find the existing images-status fixture: `grep -rl "action/trigger/update_images/status" tests/resources/moco/Back/`. Copy it to a sibling fixture for `action/trigger/update_banners/status`, matching the URL pattern and response body shape (an empty `{}` response, matching a fresh/no-run state — the same shape the existing images fixture returns for the default no-`refresh` case). Match the existing file's naming convention exactly (inspect the found file's name before naming the new one).

- [ ] **Step 6: Update the integration test's element counts**

In `tests/src/Integration/Controller/Admin/AdminTriggerPipelineTest.php::testTriggerPipeline()`, two assertions currently expect exactly 1 (`src/.../AdminTriggerPipelineTest.php:69,72`) — once Task 5 (the template) adds a second button and a second status panel, these become 2:

```php
        $this->assertCountFilter($crawler, 2, '.admin-item-description');
        $this->assertCountFilter($crawler, 1, '#trigger_update_images button.admin-item-cta');
        $this->assertCountFilter($crawler, 1, '#trigger_update_banners button.admin-item-cta');
        $this->assertCountFilter($crawler, 0, '.admin-item-cta.disabled');
        $this->assertCountFilter($crawler, 2, '.admin-pipeline-status');
```

This assertion can only be finalized once Task 5's template changes exist — leave this edit for the end of Task 5 (see that task's last step), not here; this step only documents the change needed so it isn't missed.

- [ ] **Step 7: Commit**

```bash
git add src/Controller/AdminTriggerPipelineController.php tests/src/Unit/Controller/AdminTriggerPipelineControllerTest.php tests/resources/moco/Back/
git commit -m "feat: wire banner pipeline status into AdminTriggerPipelineController"
```

---

### Task 5: Templates + translations

**Files:**
- Modify: `templates/Admin/_trigger_pipeline.html.twig`
- Create: `templates/Admin/_banner_pipeline_status.html.twig`
- Modify: `translations/messages+intl-icu.en.yaml`
- Modify: `translations/messages+intl-icu.fr.yaml`
- Modify: `tests/src/Integration/Controller/Admin/AdminTriggerPipelineTest.php` (finalize the count assertions from Task 4 Step 6)

**Interfaces:**
- Consumes: `bannerPipelineStatus` (Task 4), the existing `admin.action(...)` Twig macro (unchanged, already generic).

- [ ] **Step 1: Extend the trigger-pipeline button map**

In `templates/Admin/_trigger_pipeline.html.twig`, change:

```twig
  {% set triggerPipelineItems = {
    'update_images': 'images',
  } %}
```

to:

```twig
  {% set triggerPipelineItems = {
    'update_images': 'images',
    'update_banners': 'image',
  } %}
```

And include the new status partial after the existing one (`templates/Admin/_trigger_pipeline.html.twig:13`):

```twig
{% include 'Admin/_pipeline_status.html.twig' %}
{% include 'Admin/_banner_pipeline_status.html.twig' %}
```

- [ ] **Step 2: Write `_banner_pipeline_status.html.twig`**

Duplicate of `_pipeline_status.html.twig`, reading `bannerPipelineStatus` instead of `imagePipelineStatus`, with its own refresh-link fragment `trigger_update_banners`:

```twig
{% if bannerPipelineStatus is not null %}
  <div class="row px-4 pb-4">
    <div class="col-12">
      {% set stageColor = {
        'idle': 'light',
        'running': 'info',
        'open': 'info',
        'done': 'success',
        'merged': 'success',
        'closed': 'danger',
        'failed': 'danger',
      } %}
      {% set stages = [
        {'label': 'workflow_a', 'stage': bannerPipelineStatus.workflowA},
        {'label': 'icon_pr', 'stage': bannerPipelineStatus.iconPr},
        {'label': 'workflow_b', 'stage': bannerPipelineStatus.workflowB},
        {'label': 'resources_pr', 'stage': bannerPipelineStatus.resourcesPr},
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
            &nbsp;
            {% if item.stage.url is not empty %}
              <a href="{{ item.stage.url }}" target="_blank" rel="noopener">
                <i class="bi bi-box-arrow-up-right"></i>
              </a>
            {% endif %}
          </dd>
        {% endfor %}
      </dl>
      <a href="{{ path(app.request.attributes.get('_route'), app.request.query.all|merge({'refresh': 'now'|date('U'), '_fragment': 'trigger_update_banners'})) }}" class="btn btn-outline-info btn-sm">
        {{ 'admin.pipeline_status.refresh'|trans }}
      </a>
    </div>
  </div>
{% endif %}
```

Reuses the existing `admin.pipeline_status.*` translation keys (`workflow_a`, `icon_pr`, `workflow_b`, `resources_pr`, `refresh`, `state.*`) — same stage labels/states apply to both pipelines, no new keys needed for this partial.

- [ ] **Step 3: Add translation keys for the new button**

In `translations/messages+intl-icu.en.yaml`, under `admin.actions.trigger_pipeline` (`translations/messages+intl-icu.en.yaml:530-536`):

```yaml
      update_banners:
        title: "Update banners"
        cta: "Trigger!"
        description: "Regenerates the dex/filter banner set from base + layers."
```

In `translations/messages+intl-icu.fr.yaml`, under the same key (`translations/messages+intl-icu.fr.yaml:536-542`):

```yaml
      update_banners:
        title: "Mettre à jour les bannières"
        cta: "Déclencher"
        description: "Régénère le jeu de bannières dex/filtres à partir de la base et des calques."
```

- [ ] **Step 4: Finalize the integration test's count assertions**

In `tests/src/Integration/Controller/Admin/AdminTriggerPipelineTest.php::testTriggerPipeline()`, apply the change documented in Task 4 Step 6:

```php
        $this->assertCountFilter($crawler, 2, '.admin-item-description');
        $this->assertCountFilter($crawler, 1, '#trigger_update_images button.admin-item-cta');
        $this->assertCountFilter($crawler, 1, '#trigger_update_banners button.admin-item-cta');
        $this->assertCountFilter($crawler, 0, '.admin-item-cta.disabled');
        $this->assertCountFilter($crawler, 2, '.admin-pipeline-status');
```

- [ ] **Step 5: Run the integration test**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Admin/AdminTriggerPipelineTest.php`
Expected: PASS (this requires the Moco fixture from Task 4 Step 5 to already be in place, since `bannerPipelineStatus` is fetched unconditionally on every page load).

- [ ] **Step 6: Manually verify in the browser**

Run: `make start` (if not already running), then log in as admin via `http://localhost/fr/connect/f/c?t=admin` and visit `http://localhost/fr/istration/trigger_pipeline`. Confirm both "Update Pokémon images" and "Update banners" buttons render, and clicking "Update banners" flashes a result and shows its own status panel (the actual GitHub dispatch will fail in local dev without real `GITHUB_BANNERS_*`-adjacent secrets configured end-to-end — that's expected; the goal here is confirming the page renders and the request round-trips through `pokenini-back`, not a real dispatch).

- [ ] **Step 7: Commit**

```bash
git add templates/Admin/_trigger_pipeline.html.twig templates/Admin/_banner_pipeline_status.html.twig translations/messages+intl-icu.en.yaml translations/messages+intl-icu.fr.yaml tests/src/Integration/Controller/Admin/AdminTriggerPipelineTest.php
git commit -m "feat: render the update_banners button + status panel"
```

---

### Task 6: Quality gate

**Files:** none new — verification only.

- [ ] **Step 1: Run the full test suite**

Run: `make tests`
Expected: all pass (unit + integration + browser), including the untouched `ImagePipeline*` suites.

- [ ] **Step 2: Run coverage and mutation testing**

Run: `make measures`
Expected: both coverage and MSI stay at 100%. If `BannerPipelineStageStatus`'s or `BannerPipelineStatus`'s constructor shows as uncovered despite the tests in Task 2/3 exercising it, that's the same known coverage-tool artifact already documented on `ImagePipelineStageStatus` — already pre-handled with `@codeCoverageIgnore`; only investigate further if something else is flagged.

- [ ] **Step 3: Run quality checks**

Run: `make quality`
Expected: editorconfig, jsonlint, phpcsfixer, phpmd, psalm, phpstan, deptrac, w3c all pass with no new baseline entries. Pay attention to `w3c` in particular — it validates rendered HTML, so the duplicated `_banner_pipeline_status.html.twig`/button markup must be valid on its own merits, not just copy-pasted.

- [ ] **Step 4: Commit any formatting fixes**

```bash
make phpcsfixer-fix
git add -A
git commit -m "style: apply cs-fixer" --allow-empty
```

(Skip the commit if `phpcsfixer-fix` made no changes.)

## Self-Review Notes

- **Spec coverage**: `AdminActionController` extension ✓ (Task 1), response objects ✓ (Task 2), `GetBannerPipelineStatusService` ✓ (Task 3), controller wiring ✓ (Task 4), templates + translations ✓ (Task 5). `ImagePipelineStatus`/`_pipeline_status.html.twig` left untouched ✓.
- **Placeholder scan**: the Moco fixture step (Task 4 Step 5) names the exact `grep` to locate the existing fixture rather than guessing its path/shape blind, since fixture file naming in this repo isn't derivable from the class names alone.
- **Type/name consistency**: `bannerPipelineStatus` is the template/context key used identically in `AdminTriggerPipelineController` (Task 4), its test (Task 4), and both Twig templates (Task 5); `trigger_update_banners` is the fragment id used identically in `AdminActionController`'s route-driven redirect (Task 1, inherited from the existing generic `{action}_{item}` fragment convention) and the new status partial's refresh link (Task 5).
