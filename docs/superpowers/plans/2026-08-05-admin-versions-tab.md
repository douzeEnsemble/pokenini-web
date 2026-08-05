# Admin "Versions" Tab Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a "Versions" tab to the pokenini-web admin page showing the current version of all four Pokénini bricks (Web, Back, Api, Resources), degrading gracefully to "unavailable" per-brick when one can't be reached.

**Architecture:** `pokenini-api` gets a new `GET /version` endpoint reading its local `resources/metadata/version` file. `pokenini-back` gets a new `GET /istration/version` endpoint that reads its own local version file and calls the api's new endpoint, returning both in one JSON response (never failing itself — a failing api call just yields `api: null`). `pokenini-web` gets a new admin controller/template that combines its own local version (existing `AppVersionService`, unchanged), one call to back's new endpoint, and one direct HTTP call to the `pokenini-resources` static host (same pattern already used for images) into a single overview.

**Tech Stack:** Symfony 8 / PHP ≥ 8.5 across all three repos, PHPUnit, Moco (HTTP mock server) for pokenini-back's and pokenini-web's integration tests.

## Global Constraints

- This plan spans three independent git repositories, each with its own history, Docker stack, and Makefile:
  - `pokenini-api` — tasks 1-2
  - `pokenini-back` — tasks 3-5
  - `pokenini-web` — tasks 6-9 (this repo)
- Every command in every task runs inside that repo's `php` Docker container (`docker compose exec php ...`), from that repo's own directory. `cd` into the right repo before running any command for a given task.
- **Do not create any git commits at any point in this plan** — this is a standing user preference across every project. Leave all changes staged/unstaged for the user to review and commit themselves. Steps that would normally say "commit" are omitted from this plan for that reason.
- Every repo requires 100% test coverage and 100% Mutation Score Index (`make measures`) before the work in that repo is considered done, plus a clean `make quality` (or `make code-quality`) run. Run these at the end of each repo's task group, not necessarily after every single task, to avoid redundant slow runs — but never skip them before moving to the next repo.
- `declare(strict_types=1)` at the top of every new PHP file. Every new test class is `final`, carries `/** @internal */`, and uses `#[CoversClass(...)]`.
- Response DTOs / ResponseObjects across all three repos are `final class`es with `public readonly` constructor-promoted properties — never mutable, never with setters.
- Do not hardcode the *current* value of any `resources/metadata/version` file in a test assertion (it changes on every release). Where a test needs to assert against a repo's own local version file, read that file's content at test time instead.

---

## Task 1: `pokenini-api` — local version-reading service

**Repo:** `/home/renaud/projects/pokenini-api`

**Files:**
- Create: `src/Service/VersionService.php`
- Test: `tests/src/Unit/Service/VersionServiceTest.php`
- Modify: `config/services.yaml` (add `$metadataDir` bind)

**Interfaces:**
- Produces: `App\Service\VersionService::getVersion(): string` — reads `<metadataDir>/version`, returns the trimmed content, or the literal string `'unknown'` if the file doesn't exist. Constructor: `__construct(private readonly string $metadataDir)`.

- [ ] **Step 1: Add the `$metadataDir` bind to `config/services.yaml`**

In `config/services.yaml`, add one line to the existing `_defaults.bind` block (mirrors the existing `$sqlDir` bind right above it):

```yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true
        bind:
            string $spreadsheetId: '%env(SPREADSHEET_ID)%'
            string $googleApiSheetsUrl: '%env(GOOGLE_API_SHEETS_URL)%'
            int $eloKFactor: '%env(ELO_K_FACTOR)%'
            int $eloDDifference: '%env(ELO_D_DIFFERENCE)%'
            int $eloDefault: '%env(ELO_DEFAULT)%'
            string $sqlDir: '%kernel.project_dir%/resources/sql'
            string $metadataDir: '%kernel.project_dir%/resources/metadata'
```

- [ ] **Step 2: Write the failing unit test**

Create `tests/src/Unit/Service/VersionServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\VersionService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(VersionService::class)]
final class VersionServiceTest extends TestCase
{
    private string $tempDir;

    #[\Override]
    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir().'/version_service_test_'.uniqid();
        mkdir($this->tempDir);
    }

    #[\Override]
    protected function tearDown(): void
    {
        $files = glob($this->tempDir.'/*');
        if (false !== $files) {
            foreach ($files as $file) {
                unlink($file);
            }
        }
        rmdir($this->tempDir);
    }

    public function testGetVersionReturnsTrimmedFileContent(): void
    {
        file_put_contents($this->tempDir.'/version', "1.2.12\n");
        $service = new VersionService($this->tempDir);

        $this->assertSame('1.2.12', $service->getVersion());
    }

    public function testGetVersionReturnsFallbackWhenFileMissing(): void
    {
        $service = new VersionService($this->tempDir);

        $this->assertSame('unknown', $service->getVersion());
    }
}
```

- [ ] **Step 3: Run the test to verify it fails**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/VersionServiceTest.php
```

Expected: FAIL — `App\Service\VersionService` not found.

- [ ] **Step 4: Implement `VersionService`**

Create `src/Service/VersionService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Service;

class VersionService
{
    private const string FALLBACK_VERSION = 'unknown';

    public function __construct(private readonly string $metadataDir) {}

