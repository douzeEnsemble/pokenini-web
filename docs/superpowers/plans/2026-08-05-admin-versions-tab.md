# Admin "Versions" Tab — Enriched Display + Timestamps Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the admin "Versions" tab's plain `<table>` (Web / Back / Api / Resources, version string only) with a Bootstrap `list-group`-based enriched display, and add the date-time each brick's version file was last modified.

**Architecture:** The base "Versions" tab (plain table, version string only, no dates) is **already merged and live on `main`** in all three repos (`pokenini-web` PR #398, `pokenini-back` PR #146, `pokenini-api` PR #338). This plan is a follow-up layered on top of that merged code — not a from-scratch build. Every brick's version now travels alongside a nullable `updatedAt` timestamp, sourced from each repo's own `filemtime()` on its local `resources/metadata/version` file (api, back, web) or from the `Last-Modified` HTTP header on the existing GET request to the `pokenini-resources` static host (resources — that repo has no application code, so nothing changes there). `pokenini-web` gets a single reusable `App\ResponseObject\BrickVersion{version, updatedAt}` value object (following this repo's own precedent set by `ResponseObject\ImagePipelineStatus`, which is deserialized from JSON and passed straight through to Twig with no separate DTO twin) used both for JSON deserialization and as the field type on `DTO\VersionsOverview`.

**Tech Stack:** Symfony 8 / PHP ≥ 8.5 across all three repos, PHPUnit, Moco (HTTP mock server) for pokenini-back's and pokenini-web's integration tests.

## Global Constraints

- This plan spans three independent git repositories, each with its own history, Docker stack, and Makefile:
  - `pokenini-api` — task 1
  - `pokenini-back` — tasks 2-4
  - `pokenini-web` — tasks 5-9 (this repo)
- Every command in every task runs inside that repo's `php` Docker container (`docker compose exec php ...`), from that repo's own directory. `cd` into the right repo before running any command for a given task.
- **Local `main` is stale in all three repos** — `origin/main` already has the merged base feature (squash-merged), but local `main` doesn't. Before starting, in each repo: create a **new** branch off `origin/main` (e.g. `git fetch origin main && git switch -c feature/admin-versions-tab-polish origin/main`) using the `superpowers:using-git-worktrees` skill if isolation is wanted. **Do not** build on top of `pokenini-web`'s currently-checked-out `images-reorg` branch (unrelated work), and **do not** reuse the stale `.worktrees-avt/admin-versions-tab` (`pokenini-web`) or `.worktrees/admin-versions-tab` (`pokenini-back`) worktree directories — both correspond to the already-merged, now-obsolete base-feature branch.
- **Do not create any git commits at any point in this plan** — standing user preference across every project. Leave all changes staged/unstaged for review. Steps that would normally say "commit" are omitted for that reason.
- Every repo requires 100% test coverage and 100% Mutation Score Index (`make measures`) before the work in that repo is considered done, plus a clean `make quality` (or `make code-quality`) run. Run these at the end of each repo's task group.
- `declare(strict_types=1)` at the top of every new/modified PHP file (already present in all files touched here). Every test class is `final`, carries `/** @internal */`, and uses `#[CoversClass(...)]`.
- Response DTOs / ResponseObjects are `final class`es with `public readonly` constructor-promoted properties — never mutable, never with setters.
- Do not hardcode the *current* value of any `resources/metadata/version` file (or its mtime) in a test assertion — it changes on every release. Where a test needs to assert against a repo's own local version file, read that file's content/`filemtime()` at test time instead, exactly as the already-merged tests already do. Moco-fixture values (e.g. `"1.9.9"`, fixed ISO dates) are mock data, not real files, and may be hardcoded freely — this is also the existing convention.
- Symfony's `DateTimeNormalizer` (used both by `#[Serialize]`/`$this->json()` on the way out, and by `$serializer->deserialize()` on the way in) defaults to `\DateTimeInterface::ATOM` (`Y-m-d\TH:i:sP`) — every ISO-8601 string in this plan uses that format.

---

## Task 1: `pokenini-api` — add `updated_at` to the `/version` endpoint

**Repo:** `/home/renaud/projects/pokenini-api`

**Files:**
- Modify: `src/Service/VersionService.php`
- Modify: `src/DTO/Response/VersionResponse.php`
- Modify: `src/Controller/VersionController.php`
- Modify: `tests/src/Unit/Service/VersionServiceTest.php`
- Modify: `tests/src/Integration/Controller/VersionControllerTest.php`

**Interfaces:**
- Produces: `App\Service\VersionService::getUpdatedAt(): ?\DateTimeImmutable` — mirrors the existing `getVersion(): string`'s `is_readable()` guard; returns a `\DateTimeImmutable` built from `filemtime()`, or `null` if the file is missing/unreadable.
- Produces: `GET /version` → `200 application/json` `{"version": "<string>", "updated_at": "<ISO-8601 string>"|null}`.

- [ ] **Step 1: Write the failing unit test**

Add these two test methods to `tests/src/Unit/Service/VersionServiceTest.php` (inside the existing `final class VersionServiceTest`, alongside the existing two tests — don't remove them):

```php
    public function testGetUpdatedAtReturnsFileMtime(): void
    {
        file_put_contents($this->tempDir.'/version', "1.2.12\n");
        $expectedMtime = filemtime($this->tempDir.'/version');
        $service = new VersionService($this->tempDir);

        $this->assertSame($expectedMtime, $service->getUpdatedAt()?->getTimestamp());
    }

    public function testGetUpdatedAtReturnsNullWhenFileMissing(): void
    {
        $service = new VersionService($this->tempDir);

        $this->assertNull($service->getUpdatedAt());
    }
```

- [ ] **Step 2: Run the test to verify it fails**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/VersionServiceTest.php
```

Expected: FAIL — `VersionService::getUpdatedAt()` doesn't exist.

- [ ] **Step 3: Implement `VersionService::getUpdatedAt()`**

Add this method to `src/Service/VersionService.php` (keep `getVersion()` and `FALLBACK_VERSION` unchanged):

```php
    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        $path = $this->metadataDir.'/version';

        if (!is_readable($path)) {
            return null;
        }

        $mtime = filemtime($path);

        if (false === $mtime) {
            return null;
        }

        return (new \DateTimeImmutable())->setTimestamp($mtime);
    }
```

- [ ] **Step 4: Run the test to verify it passes**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/VersionServiceTest.php
```

Expected: PASS (4 tests).

- [ ] **Step 5: Write the failing integration test**

In `tests/src/Integration/Controller/VersionControllerTest.php`, replace `getReturnsVersionFromMetadataFile()` with:

```php
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

        $versionFilePath = dirname(__DIR__, 4).'/resources/metadata/version';
        $expectedVersion = trim((string) file_get_contents($versionFilePath));
        $expectedUpdatedAt = (new \DateTimeImmutable())->setTimestamp((int) filemtime($versionFilePath));

        self::assertJsonStringEqualsJsonString(
            (string) json_encode([
                'version' => $expectedVersion,
                'updated_at' => $expectedUpdatedAt->format(\DateTimeInterface::ATOM),
            ], JSON_THROW_ON_ERROR),
            $content,
        );
    }
```

- [ ] **Step 6: Run the test to verify it fails**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/VersionControllerTest.php
```

Expected: FAIL — response JSON has no `updated_at` key, `VersionResponse` has no such property.

- [ ] **Step 7: Implement `VersionResponse` and `VersionController` changes**

Replace `src/DTO/Response/VersionResponse.php`:

```php
<?php

declare(strict_types=1);

namespace App\DTO\Response;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class VersionResponse
{
    public function __construct(
        public readonly string $version,
        #[SerializedName('updated_at')]
        public readonly ?\DateTimeImmutable $updatedAt,
    ) {}
}
```

In `src/Controller/VersionController.php`, change the `get()` method body:

```php
    public function get(VersionService $service): VersionResponse
    {
        return new VersionResponse($service->getVersion(), $service->getUpdatedAt());
    }
```

- [ ] **Step 8: Run the test to verify it passes**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/VersionControllerTest.php
```

Expected: PASS (3 tests).

- [ ] **Step 9: Run full quality and measures gates for `pokenini-api`**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Unit tests/src/Integration
make code-quality
make measures
```

Expected: all green, coverage and MSI both 100%. Update PHPStan/Psalm/PHPMD baselines per this repo's `CLAUDE.md` if needed.

---

## Task 2: `pokenini-back` — add `getUpdatedAt()` to `LocalVersionService`

**Repo:** `/home/renaud/projects/pokenini-back`

**Files:**
- Modify: `src/Service/LocalVersionService.php`
- Modify: `tests/src/Unit/Service/LocalVersionServiceTest.php`

**Interfaces:**
- Produces: `App\Service\LocalVersionService::getUpdatedAt(): ?\DateTimeImmutable` — same shape as Task 1's `VersionService::getUpdatedAt()`, using this class's existing `is_file()` guard instead of `is_readable()`.

- [ ] **Step 1: Write the failing unit test**

Add to `tests/src/Unit/Service/LocalVersionServiceTest.php` (alongside the existing two tests):

```php
    public function testGetUpdatedAtReturnsFileMtime(): void
    {
        file_put_contents($this->tempDir.'/version', "1.2.12\n");
        $expectedMtime = filemtime($this->tempDir.'/version');
        $service = new LocalVersionService($this->tempDir);

        $this->assertSame($expectedMtime, $service->getUpdatedAt()?->getTimestamp());
    }

    public function testGetUpdatedAtReturnsNullWhenFileMissing(): void
    {
        $service = new LocalVersionService($this->tempDir);

        $this->assertNull($service->getUpdatedAt());
    }
```

- [ ] **Step 2: Run the test to verify it fails**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/LocalVersionServiceTest.php
```

Expected: FAIL — `LocalVersionService::getUpdatedAt()` doesn't exist.

- [ ] **Step 3: Implement `LocalVersionService::getUpdatedAt()`**

Add this method to `src/Service/LocalVersionService.php` (keep `getVersion()` and `FALLBACK_VERSION` unchanged):

```php
    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        $path = $this->metadataDir.'/version';

        if (!is_file($path)) {
            return null;
        }

        $mtime = filemtime($path);

        if (false === $mtime) {
            return null;
        }

        return (new \DateTimeImmutable())->setTimestamp($mtime);
    }
```

- [ ] **Step 4: Run the test to verify it passes**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/LocalVersionServiceTest.php
```

Expected: PASS (4 tests).

---

## Task 3: `pokenini-back` — `GetVersionApiService` returns `updated_at` too

**Repo:** `/home/renaud/projects/pokenini-back`

**Files:**
- Modify: `src/Service/Api/GetVersionApiService.php`
- Modify: `tests/src/Unit/Service/Api/GetVersionApiServiceTest.php`
- Modify: `tests/resources/moco/Api/moco.json`

**Interfaces:**
- Consumes: `App\Service\LocalVersionService::getUpdatedAt()` (Task 2) — not directly, but the api's `/version` response (Task 1) now includes `updated_at`.
- Produces: `App\Service\Api\GetVersionApiService::get(): array{version: ?string, updated_at: ?\DateTimeImmutable}` (was `?string`) — decodes both fields from the api's JSON response; on any transport/HTTP/JSON-decode failure, returns `['version' => null, 'updated_at' => null]` instead of throwing (unchanged failure behaviour, changed shape).

- [ ] **Step 1: Write the failing unit test**

Replace the contents of `tests/src/Unit/Service/Api/GetVersionApiServiceTest.php`:

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
    public function testGetReturnsVersionAndUpdatedAtFromDecodedBody(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getContent')->willReturn('{"version":"1.2.12","updated_at":"2026-08-05T09:12:00+00:00"}');

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

        $result = $this->getService($client)->get();

        $this->assertSame('1.2.12', $result['version']);
        $this->assertSame('2026-08-05T09:12:00+00:00', $result['updated_at']?->format(\DateTimeInterface::ATOM));
    }

    public function testGetHandlesNullUpdatedAtField(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getContent')->willReturn('{"version":"1.2.12","updated_at":null}');

        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')->willReturn($response);

        $result = $this->getService($client)->get();

        $this->assertSame('1.2.12', $result['version']);
        $this->assertNull($result['updated_at']);
    }

    public function testGetReturnsNullFieldsOnTransportError(): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')->willThrowException(
            $this->createMock(TransportException::class)
        );

        $result = $this->getService($client)->get();

        $this->assertNull($result['version']);
        $this->assertNull($result['updated_at']);
    }

    public function testGetReturnsNullFieldsOnHttpError(): void
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

        $result = $this->getService($client)->get();

        $this->assertNull($result['version']);
        $this->assertNull($result['updated_at']);
    }

    public function testGetReturnsNullFieldsOnMalformedJson(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getContent')->willReturn('not json');

        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')->willReturn($response);

        $result = $this->getService($client)->get();

        $this->assertNull($result['version']);
        $this->assertNull($result['updated_at']);
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

Expected: FAIL — `get()` still returns `?string`, `$result['version']` is a type error / the old code returns the bare string.

- [ ] **Step 3: Implement the new `GetVersionApiService::get()`**

Replace `src/Service/Api/GetVersionApiService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Utils\JsonDecoder;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

class GetVersionApiService extends AbstractApiService
{
    /**
     * @return array{version: ?string, updated_at: ?\DateTimeImmutable}
     */
    public function get(): array
    {
        try {
            $content = $this->requestContent('GET', '/version');

            /** @var array{version: string, updated_at: ?string} $decoded */
            $decoded = JsonDecoder::decode($content);

            return [
                'version' => $decoded['version'],
                'updated_at' => null !== $decoded['updated_at'] ? new \DateTimeImmutable($decoded['updated_at']) : null,
            ];
        } catch (ExceptionInterface|\JsonException) {
            return [
                'version' => null,
                'updated_at' => null,
            ];
        }
    }
}
```

- [ ] **Step 4: Run the test to verify it passes**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/Api/GetVersionApiServiceTest.php
```

Expected: PASS (5 tests).

- [ ] **Step 5: Update the Moco mock rule for the api's `/version` endpoint**

In `tests/resources/moco/Api/moco.json`, find the existing rule for `"uri": "/version"` (response `{"version": "1.9.8"}`) and replace its `response.json` value:

```json
    "response": {
      "json": "{\"version\": \"1.9.8\", \"updated_at\": \"2026-01-01T00:00:00+00:00\"}"
    }
```

(Keep the `request` block — `uri`, `headers` — exactly as-is; only the `response.json` string changes.)

---

## Task 4: `pokenini-back` — `AdminVersionController` returns nested `{version, updated_at}` per brick

**Repo:** `/home/renaud/projects/pokenini-back`

**Files:**
- Modify: `src/Controller/Admin/AdminVersionController.php`
- Modify: `tests/src/Integration/Admin/VersionsTest.php`

**Interfaces:**
- Consumes: `App\Service\LocalVersionService::getUpdatedAt()` (Task 2), `App\Service\Api\GetVersionApiService::get(): array{version: ?string, updated_at: ?\DateTimeImmutable}` (Task 3).
- Produces: `GET /istration/version` → `200 application/json`
  `{"back": {"version": "<string>", "updated_at": "<ISO-8601>"|null}, "api": {"version": "<string>"|null, "updated_at": "<ISO-8601>"|null}}`.
  Still never returns a non-2xx status itself.

- [ ] **Step 1: Write the failing integration test**

Replace `testGetVersion()` in `tests/src/Integration/Admin/VersionsTest.php` (keep `testGetVersionNonAuthenticated()` unchanged):

```php
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

        $versionFilePath = dirname(__DIR__, 4).'/resources/metadata/version';
        $expectedBackVersion = trim((string) file_get_contents($versionFilePath));
        $expectedBackUpdatedAt = (new \DateTimeImmutable())->setTimestamp((int) filemtime($versionFilePath));

        self::assertJsonStringEqualsJsonString(
            (string) json_encode([
                'back' => [
                    'version' => $expectedBackVersion,
                    'updated_at' => $expectedBackUpdatedAt->format(\DateTimeInterface::ATOM),
                ],
                'api' => [
                    'version' => '1.9.8',
                    'updated_at' => '2026-01-01T00:00:00+00:00',
                ],
            ], JSON_THROW_ON_ERROR),
            $content,
        );
    }
```

- [ ] **Step 2: Run the test to verify it fails**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Integration/Admin/VersionsTest.php
```

Expected: FAIL — response is still `{"back": "<string>", "api": "<string>"}` (flat), not nested.

- [ ] **Step 3: Implement the new `AdminVersionController::version()`**

Replace the `version()` method body in `src/Controller/Admin/AdminVersionController.php` (constructor and route attribute unchanged):

```php
    #[Route('/version', methods: ['GET'])]
    public function version(): JsonResponse
    {
        return $this->json([
            'back' => [
                'version' => $this->localVersionService->getVersion(),
                'updated_at' => $this->localVersionService->getUpdatedAt(),
            ],
            'api' => $this->getVersionApiService->get(),
        ]);
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

Expected: all green, coverage and MSI both 100%. Also run `make check-moco-refs` (or the equivalent `infra-quality` target) so the modified Moco fixture doesn't get flagged as stale. Update PHPStan/Psalm/PHPMD baselines per this repo's `CLAUDE.md` if needed.

---

## Task 5: `pokenini-web` — add `getUpdatedAt()` to `AppVersionService`

**Repo:** `/home/renaud/projects/pokenini-web`

**Files:**
- Modify: `src/Service/AppVersionService.php`
- Modify: `tests/src/Unit/Service/AppVersionServiceTest.php`

**Interfaces:**
- Produces: `App\Service\AppVersionService::getUpdatedAt(string $filename = 'version'): ?\DateTimeImmutable` — same `filemtime()` this class already computes for its cache key, exposed directly; `null` if the file doesn't exist. `getVersion()` itself is completely unchanged.

- [ ] **Step 1: Write the failing unit test**

Add to `tests/src/Unit/Service/AppVersionServiceTest.php` (alongside the existing tests, same file/class):

```php
    public function testGetUpdatedAtReturnsFileMtime(): void
    {
        $service = new AppVersionService(dirname(__DIR__, 4), new ArrayAdapter(), new Filesystem());

        $versionFilePath = dirname(__DIR__, 4).'/resources/metadata/version';
        $expectedMtime = filemtime($versionFilePath);

        $this->assertSame($expectedMtime, $service->getUpdatedAt()?->getTimestamp());
    }

    public function testGetUpdatedAtReturnsNullForMissingFile(): void
    {
        $service = new AppVersionService(dirname(__DIR__, 4), new ArrayAdapter(), new Filesystem());

        $this->assertNull($service->getUpdatedAt('non_existent_file'));
    }
```

- [ ] **Step 2: Run the test to verify it fails**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/AppVersionServiceTest.php
```

Expected: FAIL — `AppVersionService::getUpdatedAt()` doesn't exist.

- [ ] **Step 3: Implement `AppVersionService::getUpdatedAt()`**

Add this method to `src/Service/AppVersionService.php` (keep `getVersion()` byte-for-byte unchanged):

```php
    public function getUpdatedAt(string $filename = 'version'): ?\DateTimeImmutable
    {
        $filePath = $this->projectDir.'/resources/metadata/'.$filename;

        if (!$this->filesystem->exists($filePath)) {
            return null;
        }

        $mtime = filemtime($filePath);

        if (false === $mtime) {
            return null;
        }

        return (new \DateTimeImmutable())->setTimestamp($mtime);
    }
```

- [ ] **Step 4: Run the test to verify it passes**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/AppVersionServiceTest.php
```

Expected: PASS (10 tests) — the 8 already-merged tests plus these 2 new ones.

---

## Task 6: `pokenini-web` — `ResponseObject\BrickVersion` and updated `Versions`/`GetVersionsService`

**Repo:** `/home/renaud/projects/pokenini-web`

**Files:**
- Create: `src/ResponseObject/BrickVersion.php`
- Modify: `src/ResponseObject/Versions.php`
- Modify: `src/Service/Back/GetVersionsService.php`
- Modify: `tests/src/Unit/Service/Back/GetVersionsServiceTest.php`

**Interfaces:**
- Produces: `App\ResponseObject\BrickVersion` — `final class` with `public readonly ?string $version` and `#[SerializedName('updated_at')] public readonly ?\DateTimeImmutable $updatedAt`. This is the **single** shared shape used both for JSON deserialization (here) and, in Task 8, as the field type on `DTO\VersionsOverview` — following this repo's existing `ResponseObject\ImagePipelineStatus` precedent (deserialized once, then passed straight through Controller → Twig, no separate DTO twin).
- Produces: `App\ResponseObject\Versions` — `back: BrickVersion`, `api: BrickVersion` (was `?string`, `?string`).
- Produces: `App\Service\Back\GetVersionsService::get(): Versions` — unchanged control flow; failure branch now returns `new Versions(new BrickVersion(null, null), new BrickVersion(null, null))`.

- [ ] **Step 1: Write the failing unit test**

Replace `tests/src/Unit/Service/Back/GetVersionsServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\Exception\NoLoggedUserException;
use App\Security\UserTokenServiceInterface;
use App\Service\Back\GetVersionsService;
use App\Tests\Utils\RealSerializerFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\Exception\TransportException;
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
        $versions = $this->getServiceWithResponseBody(
            '{"back":{"version":"1.2.12","updated_at":"2026-08-05T09:12:00+00:00"},"api":{"version":"1.2.13","updated_at":"2026-08-04T21:47:00+00:00"}}'
        )->get();

        $this->assertSame('1.2.12', $versions->back->version);
        $this->assertSame('2026-08-05T09:12:00+00:00', $versions->back->updatedAt?->format(\DateTimeInterface::ATOM));
        $this->assertSame('1.2.13', $versions->api->version);
        $this->assertSame('2026-08-04T21:47:00+00:00', $versions->api->updatedAt?->format(\DateTimeInterface::ATOM));
    }

    public function testGetHandlesNullApiField(): void
    {
        $versions = $this->getServiceWithResponseBody(
            '{"back":{"version":"1.2.12","updated_at":"2026-08-05T09:12:00+00:00"},"api":{"version":null,"updated_at":null}}'
        )->get();

        $this->assertSame('1.2.12', $versions->back->version);
        $this->assertNull($versions->api->version);
        $this->assertNull($versions->api->updatedAt);
    }

    public function testGetReturnsNullFieldsOnTransportError(): void
    {
        $client = $this->createStub(HttpClientInterface::class);
        $client->method('request')->willThrowException(
            $this->createStub(TransportException::class)
        );

        $versions = $this->buildService($client)->get();

        $this->assertNull($versions->back->version);
        $this->assertNull($versions->back->updatedAt);
        $this->assertNull($versions->api->version);
        $this->assertNull($versions->api->updatedAt);
    }

    /**
     * Reproduces the real production failure mode: the container-wired serializer (with a
     * PropertyInfo type extractor, as configured in config/packages/serializer.yaml) throws
     * Symfony\Component\Serializer\Exception\NotNormalizableValueException — not \TypeError —
     * when a field's JSON type doesn't match the declared PHP type.
     */
    public function testGetReturnsNullFieldsOnNotNormalizableValue(): void
    {
        $versions = $this->getServiceWithResponseBody('{"back":{"x":1},"api":{"version":"1.0","updated_at":null}}')->get();

        $this->assertNull($versions->back->version);
        $this->assertNull($versions->api->version);
    }

    private function getServiceWithResponseBody(string $responseBody): GetVersionsService
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getContent')->willReturn($responseBody);

        $client = $this->createStub(HttpClientInterface::class);
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
            RealSerializerFactory::create(),
        );
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/Back/GetVersionsServiceTest.php
```

Expected: FAIL — `App\ResponseObject\BrickVersion` doesn't exist, `Versions::$back`/`$api` are still `?string`.

- [ ] **Step 3: Implement `BrickVersion`, update `Versions` and `GetVersionsService`**

Create `src/ResponseObject/BrickVersion.php`:

```php
<?php

declare(strict_types=1);

namespace App\ResponseObject;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class BrickVersion
{
    public function __construct(
        public readonly ?string $version,
        #[SerializedName('updated_at')]
        public readonly ?\DateTimeImmutable $updatedAt,
    ) {}
}
```

Replace `src/ResponseObject/Versions.php`:

```php
<?php

declare(strict_types=1);

namespace App\ResponseObject;

final class Versions
{
    public function __construct(
        public readonly BrickVersion $back,
        public readonly BrickVersion $api,
    ) {}
}
```

Replace `src/Service/Back/GetVersionsService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\ResponseObject\BrickVersion;
use App\ResponseObject\Versions;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpExceptionInterface;

class GetVersionsService extends AbstractBackService
{
    public function get(): Versions
    {
        try {
            $content = $this->requestContent('GET', '/istration/version');

            /** @var Versions */
            return $this->serializer->deserialize($content, Versions::class, 'json');
        } catch (HttpExceptionInterface|SerializerExceptionInterface) {
            return new Versions(new BrickVersion(null, null), new BrickVersion(null, null));
        }
    }
}
```

- [ ] **Step 4: Run the test to verify it passes**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/Back/GetVersionsServiceTest.php
```

Expected: PASS (4 tests).

---

## Task 7: `pokenini-web` — `GetResourcesVersionService` reads the `Last-Modified` header

**Repo:** `/home/renaud/projects/pokenini-web`

**Files:**
- Modify: `src/Service/GetResourcesVersionService.php`
- Modify: `tests/src/Unit/Service/GetResourcesVersionServiceTest.php`

**Interfaces:**
- Consumes: `App\ResponseObject\BrickVersion` (Task 6).
- Produces: `App\Service\GetResourcesVersionService::get(): BrickVersion` (was `?string`) — the body (trimmed) becomes `version`; the `Last-Modified` response header (if present) becomes `updatedAt`, parsed via `new \DateTimeImmutable($headerValue)` (PHP's `DateTimeImmutable` constructor parses RFC 7231 dates like `"Wed, 05 Aug 2026 09:12:00 GMT"` natively). On any transport/HTTP failure: `BrickVersion(null, null)`, logging exactly as today.

- [ ] **Step 1: Write the failing unit test**

Replace `tests/src/Unit/Service/GetResourcesVersionServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\GetResourcesVersionService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
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
    public function testGetReturnsVersionAndUpdatedAtFromLastModifiedHeader(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getContent')->willReturn("1.9.7\n");
        $response->method('getHeaders')->willReturn(['last-modified' => ['Wed, 05 Aug 2026 09:12:00 GMT']]);

        $client = $this->createMock(HttpClientInterface::class);
        $client
            ->expects($this->once())
            ->method('request')
            ->with('GET', 'https://resources.domain/resources/metadata/version')
            ->willReturn($response)
        ;

        $service = new GetResourcesVersionService($this->createStub(LoggerInterface::class), $client, 'https://resources.domain/resources/metadata/version');

        $brickVersion = $service->get();

        $this->assertSame('1.9.7', $brickVersion->version);
        $this->assertSame('2026-08-05T09:12:00+00:00', $brickVersion->updatedAt?->format(\DateTimeInterface::ATOM));
    }

    public function testGetReturnsNullUpdatedAtWhenLastModifiedHeaderAbsent(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getContent')->willReturn('1.9.7');
        $response->method('getHeaders')->willReturn([]);

        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')->willReturn($response);

        $service = new GetResourcesVersionService($this->createStub(LoggerInterface::class), $client, 'https://resources.domain/resources/metadata/version');

        $brickVersion = $service->get();

        $this->assertSame('1.9.7', $brickVersion->version);
        $this->assertNull($brickVersion->updatedAt);
    }

    public function testGetReturnsNullBrickVersionOnTransportError(): void
    {
        $exception = $this->createStub(TransportException::class);

        $client = $this->createStub(HttpClientInterface::class);
        $client->method('request')->willThrowException($exception);

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('warning')
            ->with('Failed to fetch resources version', ['exception' => $exception])
        ;

        $service = new GetResourcesVersionService($logger, $client, 'https://resources.domain/resources/metadata/version');

        $brickVersion = $service->get();

        $this->assertNull($brickVersion->version);
        $this->assertNull($brickVersion->updatedAt);
    }

    public function testGetReturnsNullBrickVersionOnHttpError(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('getContent')
            ->willThrowException($this->createStub(ClientExceptionInterface::class))
        ;

        $client = $this->createStub(HttpClientInterface::class);
        $client->method('request')->willReturn($response);

        $service = new GetResourcesVersionService($this->createStub(LoggerInterface::class), $client, 'https://resources.domain/resources/metadata/version');

        $brickVersion = $service->get();

        $this->assertNull($brickVersion->version);
        $this->assertNull($brickVersion->updatedAt);
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/GetResourcesVersionServiceTest.php
```

Expected: FAIL — `get()` still returns `?string`, no `->version`/`->updatedAt` properties.

- [ ] **Step 3: Implement the new `GetResourcesVersionService::get()`**

Replace `src/Service/GetResourcesVersionService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Service;

use App\ResponseObject\BrickVersion;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GetResourcesVersionService
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly HttpClientInterface $client,
        private readonly string $resourcesVersionUrl,
    ) {}

    public function get(): BrickVersion
    {
        try {
            $response = $this->client->request('GET', $this->resourcesVersionUrl);
            $content = $response->getContent();
            $lastModified = $response->getHeaders()['last-modified'][0] ?? null;
        } catch (ExceptionInterface $exception) {
            $this->logger->warning('Failed to fetch resources version', ['exception' => $exception]);

            return new BrickVersion(null, null);
        }

        return new BrickVersion(
            trim($content),
            null !== $lastModified ? new \DateTimeImmutable($lastModified) : null,
        );
    }
}
```

- [ ] **Step 4: Run the test to verify it passes**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/GetResourcesVersionServiceTest.php
```

Expected: PASS (4 tests).

---

## Task 8: `pokenini-web` — `VersionsOverview` and `VersionsOverviewService` use `BrickVersion`

**Repo:** `/home/renaud/projects/pokenini-web`

**Files:**
- Modify: `src/DTO/VersionsOverview.php`
- Modify: `src/Service/VersionsOverviewService.php`
- Modify: `tests/src/Unit/Service/VersionsOverviewServiceTest.php`

**Interfaces:**
- Consumes: `App\Service\AppVersionService::getVersion()`/`getUpdatedAt()` (Task 5), `App\Service\Back\GetVersionsService::get(): Versions` (Task 6), `App\Service\GetResourcesVersionService::get(): BrickVersion` (Task 7).
- Produces: `App\DTO\VersionsOverview` — four `App\ResponseObject\BrickVersion` fields: `web`, `back`, `api`, `resources` (was four `?string` fields).
- Produces: `App\Service\VersionsOverviewService::get(): VersionsOverview` — same orchestration role, same single entry point for the controller.

- [ ] **Step 1: Write the failing unit test**

Replace `tests/src/Unit/Service/VersionsOverviewServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\ResponseObject\BrickVersion;
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
        $webUpdatedAt = new \DateTimeImmutable('2026-08-05T09:12:00+00:00');
        $backUpdatedAt = new \DateTimeImmutable('2026-08-04T21:47:00+00:00');
        $apiUpdatedAt = new \DateTimeImmutable('2026-08-03T15:03:00+00:00');
        $resourcesUpdatedAt = new \DateTimeImmutable('2026-08-02T10:00:00+00:00');

        $appVersionService = $this->createStub(AppVersionService::class);
        $appVersionService->method('getVersion')->willReturn('1.2.12');
        $appVersionService->method('getUpdatedAt')->willReturn($webUpdatedAt);

        $getVersionsService = $this->createStub(GetVersionsService::class);
        $getVersionsService->method('get')->willReturn(new Versions(
            new BrickVersion('1.9.9', $backUpdatedAt),
            new BrickVersion('1.9.8', $apiUpdatedAt),
        ));

        $getResourcesVersionService = $this->createStub(GetResourcesVersionService::class);
        $getResourcesVersionService->method('get')->willReturn(new BrickVersion('1.9.7', $resourcesUpdatedAt));

        $service = new VersionsOverviewService($appVersionService, $getVersionsService, $getResourcesVersionService);

        $overview = $service->get();

        $this->assertSame('1.2.12', $overview->web->version);
        $this->assertSame($webUpdatedAt, $overview->web->updatedAt);
        $this->assertSame('1.9.9', $overview->back->version);
        $this->assertSame($backUpdatedAt, $overview->back->updatedAt);
        $this->assertSame('1.9.8', $overview->api->version);
        $this->assertSame($apiUpdatedAt, $overview->api->updatedAt);
        $this->assertSame('1.9.7', $overview->resources->version);
        $this->assertSame($resourcesUpdatedAt, $overview->resources->updatedAt);
    }

    public function testGetHandlesUnavailableBricks(): void
    {
        $appVersionService = $this->createStub(AppVersionService::class);
        $appVersionService->method('getVersion')->willReturn('1.2.12');
        $appVersionService->method('getUpdatedAt')->willReturn(null);

        $getVersionsService = $this->createStub(GetVersionsService::class);
        $getVersionsService->method('get')->willReturn(new Versions(
            new BrickVersion(null, null),
            new BrickVersion(null, null),
        ));

        $getResourcesVersionService = $this->createStub(GetResourcesVersionService::class);
        $getResourcesVersionService->method('get')->willReturn(new BrickVersion(null, null));

        $service = new VersionsOverviewService($appVersionService, $getVersionsService, $getResourcesVersionService);

        $overview = $service->get();

        $this->assertSame('1.2.12', $overview->web->version);
        $this->assertNull($overview->web->updatedAt);
        $this->assertNull($overview->back->version);
        $this->assertNull($overview->api->version);
        $this->assertNull($overview->resources->version);
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/VersionsOverviewServiceTest.php
```

Expected: FAIL — `VersionsOverview::$web` etc. are still `?string`, `$overview->web->version` errors.

- [ ] **Step 3: Implement the new `VersionsOverview` and `VersionsOverviewService`**

Replace `src/DTO/VersionsOverview.php`:

```php
<?php

declare(strict_types=1);

namespace App\DTO;

use App\ResponseObject\BrickVersion;

final class VersionsOverview
{
    public function __construct(
        public readonly BrickVersion $web,
        public readonly BrickVersion $back,
        public readonly BrickVersion $api,
        public readonly BrickVersion $resources,
    ) {}
}
```

Replace `src/Service/VersionsOverviewService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\VersionsOverview;
use App\ResponseObject\BrickVersion;
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
            web: new BrickVersion($this->appVersionService->getVersion(), $this->appVersionService->getUpdatedAt()),
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

## Task 9: `pokenini-web` — enriched-list template, Moco fixtures, integration test

**Repo:** `/home/renaud/projects/pokenini-web`

**Files:**
- Modify: `templates/Admin/_versions.html.twig`
- Modify: `tests/resources/moco/Back/moco.json`
- Modify: `tests/src/Integration/Controller/Admin/AdminVersionsTest.php`

**Interfaces:**
- Consumes: `App\DTO\VersionsOverview` (Task 8), exposed to the template as the `versionsOverview` variable (unchanged — `AdminVersionsController` and `versions.html.twig` need no changes at all).

- [ ] **Step 1: Write the failing integration test**

Replace `tests/src/Integration/Controller/Admin/AdminVersionsTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Admin;

use App\Controller\AdminVersionsController;
use App\DTO\VersionsOverview;
use App\ResponseObject\BrickVersion;
use App\Service\VersionsOverviewService;
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

        $versionFilePath = dirname(__DIR__, 5).'/resources/metadata/version';
        $expectedWebVersion = trim((string) file_get_contents($versionFilePath));
        $expectedWebUpdatedAt = (new \DateTimeImmutable())
            ->setTimestamp((int) filemtime($versionFilePath))
            ->setTimezone(new \DateTimeZone('Europe/Paris'))
            ->format('d/m/Y \\à H:i')
        ;
        $expectedBackUpdatedAt = (new \DateTimeImmutable('2026-08-04T21:47:00+00:00'))
            ->setTimezone(new \DateTimeZone('Europe/Paris'))
            ->format('d/m/Y \\à H:i')
        ;
        $expectedApiUpdatedAt = (new \DateTimeImmutable('2026-01-01T00:00:00+00:00'))
            ->setTimezone(new \DateTimeZone('Europe/Paris'))
            ->format('d/m/Y \\à H:i')
        ;
        $expectedResourcesUpdatedAt = (new \DateTimeImmutable('Wed, 05 Aug 2026 09:12:00 GMT'))
            ->setTimezone(new \DateTimeZone('Europe/Paris'))
            ->format('d/m/Y \\à H:i')
        ;

        $this->assertSame($expectedWebVersion, trim($crawler->filter('#versions-row-web .versions-version')->text()));
        $this->assertSame($expectedWebUpdatedAt, trim($crawler->filter('#versions-row-web .versions-date')->text()));

        $this->assertSame('1.9.9', trim($crawler->filter('#versions-row-back .versions-version')->text()));
        $this->assertSame($expectedBackUpdatedAt, trim($crawler->filter('#versions-row-back .versions-date')->text()));

        $this->assertSame('1.9.8', trim($crawler->filter('#versions-row-api .versions-version')->text()));
        $this->assertSame($expectedApiUpdatedAt, trim($crawler->filter('#versions-row-api .versions-date')->text()));

        $this->assertSame('1.9.7', trim($crawler->filter('#versions-row-resources .versions-version')->text()));
        $this->assertSame($expectedResourcesUpdatedAt, trim($crawler->filter('#versions-row-resources .versions-date')->text()));
    }

    public function testVersionsTabShowsUnavailableBadgeWhenBricksCannotBeFetched(): void
    {
        $client = self::createClient();

        $user = GetUserToken::getFakeUserToken('8764532', 'TestProvider');
        $user->addAdminRole();
        $client->loginUser($user, 'web');

        $versionFilePath = dirname(__DIR__, 5).'/resources/metadata/version';
        $expectedWebVersion = trim((string) file_get_contents($versionFilePath));
        $expectedWebUpdatedAt = (new \DateTimeImmutable())->setTimestamp((int) filemtime($versionFilePath));

        $versionsOverviewService = $this->createStub(VersionsOverviewService::class);
        $versionsOverviewService->method('get')->willReturn(
            new VersionsOverview(
                web: new BrickVersion($expectedWebVersion, $expectedWebUpdatedAt),
                back: new BrickVersion(null, null),
                api: new BrickVersion(null, null),
                resources: new BrickVersion(null, null),
            )
        );
        self::getContainer()->set(VersionsOverviewService::class, $versionsOverviewService);

        $crawler = $client->request('GET', '/fr/istration/versions');

        $this->assertResponseStatusCodeSame(200);

        $this->assertSame($expectedWebVersion, trim($crawler->filter('#versions-row-web .versions-version')->text()));
        $this->assertSame('Indisponible', trim($crawler->filter('#versions-row-back .versions-version')->text()));
        $this->assertSame('', trim($crawler->filter('#versions-row-back .versions-date')->text()));
        $this->assertSame('Indisponible', trim($crawler->filter('#versions-row-api .versions-version')->text()));
        $this->assertSame('', trim($crawler->filter('#versions-row-api .versions-date')->text()));
        $this->assertSame('Indisponible', trim($crawler->filter('#versions-row-resources .versions-version')->text()));
        $this->assertSame('', trim($crawler->filter('#versions-row-resources .versions-date')->text()));
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

- [ ] **Step 2: Update the Moco fixtures**

In `tests/resources/moco/Back/moco.json`, find the rule matching `"uri": {"match": "/istration/version"}` and replace its `response.json`:

```json
    "response": {
      "status": "200",
      "json": {
        "back": {
          "version": "1.9.9",
          "updated_at": "2026-08-04T21:47:00+00:00"
        },
        "api": {
          "version": "1.9.8",
          "updated_at": "2026-01-01T00:00:00+00:00"
        }
      }
    }
```

Find the rule matching `"uri": "/resources/metadata/version"` and add a `headers` block to its response:

```json
{
  "request": {
    "uri": "/resources/metadata/version"
  },
  "response": {
    "text": "1.9.7",
    "headers": {
      "Last-Modified": "Wed, 05 Aug 2026 09:12:00 GMT"
    }
  }
}
```

If Moco rejects a bare string for a response header value (some Moco versions expect an array), the test run in Step 4 will surface this as an assertion failure with the actual body/headers Moco returned — switch to whatever array form Moco's error message implies and re-run.

- [ ] **Step 3: Run the test to verify it fails**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Admin/AdminVersionsTest.php
```

Expected: FAIL — `templates/Admin/_versions.html.twig` still renders a `<table>` with plain `<td>` cells; `.versions-version`/`.versions-date` selectors match nothing.

- [ ] **Step 4: Rewrite the template**

Replace `templates/Admin/_versions.html.twig`:

```twig
{% set bricks = [
  {'key': 'web', 'label': 'admin.versions.web', 'color': 'primary', 'brickVersion': versionsOverview.web},
  {'key': 'back', 'label': 'admin.versions.back', 'color': 'info', 'brickVersion': versionsOverview.back},
  {'key': 'api', 'label': 'admin.versions.api', 'color': 'warning', 'brickVersion': versionsOverview.api},
  {'key': 'resources', 'label': 'admin.versions.resources', 'color': 'success', 'brickVersion': versionsOverview.resources},
] %}
<div class="list-group" id="versions-list">
  {% for brick in bricks %}
    <div class="list-group-item d-flex align-items-center gap-3" id="versions-row-{{ brick.key }}">
      <span class="badge rounded-pill text-bg-{{ brick.color }}">{{ brick.label|trans|slice(0, 1)|upper }}</span>
      <span class="fw-semibold">{{ brick.label|trans }}</span>
      <span class="versions-version fw-bold ms-auto">
        {% if brick.brickVersion.version is not null %}
          {{ brick.brickVersion.version }}
        {% else %}
          <span class="badge text-bg-secondary">{{ 'admin.versions.unavailable'|trans }}</span>
        {% endif %}
      </span>
      <span class="versions-date text-body-secondary small">
        {% if brick.brickVersion.updatedAt is not null %}
          {{ brick.brickVersion.updatedAt|date('d/m/Y \\à H:i', 'Europe/Paris') }}
        {% endif %}
      </span>
    </div>
  {% endfor %}
</div>
```

Note: `templates/Admin/versions.html.twig` (the page shell) and `templates/Admin/_tabs.html.twig` need **no changes** — the shell already includes `_versions.html.twig` and passes no extra variables it doesn't already have.

- [ ] **Step 5: Run the test to verify it passes**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Admin/AdminVersionsTest.php
```

Expected: PASS (4 tests). If date assertions fail, re-check Step 2's Moco fixture dates were saved exactly as written (a typo'd ISO string is the most likely cause) and that the template's `date` filter format string matches `'d/m/Y \\à H:i'` exactly (the `\\à` escapes the literal "à" so Twig doesn't try to parse it as a format character).

- [ ] **Step 6: Run full quality and measures gates for `pokenini-web`**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Unit
docker compose exec php php vendor/bin/phpunit tests/src/Integration
make code-quality
make measures
```

Expected: all green, coverage and MSI both 100%. Update PHPStan/Psalm/PHPMD baselines per this repo's `CLAUDE.md` if needed. A browser test is not required (static admin content, no interactive JS) — same as the already-merged base feature.

- [ ] **Step 7: Manual smoke check**

```bash
make start
```

Visit `http://localhost/fr/connect/f/c?t=admin` to get an admin session, then `http://localhost/fr/istration/versions` and confirm all four rows render as a Bootstrap list group with coloured initial badges, bold version numbers, and a right-aligned date per row (or "Indisponible" badges with no date, for any brick genuinely unreachable in the local dev stack).
