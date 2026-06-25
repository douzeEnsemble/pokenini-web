# ActionLog Symfony Serializer Refactoring — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [x]`) syntax for tracking.

**Goal:** Remplacer `ActionLog::createFromArray` par la désérialisation Symfony pour éliminer les casts manuels, les `@psalm-suppress RiskyCast` et les `/** @var */` forcés.

**Architecture:** `ActionLog` conserve son emplacement dans `src/DTO/`, son constructeur devient `public` et chaque paramètre reçoit `#[SerializedName]`. `GetActionLogsService` utilise `$this->serializer->denormalize()` déjà disponible via `AbstractBackService`.

**Tech Stack:** PHP 8.4, Symfony Serializer (`ObjectNormalizer` + `DateTimeNormalizer` + `ClassMetadataFactory`), PHPUnit 13.

---

## Fichiers touchés

| Action   | Fichier |
|----------|---------|
| Modifier | `src/DTO/ActionLog.php` |
| Modifier | `src/Service/Back/GetActionLogsService.php` |
| Modifier | `tests/src/Unit/Service/Back/GetActionLogsServiceTest.php` |
| Supprimer | `tests/src/Unit/DTO/ActionLogTest.php` |

---

## Task 1 : Enrichir `GetActionLogsServiceTest` avec un vrai sérialiseur et des assertions de champ

**Files:**
- Modify: `tests/src/Unit/Service/Back/GetActionLogsServiceTest.php`

> Ces assertions passent avec l'implémentation actuelle (`createFromArray`) ET avec la future (`denormalize`). On fixe d'abord le comportement attendu avant de refactorer.

- [x] **Step 1 : Remplacer le contenu complet de `GetActionLogsServiceTest.php`**

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\DTO\ActionLog;
use App\Security\UserTokenServiceInterface;
use App\Service\Back\AbstractBackService;
use App\Service\Back\GetActionLogsService;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @internal
 */
#[CoversClass(GetActionLogsService::class)]
#[CoversClass(ActionLog::class)]
final class GetActionLogsServiceTest extends AbstractTestBackService
{
    public const ENDPOINT = 'istration/action-logs';
    public const RESPONSE_CONTENT = '/app/tests/resources/unit/service/back/action-logs.json';

    public function testGet(): void
    {
        /** @var GetActionLogsService $service */
        $service = $this->getServiceWithLoggedUser(
            'GET',
            (string) file_get_contents(self::RESPONSE_CONTENT),
            self::ENDPOINT,
            [],
            $this->buildSerializer(),
        );

        $this->assertServiceGet($service);
    }

    public function testWithoutLoggedUser(): void
    {
        /** @var GetActionLogsService $service */
        $service = $this->getServiceWithoutLoggedUser(
            'GET',
            (string) file_get_contents(self::RESPONSE_CONTENT),
            self::ENDPOINT,
            [],
            $this->buildSerializer(),
        );

        $this->assertServiceGet($service);
    }

    #[\Override]
    protected function instanciateService(
        LoggerInterface $logger,
        HttpClientInterface $client,
        string $url,
        string $cafilePath,
        UserTokenServiceInterface $userTokenService,
        SerializerInterface $serializer,
    ): AbstractBackService {
        return new GetActionLogsService(
            $logger,
            $client,
            $url,
            $cafilePath,
            $userTokenService,
            $serializer,
        );
    }

    private function buildSerializer(): SerializerInterface
    {
        $classMetadataFactory = new ClassMetadataFactory(new AttributeLoader());

        return new Serializer([
            new DateTimeNormalizer(),
            new ObjectNormalizer($classMetadataFactory),
        ]);
    }

    private function assertServiceGet(GetActionLogsService $service): void
    {
        $actionLogs = $service->get();

        $this->assertCount(10, $actionLogs);

        $expectedLogs = [
            'calculate_dex_availabilities',
            'calculate_pokemon_availabilities',
            'calculate_game_bundles_availabilities',
            'calculate_game_bundles_shinies_availabilities',
            'update_games_collections_and_dex',
            'update_games_availabilities',
            'update_games_shinies_availabilities',
            'update_labels',
            'update_pokemons',
            'update_collections_availabilities',
        ];

        foreach ($expectedLogs as $key) {
            $this->assertArrayHasKey($key, $actionLogs);
        }

        // Cas minimal : last = null, current sans valeurs optionnelles
        $calcGameBundles = $actionLogs['calculate_game_bundles_availabilities'];
        $this->assertNull($calcGameBundles->last);
        $this->assertEquals(new \DateTime('2023-03-21T07:15:04+00:00'), $calcGameBundles->current->createdAt);
        $this->assertNull($calcGameBundles->current->doneAt);
        $this->assertNull($calcGameBundles->current->executionTime);
        $this->assertSame([], $calcGameBundles->current->details);
        $this->assertNull($calcGameBundles->current->errorTrace);

        // Cas complet : last avec toutes les valeurs non-null
        $calcDex = $actionLogs['calculate_dex_availabilities'];
        $this->assertNotNull($calcDex->last);
        $this->assertEquals(new \DateTime('2023-03-20T09:14:36+00:00'), $calcDex->last->createdAt);
        $this->assertEquals(new \DateTime('2023-03-20T10:05:08+00:00'), $calcDex->last->doneAt);
        $this->assertSame(3032, $calcDex->last->executionTime);
        $this->assertSame(['dex_availabilities' => 22472], $calcDex->last->details);
        $this->assertNull($calcDex->last->errorTrace);
    }
}
```

- [x] **Step 2 : Lancer les tests pour vérifier qu'ils passent (GREEN)**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/Back/GetActionLogsServiceTest.php
```