    public function getVersion(): string
    {
        $path = $this->metadataDir.'/version';

        if (!is_file($path)) {
            return self::FALLBACK_VERSION;
        }

        return trim((string) file_get_contents($path));
    }
}
```

- [ ] **Step 5: Run the test to verify it passes**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/VersionServiceTest.php
```

Expected: PASS (2 tests).

---

## Task 2: `pokenini-api` — `GET /version` endpoint

**Repo:** `/home/renaud/projects/pokenini-api`

**Files:**
- Create: `src/DTO/Response/VersionResponse.php`
- Create: `src/Controller/VersionController.php`
- Test: `tests/src/Integration/Controller/VersionControllerTest.php`

**Interfaces:**
- Consumes: `App\Service\VersionService::getVersion(): string` (Task 1).
- Produces: `GET /version` → `200 application/json` `{"version": "<string>"}`, gated by the existing global `access_control` (`roles: ROLE_API`, HTTP Basic `web`/`douze`) — no security config change needed.

- [ ] **Step 1: Write the failing integration test**

Create `tests/src/Integration/Controller/VersionControllerTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Controller\VersionController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(VersionController::class)]
final class VersionControllerTest extends WebTestCase
{
    #[Test]
    public function getReturnsSuccessfulJsonResponse(): void
    {
        $client = self::createClient();
        $client->request('GET', '/version', [], [], [
            'PHP_AUTH_USER' => 'web',
            'PHP_AUTH_PW' => 'douze',
        ]);

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/json');
    }

    #[Test]
    public function getReturnsVersionFromMetadataFile(): void
    {
        $client = self::createClient();
        $client->request('GET', '/version', [], [], [
            'PHP_AUTH_USER' => 'web',
            'PHP_AUTH_PW' => 'douze',
        ]);

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);

        $expectedVersion = trim((string) file_get_contents(dirname(__DIR__, 4).'/resources/metadata/version'));

        self::assertJsonStringEqualsJsonString(
            (string) json_encode(['version' => $expectedVersion], JSON_THROW_ON_ERROR),
            $content,
        );
    }

    #[Test]
    public function getNonAuthenticatedReturns401(): void
    {
        $client = self::createClient();
        $client->request('GET', '/version');

        self::assertResponseStatusCodeSame(401);
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/VersionControllerTest.php
```

Expected: FAIL — route `/version` doesn't exist (404), or class not found.

- [ ] **Step 3: Implement the DTO and controller**

Create `src/DTO/Response/VersionResponse.php`:

```php
<?php

declare(strict_types=1);

namespace App\DTO\Response;

final class VersionResponse
{
    public function __construct(
        public readonly string $version,
    ) {}
}
```

Create `src/Controller/VersionController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\Response\VersionResponse;
use App\Service\VersionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Attribute\Serialize;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/version')]
final class VersionController extends AbstractController
{
    #[Route(path: '', methods: ['GET'])]
    #[Serialize]
    public function get(VersionService $service): VersionResponse
    {
        return new VersionResponse($service->getVersion());
    }
}
```

- [ ] **Step 4: Run the test to verify it passes**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/VersionControllerTest.php
```

Expected: PASS (3 tests).

- [ ] **Step 5: Run full quality and measures gates for `pokenini-api`**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Unit tests/src/Integration
make code-quality
make measures
```

Expected: all green, coverage and MSI both 100%. If PHPStan/Psalm/PHPMD baselines need updating because of the new files, follow this repo's `CLAUDE.md` baseline-update commands.

---

## Task 3: `pokenini-back` — local version-reading service

**Repo:** `/home/renaud/projects/pokenini-back`

**Files:**
- Create: `src/Service/LocalVersionService.php`
- Test: `tests/src/Unit/Service/LocalVersionServiceTest.php`
- Modify: `config/services.yaml` (add `$metadataDir` bind)

**Interfaces:**
- Produces: `App\Service\LocalVersionService::getVersion(): string` — reads `<metadataDir>/version`, returns trimmed content or `'unknown'` fallback. Constructor: `__construct(private readonly string $metadataDir)`.

- [ ] **Step 1: Add the `$metadataDir` bind to `config/services.yaml`**

In `config/services.yaml`, add to the existing `_defaults.bind` block:

```yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true
        bind:
            string $apiUrl: '%env(API_URL)%'
            string $apiCafilePath: '%env(API_CAFILE_PATH)%'
            string $listAdmin: '%env(LIST_ADMIN)%'
            string $listTrainer: '%env(LIST_TRAINER)%'
            string $listCollector: '%env(LIST_COLLECTOR)%'
            bool $isInvitationRequired: '%env(bool:REQUIRE_INVITATION)%'
            string $apiLogin: '%env(API_LOGIN)%'
            string $apiPassword: '%env(API_PASSWORD)%'
            string $env: '%kernel.environment%'
            string $metadataDir: '%kernel.project_dir%/resources/metadata'
```

(Keep every other existing line in that block untouched — only add the new `$metadataDir` entry.)

- [ ] **Step 2: Write the failing unit test**

