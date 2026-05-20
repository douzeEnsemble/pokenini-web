# ResponseObject Deserialization Tests — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ajouter les 2 tests de désérialisation manquants (`ActionLog` et `CatchState`) dans `tests/src/Integration/ResponseObject/`, puis marquer le point 12 de `doc/improvement.md` comme traité.

**Architecture:** Tests `KernelTestCase` qui récupèrent le vrai `SerializerInterface` depuis le container Symfony, passent du JSON inline, et vérifient les valeurs désérialisées. Même pattern que les 17 tests existants dans `tests/src/Integration/ResponseObject/`. Pas de Moco requis, pas de `#[Group('api-mocked-testing')]`.

**Tech Stack:** PHP 8.4, PHPUnit 11, Symfony Serializer (container), `#[SerializedName]` attributes sur les ResponseObjects.

---

### Task 1 : Test de désérialisation pour `ActionLog`

**Files:**
- Create: `tests/src/Integration/ResponseObject/ActionLogTest.php`

- [ ] **Step 1 : Écrire le test (il doit passer car le code source existe déjà)**

Créer `tests/src/Integration/ResponseObject/ActionLogTest.php` :

```php
<?php

declare(strict_types=1);

namespace App\Tests\Integration\ResponseObject;

use App\ResponseObject\ActionLog;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
#[CoversClass(ActionLog::class)]
final class ActionLogTest extends KernelTestCase
{
    public function testDeserialize(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get(SerializerInterface::class);

        $json = <<<'JSON'
            {
                "created_at": "2023-03-21T09:14:36+00:00",
                "done_at": null,
                "execution_time": null,
                "details": [],
                "error_trace": null
            }
            JSON;

        $object = $serializer->deserialize($json, ActionLog::class, 'json');

        $this->assertInstanceOf(ActionLog::class, $object);
        $this->assertInstanceOf(\DateTime::class, $object->createdAt);
        $this->assertSame('2023-03-21T09:14:36+00:00', $object->createdAt->format('c'));
        $this->assertNull($object->doneAt);
        $this->assertNull($object->executionTime);
        $this->assertSame([], $object->details);
        $this->assertNull($object->errorTrace);
    }

    public function testDeserializeWithDoneAt(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get(SerializerInterface::class);

        $json = <<<'JSON'
            {
                "created_at": "2023-03-20T09:14:36+00:00",
                "done_at": "2023-03-20T10:05:08+00:00",
                "execution_time": 3032,
                "details": {"dex_availabilities": 22472},
                "error_trace": null
            }
            JSON;

        $object = $serializer->deserialize($json, ActionLog::class, 'json');

        $this->assertInstanceOf(ActionLog::class, $object);
        $this->assertInstanceOf(\DateTime::class, $object->createdAt);
        $this->assertSame('2023-03-20T09:14:36+00:00', $object->createdAt->format('c'));
        $this->assertInstanceOf(\DateTime::class, $object->doneAt);
        $this->assertSame('2023-03-20T10:05:08+00:00', $object->doneAt->format('c'));
        $this->assertSame(3032, $object->executionTime);
        $this->assertSame(['dex_availabilities' => 22472], $object->details);
        $this->assertNull($object->errorTrace);
    }
}
```

- [ ] **Step 2 : Lancer le test**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Integration/ResponseObject/ActionLogTest.php
```

Expected : 2 tests passent (OK, 2 tests, 0 failures).

- [ ] **Step 3 : Vérifier PHPStan**

```bash
docker compose exec php php tools/phpstan/vendor/bin/phpstan analyse tests/src/Integration/ResponseObject/ActionLogTest.php --memory-limit=-1
```

Expected : `[OK] No errors`.

---

### Task 2 : Test de désérialisation pour `CatchState`

**Files:**
- Create: `tests/src/Integration/ResponseObject/Label/CatchStateTest.php`

- [ ] **Step 1 : Écrire le test**

Créer `tests/src/Integration/ResponseObject/Label/CatchStateTest.php` :

```php
<?php

declare(strict_types=1);

namespace App\Tests\Integration\ResponseObject\Label;

use App\ResponseObject\Label\CatchState;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
#[CoversClass(CatchState::class)]
final class CatchStateTest extends KernelTestCase
{
    public function testDeserialize(): void
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get(SerializerInterface::class);

        $json = <<<'JSON'
            {
                "name": "No",
                "french_name": "Non",
                "slug": "no",
                "color": "#e57373"
            }
            JSON;

        $object = $serializer->deserialize($json, CatchState::class, 'json');

        $this->assertInstanceOf(CatchState::class, $object);
        $this->assertSame('No', $object->getName());
        $this->assertSame('Non', $object->getFrenchName());
        $this->assertSame('no', $object->getSlug());
        $this->assertSame('#e57373', $object->getColor());
    }
}
```

- [ ] **Step 2 : Lancer le test**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Integration/ResponseObject/Label/CatchStateTest.php
```

Expected : 1 test passe (OK, 1 test, 0 failures).

- [ ] **Step 3 : Vérifier PHPStan**

```bash
docker compose exec php php tools/phpstan/vendor/bin/phpstan analyse tests/src/Integration/ResponseObject/Label/CatchStateTest.php --memory-limit=-1
```

Expected : `[OK] No errors`.

---

### Task 3 : Marquer le point 12 comme traité dans `doc/improvement.md`

**Files:**
- Modify: `doc/improvement.md`

- [ ] **Step 1 : Ajouter la section "Traité" au point 12**

Dans `doc/improvement.md`, au point 12, après la ligne `**Fichiers** : \`tests/src/Unit/\` (dossier absent pour \`Service/Back/\`)`, ajouter :

```markdown
**Traité** : 19 tests de désérialisation dans `tests/src/Integration/ResponseObject/` couvrent tous les ResponseObjects (`Album`, `Pokedex`, `Dex`, `Pokemon`, `Report`, `ReportDetail`, `ElectionIndex`, `ElectionList`, `TopPokemon`, `ActionLog`, et tous les labels dont `CatchState`). Chaque test utilise `KernelTestCase` avec le `SerializerInterface` réel du container — aucune dépendance Moco.
```

- [ ] **Step 2 : Lancer tous les tests d'intégration ResponseObject**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Integration/ResponseObject/
```

Expected : tous les tests passent (19 tests, 0 failures).

- [ ] **Step 3 : Lancer la suite qualité complète**

```bash
make code-quality
```

Expected : aucune erreur.