Résultat attendu : `OK (2 tests, N assertions)` — pas d'erreur.

---

## Task 2 : Refactorer `ActionLog.php`

**Files:**
- Modify: `src/DTO/ActionLog.php`

- [x] **Step 1 : Remplacer le contenu complet de `ActionLog.php`**

```php
<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class ActionLog
{
    /**
     * @param array<string, int> $details
     */
    public function __construct(
        #[SerializedName('created_at')]
        public readonly \DateTime $createdAt,
        #[SerializedName('done_at')]
        public readonly ?\DateTime $doneAt,
        #[SerializedName('execution_time')]
        public readonly ?int $executionTime,
        #[SerializedName('details')]
        public readonly array $details,
        #[SerializedName('error_trace')]
        public readonly ?string $errorTrace,
    ) {}
}
```

---

## Task 3 : Refactorer `GetActionLogsService.php`

**Files:**
- Modify: `src/Service/Back/GetActionLogsService.php`

- [x] **Step 1 : Remplacer le contenu complet de `GetActionLogsService.php`**

```php
<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\DTO\ActionLog;
use App\DTO\ActionLogData;
use App\Utils\JsonDecoder;

class GetActionLogsService extends AbstractBackService
{
    /**
     * @return array<string, ActionLogData>
     */
    public function get(): array
    {
        $json = $this->requestContent(
            'GET',
            '/istration/action-logs'
        );

        /** @var array<string, array{current: mixed[], last: mixed[]|null}> */
        $actionLogsData = JsonDecoder::decode($json);

        $list = [];
        foreach ($actionLogsData as $item => $data) {
            /** @var mixed[] */
            $currentData = $data['current'];

            /** @var mixed[]|null */
            $lastData = $data['last'];

            $list[$item] = new ActionLogData(
                $item,
                $this->serializer->denormalize($currentData, ActionLog::class),
                $lastData ? $this->serializer->denormalize($lastData, ActionLog::class) : null,
            );
        }

        return $list;
    }
}
```

- [x] **Step 2 : Lancer les tests pour vérifier que tout passe encore (GREEN)**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/Back/GetActionLogsServiceTest.php
```

Résultat attendu : `OK (2 tests, N assertions)`.

---

## Task 4 : Supprimer `ActionLogTest.php`

**Files:**
- Delete: `tests/src/Unit/DTO/ActionLogTest.php`

- [x] **Step 1 : Supprimer le fichier**

```bash
rm /home/renaud/projects/pokenini-web/tests/src/Unit/DTO/ActionLogTest.php
```

- [x] **Step 2 : Lancer tous les tests unitaires pour vérifier l'absence de régression**

```bash
docker compose exec php php vendor/bin/phpunit --testsuite=Unit
```

Résultat attendu : tous les tests passent, pas de mention de `ActionLogTest`.

---

## Task 5 : Vérification qualité et commit

- [x] **Step 1 : Lancer le fixer de style**

```bash
make phpcsfixer-fix
```

- [x] **Step 2 : Lancer les outils de qualité complets**

```bash
make code-quality
```

Résultat attendu : `phpcsfixer`, `psalm`, `phpstan`, `phpmd`, `deptrac` passent sans erreur.

- [x] **Step 3 : Committer**

```bash
git add src/DTO/ActionLog.php \
        src/Service/Back/GetActionLogsService.php \
        tests/src/Unit/Service/Back/GetActionLogsServiceTest.php
git rm tests/src/Unit/DTO/ActionLogTest.php
git commit -m "$(cat <<'EOF'
Refactor ActionLog to use Symfony Serializer instead of createFromArray

Removes manual array casts, @psalm-suppress RiskyCast and forced @var
annotations. GetActionLogsService now calls $this->serializer->denormalize()
which is already injected via AbstractBackService.

Co-Authored-By: Claude Sonnet 4.6 <noreply@anthropic.com>
EOF
)"
```