Create `tests/src/Unit/Service/LocalVersionServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\LocalVersionService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(LocalVersionService::class)]
final class LocalVersionServiceTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir().'/local_version_service_test_'.uniqid();
        mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        $files = glob($this->tempDir.'/*');
        if (false !== $files) {
            foreach ($files as $file) {
                unlink($file);
            }
        }
        rmdir($this->tempDir);
    }

    public function testGetVersionReturnsTrimmedFileContent(): void
    {
        file_put_contents($this->tempDir.'/version', "1.2.12\n");
        $service = new LocalVersionService($this->tempDir);

        $this->assertSame('1.2.12', $service->getVersion());
    }

    public function testGetVersionReturnsFallbackWhenFileMissing(): void
    {
        $service = new LocalVersionService($this->tempDir);

        $this->assertSame('unknown', $service->getVersion());
    }
}
```

- [ ] **Step 3: Run the test to verify it fails**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/LocalVersionServiceTest.php
```

Expected: FAIL — class not found.

- [ ] **Step 4: Implement `LocalVersionService`**

Create `src/Service/LocalVersionService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Service;

class LocalVersionService
{
    private const string FALLBACK_VERSION = 'unknown';

    public function __construct(private readonly string $metadataDir) {}

    public function getVersion(): string
    {
        $path = $this->metadataDir.'/version';

        if (!is_file($path)) {
            return self::FALLBACK_VERSION;
        }

        return trim((string) file_get_contents($path));
    }
}
```

- [ ] **Step 5: Run the test to verify it passes**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/LocalVersionServiceTest.php
```

Expected: PASS (2 tests).

---

## Task 4: `pokenini-back` — `GetVersionApiService` (calls the api's new `/version`)

**Repo:** `/home/renaud/projects/pokenini-back`

**Files:**
- Create: `src/Service/Api/GetVersionApiService.php`
- Test: `tests/src/Unit/Service/Api/GetVersionApiServiceTest.php`
- Modify: `tests/resources/moco/Api/moco.json` (add a `/version` mock rule)

**Interfaces:**
- Consumes: `AbstractApiService::requestContent(string $method, string $endpointUrl, array $options = []): string` (existing, `src/Service/Api/AbstractApiService.php`), `App\Utils\JsonDecoder::decode(string $json): mixed` (existing).
- Produces: `App\Service\Api\GetVersionApiService::get(): ?string` — calls `GET /version` on the api, returns the decoded `version` field, or `null` on any transport/HTTP/JSON-decode failure. Never throws.

- [ ] **Step 1: Write the failing unit test**

Create `tests/src/Unit/Service/Api/GetVersionApiServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Api;

use App\Service\Api\GetVersionApiService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal
 */
#[CoversClass(GetVersionApiService::class)]
final class GetVersionApiServiceTest extends TestCase
{
    public function testGetReturnsVersionFromDecodedBody(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getContent')->willReturn('{"version":"1.2.12"}');

        $client = $this->createMock(HttpClientInterface::class);
        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'https://api.domain/version',
                [
                    'headers' => [
                        'accept' => 'application/json',
                    ],
                    'auth_basic' => [
                        'web',
                        'douze',
                    ],
                    'cafile' => './resources/certificates/cacert.pem',
                ],
            )
            ->willReturn($response)
        ;

        $this->assertSame('1.2.12', $this->getService($client)->get());
    }

    public function testGetReturnsNullOnTransportError(): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')->willThrowException(
            $this->createMock(TransportException::class)
        );

        $this->assertNull($this->getService($client)->get());
    }

    public function testGetReturnsNullOnHttpError(): void
    {
        $errorResponse = $this->createMock(ResponseInterface::class);
        $errorResponse->method('getStatusCode')->willReturn(500);

        $exception = $this->createStub(ClientExceptionInterface::class);
        $exception->method('getResponse')->willReturn($errorResponse);

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('getContent')
            ->willThrowException($exception)
        ;

        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')->willReturn($response);

        $this->assertNull($this->getService($client)->get());
    }

    public function testGetReturnsNullOnMalformedJson(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getContent')->willReturn('not json');

        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')->willReturn($response);

        $this->assertNull($this->getService($client)->get());
    }

    private function getService(HttpClientInterface $client): GetVersionApiService
    {
        $cache = new TagAwareAdapter(new ArrayAdapter(), new ArrayAdapter());

        return new GetVersionApiService(
            $this->createStub(LoggerInterface::class),
            $client,
            'https://api.domain',
            './resources/certificates/cacert.pem',
            $cache,
            'web',
            'douze',
        );
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/Api/GetVersionApiServiceTest.php
```

Expected: FAIL — class not found.

- [ ] **Step 3: Implement `GetVersionApiService`**

Create `src/Service/Api/GetVersionApiService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Utils\JsonDecoder;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

class GetVersionApiService extends AbstractApiService
{
    public function get(): ?string
    {
        try {
            $content = $this->requestContent('GET', '/version');

            /** @var array{version: string} $decoded */
            $decoded = JsonDecoder::decode($content);

            return $decoded['version'];
        } catch (ExceptionInterface|\JsonException) {
            return null;
        }
    }
}
```

- [ ] **Step 4: Run the test to verify it passes**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/Api/GetVersionApiServiceTest.php
```

Expected: PASS (4 tests).

- [ ] **Step 5: Add the Moco mock rule for the api's `/version` endpoint**

In `tests/resources/moco/Api/moco.json`, add a new rule to the top-level JSON array (mirror the existing `/types` rule's shape, right next to it):

```json
{
  "request": {
    "uri": "/version",
    "headers": {
      "accept": "application/json",
      "authorization": "Basic d2ViOmRvdXpl"
    }
  },
  "response": {
    "json": {
      "version": "1.9.8"
    }
  }
}
```

This fixture value (`1.9.8`) is deliberately different from the real committed `resources/metadata/version` files, so Task 5's integration test can distinguish "api version from the mock" from "back's own local version" unambiguously.

---

## Task 5: `pokenini-back` — `GET /istration/version` endpoint

**Repo:** `/home/renaud/projects/pokenini-back`

**Files:**
- Create: `src/Controller/Admin/AdminVersionController.php`
- Test: `tests/src/Integration/Admin/VersionsTest.php`

**Interfaces:**
- Consumes: `App\Service\LocalVersionService::getVersion(): string` (Task 3), `App\Service\Api\GetVersionApiService::get(): ?string` (Task 4).
- Produces: `GET /istration/version` → `200 application/json` `{"back": "<string>", "api": "<string>"|null}`. Already gated by the existing `{ path: ^/istration, roles: ROLE_ADMIN }` access-control rule — no security config change needed. Never returns a non-2xx status itself (a failing api call degrades to `api: null`, not an error).

- [ ] **Step 1: Write the failing integration test**

Create `tests/src/Integration/Admin/VersionsTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Integration\Admin;

use App\Controller\Admin\AdminVersionController;
use App\Tests\Integration\Trait\ClientRequestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[Group('api-mocked-testing')]
#[CoversClass(AdminVersionController::class)]
final class VersionsTest extends WebTestCase
{
    use ClientRequestTrait;

    public function testGetVersion(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'admin',
            'GET',
            '/istration/version',
        );

        $this->assertResponseIsSuccessful();

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);

        $expectedBackVersion = trim((string) file_get_contents(dirname(__DIR__, 4).'/resources/metadata/version'));

        self::assertJsonStringEqualsJsonString(
            (string) json_encode(['back' => $expectedBackVersion, 'api' => '1.9.8'], JSON_THROW_ON_ERROR),
            $content,
        );
    }

    public function testGetVersionNonAuthenticated(): void
    {
        $client = self::createClient();

        $client->request(
            'GET',
            '/istration/version',
        );

        $this->assertResponseStatusCodeSame(401);
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Integration/Admin/VersionsTest.php
```

Expected: FAIL — route `/istration/version` doesn't exist (404), or class not found.

- [ ] **Step 3: Implement `AdminVersionController`**

Create `src/Controller/Admin/AdminVersionController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\Api\GetVersionApiService;
use App\Service\LocalVersionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/istration')]
final class AdminVersionController extends AbstractController
{
    public function __construct(
        private readonly LocalVersionService $localVersionService,
        private readonly GetVersionApiService $getVersionApiService,
    ) {}

    #[Route('/version', methods: ['GET'])]
    public function version(): JsonResponse
    {
        return $this->json([
            'back' => $this->localVersionService->getVersion(),
            'api' => $this->getVersionApiService->get(),
        ]);
    }
}
```

- [ ] **Step 4: Run the test to verify it passes**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Integration/Admin/VersionsTest.php
```

Expected: PASS (2 tests).

- [ ] **Step 5: Run full quality and measures gates for `pokenini-back`**

```bash
docker compose exec php php vendor/bin/phpunit --display-all tests/src/Unit
docker compose exec php php vendor/bin/phpunit --display-all tests/src/Integration
make code-quality
make measures
```

Expected: all green, coverage and MSI both 100%. Also run `make check-moco-refs` (or the equivalent `infra-quality` target covering it) so the new Moco fixture doesn't get flagged as stale/unused. Update PHPStan/Psalm/PHPMD baselines per this repo's `CLAUDE.md` if needed.

---

## Task 6: `pokenini-web` — `RESOURCES_VERSION_URL` env var and `GetResourcesVersionService`

**Repo:** `/home/renaud/projects/pokenini-web`

**Files:**
- Modify: `.env`, `.env.dev`, `.env.dev.local`, `.env.prod`, `.env.int`, `.env.ci`, `.env.test`, `.env.test.local` (add `RESOURCES_VERSION_URL`)
- Modify: `config/services.yaml` (add `$resourcesVersionUrl` bind)
- Modify: `tests/resources/moco/Back/moco.json` (add a `/resources/metadata/version` mock rule — see note below on why this reuses the `moco.back` container)
- Create: `src/Service/GetResourcesVersionService.php`
- Test: `tests/src/Unit/Service/GetResourcesVersionServiceTest.php`

**Interfaces:**
- Produces: `App\Service\GetResourcesVersionService::get(): ?string` — GETs `$resourcesVersionUrl`, returns the trimmed body, or `null` on any transport/HTTP failure. Constructor: `__construct(private readonly HttpClientInterface $client, private readonly string $resourcesVersionUrl)`.

**Note on the test-environment URL:** `pokenini-resources` is a genuinely different host from `pokenini-back` in every real environment, but in tests there is no dedicated Moco container for it. Rather than provisioning a third Docker service for one plain-text fixture, `.env.test`/`.env.test.local` point `RESOURCES_VERSION_URL` at the existing `moco.back` container (`http://moco.back/resources/metadata/version`) — Moco just matches URIs, it doesn't care what real host a path "belongs to" conceptually.

- [ ] **Step 1: Add `RESOURCES_VERSION_URL` to every env file**

Add one line to each file, right after the existing `POKEMON_IMAGE_URL` line, using the same host/port as that file's `POKEMON_IMAGE_URL` but with the path replaced by `/resources/metadata/version`:

`.env` (after line 30):
```
RESOURCES_VERSION_URL='http://localhost:8083/resources/metadata/version'
```

`.env.dev` (after its `POKEMON_IMAGE_URL` line):
```
RESOURCES_VERSION_URL='http://localhost:8083/resources/metadata/version'
```

`.env.dev.local` (after its `POKEMON_IMAGE_URL` line):
```
RESOURCES_VERSION_URL='http://resources.pokenini.local:8083/resources/metadata/version'
```

`.env.prod` (after its `POKEMON_IMAGE_URL` line):
```
RESOURCES_VERSION_URL='http://localhost:8082/resources/metadata/version'
```

`.env.int` (after its `POKEMON_IMAGE_URL` line):
```
RESOURCES_VERSION_URL='https://icon.pokenini.fr/resources/metadata/version'
```

`.env.ci` (after its `POKEMON_IMAGE_URL` line):
```
RESOURCES_VERSION_URL='http://localhost:8082/resources/metadata/version'
```

`.env.test` (after its `POKEMON_IMAGE_URL` line) — **deliberately points at Moco, not the real prod-like value**:
```
RESOURCES_VERSION_URL='http://moco.back/resources/metadata/version'
```

`.env.test.local` (same as `.env.test`):
```
RESOURCES_VERSION_URL='http://moco.back/resources/metadata/version'
```

- [ ] **Step 2: Add the `$resourcesVersionUrl` bind to `config/services.yaml`**

In `config/services.yaml`, add to the existing `_defaults.bind` block:

```yaml
services:
  _defaults:
    autowire: true
    autoconfigure: true
    bind:
      string $projectDir: "%kernel.project_dir%"
      string $backUrl: "%env(BACK_URL)%"
      string $backCafilePath: "%env(BACK_CAFILE_PATH)%"
      string $demoUserId: "%env(DEMO_USER_ID)%"
      string $resourcesVersionUrl: "%env(RESOURCES_VERSION_URL)%"
```

- [ ] **Step 3: Write the failing unit test**

Create `tests/src/Unit/Service/GetResourcesVersionServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\GetResourcesVersionService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal
 */
#[CoversClass(GetResourcesVersionService::class)]
final class GetResourcesVersionServiceTest extends TestCase
{
    public function testGetReturnsTrimmedVersion(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getContent')->willReturn("1.9.7\n");

        $client = $this->createMock(HttpClientInterface::class);
        $client
            ->expects($this->once())
            ->method('request')
            ->with('GET', 'https://resources.domain/resources/metadata/version')
            ->willReturn($response)
        ;

        $service = new GetResourcesVersionService($client, 'https://resources.domain/resources/metadata/version');

        $this->assertSame('1.9.7', $service->get());
    }

    public function testGetReturnsNullOnTransportError(): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')->willThrowException(
            $this->createMock(TransportException::class)
        );

        $service = new GetResourcesVersionService($client, 'https://resources.domain/resources/metadata/version');

        $this->assertNull($service->get());
    }

    public function testGetReturnsNullOnHttpError(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('getContent')
            ->willThrowException($this->createStub(ClientExceptionInterface::class))
        ;

        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')->willReturn($response);

        $service = new GetResourcesVersionService($client, 'https://resources.domain/resources/metadata/version');

        $this->assertNull($service->get());
    }
}
```

- [ ] **Step 4: Run the test to verify it fails**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/GetResourcesVersionServiceTest.php
```

Expected: FAIL — class not found.

- [ ] **Step 5: Implement `GetResourcesVersionService`**

Create `src/Service/GetResourcesVersionService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GetResourcesVersionService
{
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly string $resourcesVersionUrl,
    ) {}

    public function get(): ?string
    {
        try {
            $content = $this->client->request('GET', $this->resourcesVersionUrl)->getContent();
        } catch (ExceptionInterface) {
            return null;
        }

        return trim($content);
    }
}
```

- [ ] **Step 6: Run the test to verify it passes**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/GetResourcesVersionServiceTest.php
```

Expected: PASS (3 tests).

- [ ] **Step 7: Add the Moco rule reusing `moco.back` for the resources fixture**

In `tests/resources/moco/Back/moco.json`, add a new rule to the top-level JSON array:

```json
{
  "request": {
    "uri": "/resources/metadata/version"
  },
  "response": {
    "text": "1.9.7"
  }
}
```

(No auth headers required in the request matcher — `pokenini-resources` is a public static host with no authentication, and `GetResourcesVersionService` doesn't send any.)

---

## Task 7: `pokenini-web` — `GetVersionsService` (calls back's `/istration/version`)

**Repo:** `/home/renaud/projects/pokenini-web`

**Files:**
- Create: `src/ResponseObject/Versions.php`
- Create: `src/Service/Back/GetVersionsService.php`
- Test: `tests/src/Unit/Service/Back/GetVersionsServiceTest.php`

**Interfaces:**
- Consumes: `AbstractBackService::requestContent(...)` (existing, `src/Service/Back/AbstractBackService.php`).
- Produces:
  - `App\ResponseObject\Versions` — `final class` with `public readonly ?string $back` and `public readonly ?string $api`.
  - `App\Service\Back\GetVersionsService::get(): Versions` — calls `GET /istration/version` on the back, deserializes into `Versions`; on any transport/HTTP/deserialization failure, returns `new Versions(null, null)` instead of throwing.

- [ ] **Step 1: Write the failing unit test**

Create `tests/src/Unit/Service/Back/GetVersionsServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\Exception\NoLoggedUserException;
use App\Security\UserTokenServiceInterface;
use App\Service\Back\GetVersionsService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\Exception\TransportException;
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
 */
#[CoversClass(GetVersionsService::class)]
final class GetVersionsServiceTest extends TestCase
{
    public function testGetDeserializesVersions(): void
    {
        $versions = $this->getServiceWithResponseBody('{"back":"1.2.12","api":"1.2.13"}')->get();

        $this->assertSame('1.2.12', $versions->back);
        $this->assertSame('1.2.13', $versions->api);
    }

    public function testGetHandlesNullApiField(): void
    {
        $versions = $this->getServiceWithResponseBody('{"back":"1.2.12","api":null}')->get();

        $this->assertSame('1.2.12', $versions->back);
        $this->assertNull($versions->api);
    }

    public function testGetReturnsNullFieldsOnTransportError(): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')->willThrowException(
            $this->createMock(TransportException::class)
        );

        $versions = $this->buildService($client)->get();

        $this->assertNull($versions->back);
        $this->assertNull($versions->api);
    }

    private function getServiceWithResponseBody(string $responseBody): GetVersionsService
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getContent')->willReturn($responseBody);

        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')->willReturn($response);

        return $this->buildService($client);
    }

    private function buildService(HttpClientInterface $client): GetVersionsService
    {
        $userTokenService = $this->createStub(UserTokenServiceInterface::class);
        $userTokenService
            ->method('getLoggedUser')
            ->willThrowException(new NoLoggedUserException('No user logged'))
        ;

        return new GetVersionsService(
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

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/Back/GetVersionsServiceTest.php
```

Expected: FAIL — classes not found.

- [ ] **Step 3: Implement `Versions` and `GetVersionsService`**

Create `src/ResponseObject/Versions.php`:

```php
<?php

declare(strict_types=1);

namespace App\ResponseObject;

final class Versions
{
    public function __construct(
        public readonly ?string $back,
        public readonly ?string $api,
    ) {}
}
```

Create `src/Service/Back/GetVersionsService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\ResponseObject\Versions;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

class GetVersionsService extends AbstractBackService
{
    public function get(): Versions
    {
        try {
            $content = $this->requestContent('GET', '/istration/version');

            /** @var Versions */
            return $this->serializer->deserialize($content, Versions::class, 'json');
        } catch (ExceptionInterface|NotEncodableValueException) {
            return new Versions(null, null);
        }
    }
}
```

- [ ] **Step 4: Run the test to verify it passes**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/Back/GetVersionsServiceTest.php
```

Expected: PASS (3 tests).

---

## Task 8: `pokenini-web` — `VersionsOverviewService` (combines all four bricks)

**Repo:** `/home/renaud/projects/pokenini-web`

**Files:**
- Create: `src/DTO/VersionsOverview.php`
- Create: `src/Service/VersionsOverviewService.php`
- Test: `tests/src/Unit/Service/VersionsOverviewServiceTest.php`

**Interfaces:**
- Consumes: `App\Service\AppVersionService::getVersion(string $filename = 'version'): string` (existing, unchanged), `App\Service\Back\GetVersionsService::get(): Versions` (Task 7), `App\Service\GetResourcesVersionService::get(): ?string` (Task 6).
- Produces:
  - `App\DTO\VersionsOverview` — `final class` with `public readonly ?string $web`, `?string $back`, `?string $api`, `?string $resources`.
  - `App\Service\VersionsOverviewService::get(): VersionsOverview`.

- [ ] **Step 1: Write the failing unit test**

Create `tests/src/Unit/Service/VersionsOverviewServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\ResponseObject\Versions;
use App\Service\AppVersionService;
use App\Service\Back\GetVersionsService;
use App\Service\GetResourcesVersionService;
use App\Service\VersionsOverviewService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(VersionsOverviewService::class)]
final class VersionsOverviewServiceTest extends TestCase
{
    public function testGetCombinesAllFourVersions(): void
    {
        $appVersionService = $this->createMock(AppVersionService::class);
        $appVersionService->method('getVersion')->willReturn('1.2.12');

        $getVersionsService = $this->createMock(GetVersionsService::class);
        $getVersionsService->method('get')->willReturn(new Versions('1.9.9', '1.9.8'));

        $getResourcesVersionService = $this->createMock(GetResourcesVersionService::class);
        $getResourcesVersionService->method('get')->willReturn('1.9.7');

        $service = new VersionsOverviewService($appVersionService, $getVersionsService, $getResourcesVersionService);

        $overview = $service->get();

        $this->assertSame('1.2.12', $overview->web);
        $this->assertSame('1.9.9', $overview->back);
        $this->assertSame('1.9.8', $overview->api);
        $this->assertSame('1.9.7', $overview->resources);
    }

    public function testGetHandlesUnavailableBricks(): void
    {
        $appVersionService = $this->createMock(AppVersionService::class);
        $appVersionService->method('getVersion')->willReturn('1.2.12');

        $getVersionsService = $this->createMock(GetVersionsService::class);
        $getVersionsService->method('get')->willReturn(new Versions(null, null));

        $getResourcesVersionService = $this->createMock(GetResourcesVersionService::class);
        $getResourcesVersionService->method('get')->willReturn(null);

        $service = new VersionsOverviewService($appVersionService, $getVersionsService, $getResourcesVersionService);

        $overview = $service->get();

        $this->assertSame('1.2.12', $overview->web);
        $this->assertNull($overview->back);
        $this->assertNull($overview->api);
        $this->assertNull($overview->resources);
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/VersionsOverviewServiceTest.php
```

Expected: FAIL — classes not found.

- [ ] **Step 3: Implement `VersionsOverview` and `VersionsOverviewService`**

Create `src/DTO/VersionsOverview.php`:

```php
<?php

declare(strict_types=1);

namespace App\DTO;

final class VersionsOverview
{
    public function __construct(
        public readonly ?string $web,
        public readonly ?string $back,
        public readonly ?string $api,
        public readonly ?string $resources,
    ) {}
}
```

Create `src/Service/VersionsOverviewService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\VersionsOverview;
use App\Service\Back\GetVersionsService;

class VersionsOverviewService
{
    public function __construct(
        private readonly AppVersionService $appVersionService,
        private readonly GetVersionsService $getVersionsService,
        private readonly GetResourcesVersionService $getResourcesVersionService,
    ) {}

    public function get(): VersionsOverview
    {
        $versions = $this->getVersionsService->get();

        return new VersionsOverview(
            web: $this->appVersionService->getVersion(),
            back: $versions->back,
            api: $versions->api,
            resources: $this->getResourcesVersionService->get(),
        );
    }
}
```

- [ ] **Step 4: Run the test to verify it passes**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/VersionsOverviewServiceTest.php
```

Expected: PASS (2 tests).

---

## Task 9: `pokenini-web` — admin "Versions" tab (controller, templates, tab link, translations)

**Repo:** `/home/renaud/projects/pokenini-web`

**Files:**
- Create: `src/Controller/AdminVersionsController.php`
- Create: `templates/Admin/versions.html.twig`
- Create: `templates/Admin/_versions.html.twig`
- Modify: `templates/Admin/_tabs.html.twig` (add the "Versions" tab link)
- Modify: `translations/messages+intl-icu.en.yaml`, `translations/messages+intl-icu.fr.yaml`
- Test: `tests/src/Integration/Controller/Admin/AdminVersionsTest.php`

**Interfaces:**
- Consumes: `App\Service\VersionsOverviewService::get(): VersionsOverview` (Task 8).
- Produces: `GET /{_locale}/istration/versions` (route `app_admin_versions`), gated by the same `ROLE_ADMIN` mechanism as every other `/istration/*` route in this repo (no security config change needed — verified by the existing `AdminPageTest`-style 307/403 behavior).

- [ ] **Step 1: Write the failing integration test**

Create `tests/src/Integration/Controller/Admin/AdminVersionsTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Admin;

use App\Controller\AdminVersionsController;
use App\Tests\Utils\GetUserToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(AdminVersionsController::class)]
#[Group('api-mocked-testing')]
final class AdminVersionsTest extends WebTestCase
{
    public function testVersionsTabShowsAllFourBricks(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $crawler = $client->request('GET', '/fr/istration/versions');

        $this->assertResponseStatusCodeSame(200);

        $expectedWebVersion = trim((string) file_get_contents(dirname(__DIR__, 5).'/resources/metadata/version'));

        $this->assertSame($expectedWebVersion, trim($crawler->filter('#versions-row-web td')->eq(1)->text()));
        $this->assertSame('1.9.9', trim($crawler->filter('#versions-row-back td')->eq(1)->text()));
        $this->assertSame('1.9.8', trim($crawler->filter('#versions-row-api td')->eq(1)->text()));
        $this->assertSame('1.9.7', trim($crawler->filter('#versions-row-resources td')->eq(1)->text()));
    }

    public function testVersionsNotConnected(): void
    {
        $client = self::createClient();

        $client->request('GET', '/fr/istration/versions');

        $this->assertResponseStatusCodeSame(307);
    }

    public function testVersionsNotAllowed(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $client->loginUser($user, 'web');

        $client->request('GET', '/fr/istration/versions');

        $this->assertResponseStatusCodeSame(403);
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Admin/AdminVersionsTest.php
```

Expected: FAIL — route/class not found.

- [ ] **Step 3: Implement the controller**

Create `src/Controller/AdminVersionsController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\VersionsOverviewService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/istration')]
final class AdminVersionsController extends AbstractController
{
    public function __construct(
        private readonly VersionsOverviewService $versionsOverviewService,
    ) {}

    #[Route('/versions', methods: ['GET'], name: 'app_admin_versions')]
    public function versions(): Response
    {
        return $this->render(
            'Admin/versions.html.twig',
            [
                'versionsOverview' => $this->versionsOverviewService->get(),
            ]
        );
    }
}
```

- [ ] **Step 4: Create the page shell template**

Create `templates/Admin/versions.html.twig`:

```twig
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
    {% include 'Admin/_tabs.html.twig' with {'page': 'versions', 'active': 'versions'} %}
    {% include 'Admin/_versions.html.twig' %}
  </div>
</div>
{% endblock %}
```

- [ ] **Step 5: Create the versions table partial**

Create `templates/Admin/_versions.html.twig`:

```twig
{% set bricks = [
  {'key': 'web', 'label': 'admin.versions.web', 'version': versionsOverview.web},
  {'key': 'back', 'label': 'admin.versions.back', 'version': versionsOverview.back},
  {'key': 'api', 'label': 'admin.versions.api', 'version': versionsOverview.api},
  {'key': 'resources', 'label': 'admin.versions.resources', 'version': versionsOverview.resources},
] %}
<table class="table" id="versions-table">
  <thead>
    <tr>
      <th>{{ 'admin.versions.brick'|trans }}</th>
      <th>{{ 'admin.versions.version'|trans }}</th>
    </tr>
  </thead>
  <tbody>
    {% for brick in bricks %}
      <tr id="versions-row-{{ brick.key }}">
        <td>{{ brick.label|trans }}</td>
        <td>
          {% if brick.version is not null %}
            {{ brick.version }}
          {% else %}
            <span class="badge text-bg-secondary">{{ 'admin.versions.unavailable'|trans }}</span>
          {% endif %}
        </td>
      </tr>
    {% endfor %}
  </tbody>
</table>
```

- [ ] **Step 6: Add the "Versions" tab link**

In `templates/Admin/_tabs.html.twig`, add a new `<li>` right after the existing Reports `<li>`, before the closing `</ul>`:

```twig
  <li class="nav-item" role="presentation">
    <a class="nav-link{{ 'reports' == active ? ' active' : '' }}" href="{{ path('app_admin_reports') }}">
      {{ 'title.admin_reports'|trans }}
    </a>
  </li>
  <li class="nav-item" role="presentation">
    <a class="nav-link{{ 'versions' == active ? ' active' : '' }}" href="{{ path('app_admin_versions') }}">
      {{ 'title.admin_versions'|trans }}
    </a>
  </li>
</ul>
```

(Only the new `<li>` block and the closing `</ul>` are new — the Reports `<li>` above it is shown for placement context, don't duplicate it.)

- [ ] **Step 7: Add translations**

In `translations/messages+intl-icu.en.yaml`, add `admin_versions: "Versions"` right after the existing `admin_reports: "Reporting"` line in the `title:` block:

```yaml
title:
  home: "Home"
  credits: "Credits"
  report: "Report"
  admin: "Backoffice"
  admin_actions: "Data and caches"
  admin_reports: "Reporting"
  admin_versions: "Versions"
```

And add a new `versions:` block as a sibling of the existing `reports:` block, under the top-level `admin:` key:

```yaml
admin:
  reports:
    cache:
      title: "Cache"
    table:
      hide: "Hide data"
      show: "Show data"
    catch_state_counts_defined_by_trainer:
      # ... existing content unchanged ...
  versions:
    brick: "Brick"
    version: "Version"
    unavailable: "Unavailable"
    web: "Web"
    back: "Back"
    api: "Api"
    resources: "Resources"
```

In `translations/messages+intl-icu.fr.yaml`, add `admin_versions: "Versions"` after `admin_reports: "Rapports"`:

```yaml
title:
  home: "Accueil"
  credits: "Crédits"
  report: "Stats"
  admin: "Administration"
  admin_actions: "Données et caches"
  admin_reports: "Rapports"
  admin_versions: "Versions"
```

And the matching `versions:` block under `admin:`:

```yaml
admin:
  reports:
    cache:
      title: "Cache"
    table:
      hide: "Cacher la donnée"
      show: "Afficher la donnée"
      # ... existing content unchanged ...
  versions:
    brick: "Brique"
    version: "Version"
    unavailable: "Indisponible"
    web: "Web"
    back: "Back"
    api: "Api"
    resources: "Resources"
```

- [ ] **Step 8: Run the test to verify it passes**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Admin/AdminVersionsTest.php
```

Expected: PASS (3 tests). If the Moco text-response fixture from Task 6 Step 7 doesn't work as expected (unlikely, but Moco's plain-text response support is less exercised in this codebase than its JSON/file responses), the test failure will show the actual body Moco returned — adjust the fixture's response type accordingly and re-run.

- [ ] **Step 9: Run full quality and measures gates for `pokenini-web`**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Unit
docker compose exec php php vendor/bin/phpunit tests/src/Integration
make code-quality
make measures
```

Expected: all green, coverage and MSI both 100%. Update PHPStan/Psalm/PHPMD baselines per this repo's `CLAUDE.md` if needed. A browser test is not required (static admin table, no interactive JS), per the design doc's testing section.

- [ ] **Step 10: Manual smoke check**

```bash
make start
```

Then visit `http://localhost/fr/connect/f/c?t=admin` to get an admin session, then `http://localhost/fr/istration/versions` and confirm the four rows render with real values (or "Indisponible" badges if a brick is genuinely unreachable in the local dev stack).
