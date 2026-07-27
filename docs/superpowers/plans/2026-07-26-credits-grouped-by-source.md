# Credits Grouped By Source Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the flat, deduplicated Credits page with one that groups image credits by source and shows, per source, how many images use it plus an expandable detail list of the concerned Pokémon + image slot.

**Architecture:** `pokenini-api`'s `pokemon_image_credit` table already links a credit `source` string to `(pokemon, size, isShiny)` — the current `/credits` endpoint discards that link with a `SELECT DISTINCT source` query. This plan replaces that query with a join that keeps the Pokémon association, groups + sorts by image count in the API's Service layer, and threads the richer shape through `pokenini-back` (pure pass-through, no code change) to `pokenini-web`, which renders it as a collapsible per-source detail list with a sprite-thumbnail tooltip per line.

**Tech Stack:** Symfony 8 / PHP ≥ 8.5 across all three repos, Doctrine DBAL (raw SQL) in `pokenini-api`, Symfony Serializer in `pokenini-web`, Twig + Bootstrap 5 (collapse + tooltip components) for display.

## Global Constraints

- `declare(strict_types=1)` in every PHP file (all three repos).
- Entities/DTOs/Controllers/test classes are `final`; Repository/Service classes are NOT `final` (PHPUnit mocking).
- Every test class: `/** @internal */` docblock + `#[CoversClass(...)]`; `pokenini-api` tests use `#[Test]`-attributed methods (not `test`-prefixed names) — match each repo's existing convention exactly (see per-task examples).
- 100% coverage + 100% Mutation Score Index (Infection) required in all three repos (`make measures` / `make coverage` + `make infection`); PHPStan level 9; Psalm strict; Deptrac clean; PHP CS Fixer clean.
- Integration tests use Moco fixtures (never mock the HTTP client) in `pokenini-back` and `pokenini-web`.
- **Do not run `git commit` at any point while executing this plan.** The user's standing instruction is to never commit proactively — leave all changes staged/unstaged for the user to review and commit themselves. Each task ends with "verify tests pass", not "commit".
- No database migration is needed — `pokemon_image_credit` already has every column this feature needs.
- The per-image credit badge shown on Album/Election pages (`_image_macros.html.twig`, `PokemonCredit` on `pokenini-web`, `ImageCreditResponse`/`PokemonDataResponse` on `pokenini-api`) is **out of scope and must not change** — only the standalone `GET /credits` list endpoint and the Credits page change.

---

## Task 1: `pokenini-api` — add tie-breaking fixture data

**Files:**
- Modify: `fixtures/pokemon_image_credits.yaml`

**Interfaces:**
- Consumes: nothing (fixture-only change).
- Produces: fixture data consumed by Task 2's repository test and Task 5's controller test.

The current fixture file has exactly one non-null-source row per source (4 distinct sources, one row each), which can't exercise "sort by image count descending" (every group would tie at count 1). Add one more row crediting an existing Pokémon's *other* slot to the same source as another row, so one source has 2 images and the rest have 1.

- [ ] **Step 1: Add the new fixture row**

Edit `fixtures/pokemon_image_credits.yaml` — current full content is:

```yaml
App\Entity\PokemonImageCredit:
  pokemon_image_credit_bulbasaur_small_regular:
    pokemon: "@pokemon_bulbasaur"
    size: "small"
    isShiny: false
    source: "PokéSprite - https://github.com/msikma/pokesprite"

  pokemon_image_credit_bulbasaur_big_regular:
    pokemon: "@pokemon_bulbasaur"
    size: "big"
    isShiny: false
    source: "PokemonDB - https://pokemondb.net/sprites/bulbasaur"

  pokemon_image_credit_douze_small_shiny_no_source:
    pokemon: "@pokemon_douze"
    size: "small"
    isShiny: true
    source: ~

  pokemon_image_credit_ivysaur_small_regular:
    pokemon: "@pokemon_ivysaur"
    size: "small"
    isShiny: false
    source: "Bulbapedia - https://bulbapedia.bulbagarden.net"

  pokemon_image_credit_venusaur_small_regular:
    pokemon: "@pokemon_venusaur"
    size: "small"
    isShiny: false
    source: "Serebii - https://serebii.net"
```

Add a new entry `pokemon_image_credit_ivysaur_big_regular` crediting Ivysaur's big/regular slot to the **same** source as Bulbasaur's small/regular slot (`"PokéSprite - https://github.com/msikma/pokesprite"`), so that source now covers 2 images while the other 3 sources still cover 1 each:

```yaml
  pokemon_image_credit_ivysaur_big_regular:
    pokemon: "@pokemon_ivysaur"
    size: "big"
    isShiny: false
    source: "PokéSprite - https://github.com/msikma/pokesprite"
```

Append it after `pokemon_image_credit_bulbasaur_big_regular` (or anywhere in the file — fixture load order doesn't matter here).

- [ ] **Step 2: Confirm fixtures still load**

Run: `docker compose exec php php bin/console doctrine:fixtures:load --env=test --no-interaction` (or simply proceed to Task 2, whose test run reloads fixtures via `RefreshDatabaseTrait`).
Expected: no error.

---

## Task 2: `pokenini-api` — repository query joining Pokémon

**Files:**
- Modify: `src/Repository/PokemonImageCreditRepository.php`
- Modify (replace test): `tests/src/Integration/Repository/PokemonImageCreditRepositoryTest.php`

**Interfaces:**
- Consumes: `pokemon_image_credit` + `pokemon` tables (via `$this->getEntityManager()->getConnection()`).
- Produces: `PokemonImageCreditRepository::findAllWithPokemon(): array` returning
  `array<array{source: string, pokemon_slug: string, pokemon_name: string, pokemon_french_name: string, pokemon_icon: string, size: string, is_shiny: bool}>`, ordered by `p.national_dex_number, pic.size, pic.is_shiny`. This replaces `findAllDistinctSources()`, which is removed (its only caller, `ImageCreditsService::getAll()`, is replaced in Task 3).

- [ ] **Step 1: Replace the repository test**

Overwrite `tests/src/Integration/Repository/PokemonImageCreditRepositoryTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Repository\PokemonImageCreditRepository;
use Hautelook\AliceBundle\PhpUnit\RefreshDatabaseTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @internal
 */
#[CoversClass(PokemonImageCreditRepository::class)]
final class PokemonImageCreditRepositoryTest extends KernelTestCase
{
    use RefreshDatabaseTrait;

    #[\Override]
    public function setUp(): void
    {
        self::bootKernel();
    }

    #[Test]
    public function findAllWithPokemonReturnsEachNonNullCreditWithItsPokemonAndSlot(): void
    {
        $repo = self::getContainer()->get(PokemonImageCreditRepository::class);

        $result = $repo->findAllWithPokemon();

        self::assertCount(5, $result);
        self::assertContains(
            [
                'source' => 'PokéSprite - https://github.com/msikma/pokesprite',
                'pokemon_slug' => 'bulbasaur',
                'pokemon_name' => 'Bulbasaur',
                'pokemon_french_name' => 'Bulbizarre',
                'pokemon_icon' => 'bulbasaur',
                'size' => 'small',
                'is_shiny' => false,
            ],
            $result,
        );
        self::assertContains(
            [
                'source' => 'PokéSprite - https://github.com/msikma/pokesprite',
                'pokemon_slug' => 'ivysaur',
                'pokemon_name' => 'Ivysaur',
                'pokemon_french_name' => 'Herbizarre',
                'pokemon_icon' => 'ivysaur',
                'size' => 'big',
                'is_shiny' => false,
            ],
            $result,
        );
        self::assertContains(
            [
                'source' => 'PokemonDB - https://pokemondb.net/sprites/bulbasaur',
                'pokemon_slug' => 'bulbasaur',
                'pokemon_name' => 'Bulbasaur',
                'pokemon_french_name' => 'Bulbizarre',
                'pokemon_icon' => 'bulbasaur',
                'size' => 'big',
                'is_shiny' => false,
            ],
            $result,
        );
        self::assertContains(
            [
                'source' => 'Bulbapedia - https://bulbapedia.bulbagarden.net',
                'pokemon_slug' => 'ivysaur',
                'pokemon_name' => 'Ivysaur',
                'pokemon_french_name' => 'Herbizarre',
                'pokemon_icon' => 'ivysaur',
                'size' => 'small',
                'is_shiny' => false,
            ],
            $result,
        );
        self::assertContains(
            [
                'source' => 'Serebii - https://serebii.net',
                'pokemon_slug' => 'venusaur',
                'pokemon_name' => 'Venusaur',
                'pokemon_french_name' => 'Florizarre',
                'pokemon_icon' => 'venusaur',
                'size' => 'small',
                'is_shiny' => false,
            ],
            $result,
        );
    }

    #[Test]
    public function findAllWithPokemonExcludesRowsWithNullSource(): void
    {
        $repo = self::getContainer()->get(PokemonImageCreditRepository::class);

        $result = $repo->findAllWithPokemon();

        foreach ($result as $row) {
            self::assertNotSame('douze', $row['pokemon_slug']);
        }
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/Repository/PokemonImageCreditRepositoryTest.php`
Expected: FAIL — `findAllWithPokemon()` does not exist (`Error: Call to undefined method`).

- [ ] **Step 3: Implement `findAllWithPokemon()`**

Replace `findAllDistinctSources()` in `src/Repository/PokemonImageCreditRepository.php` with:

```php
<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\PokemonImageCredit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PokemonImageCredit>
 */
class PokemonImageCreditRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PokemonImageCredit::class);
    }

    /**
     * @return array<array{
     *   source: string,
     *   pokemon_slug: string,
     *   pokemon_name: string,
     *   pokemon_french_name: string,
     *   pokemon_icon: string,
     *   size: string,
     *   is_shiny: bool,
     * }>
     */
    public function findAllWithPokemon(): array
    {
        $sql = <<<'SQL'
            SELECT      pic.source AS source,
                        p.slug AS pokemon_slug,
                        p.name AS pokemon_name,
                        p.french_name AS pokemon_french_name,
                        p.icon_name AS pokemon_icon,
                        pic.size AS size,
                        pic.is_shiny AS is_shiny
            FROM        pokemon_image_credit AS pic
                    JOIN pokemon AS p ON p.id = pic.pokemon_id
            WHERE       pic.source IS NOT NULL
            ORDER BY    p.national_dex_number, pic.size, pic.is_shiny
            SQL;

        /** @var array<array{source: string, pokemon_slug: string, pokemon_name: string, pokemon_french_name: string, pokemon_icon: string, size: string, is_shiny: bool}> */
        return $this->getEntityManager()->getConnection()->fetchAllAssociative($sql);
    }
}
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/Repository/PokemonImageCreditRepositoryTest.php`
Expected: PASS (2 tests, both assertions green).

---

## Task 3: `pokenini-api` — service groups and sorts by source

**Files:**
- Modify: `src/Service/ImageCreditsService.php`
- Modify (replace test): `tests/src/Unit/Service/ImageCreditsServiceTest.php`

**Interfaces:**
- Consumes: `PokemonImageCreditRepository::findAllWithPokemon(): array` (Task 2's return shape).
- Produces: `ImageCreditsService::getAllGroupedBySource(): array` returning
  `array<array{source: string, images: array<array{pokemon_slug: string, pokemon_name: string, pokemon_french_name: string, pokemon_icon: string, size: string, is_shiny: bool}>}>`,
  sorted by `count(images)` descending, ties broken alphabetically by `source`. Replaces `getAll()`.

- [ ] **Step 1: Replace the service test**

Overwrite `tests/src/Unit/Service/ImageCreditsServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Repository\PokemonImageCreditRepository;
use App\Service\ImageCreditsService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ImageCreditsService::class)]
final class ImageCreditsServiceTest extends TestCase
{
    private MockObject&PokemonImageCreditRepository $repository;
    private ImageCreditsService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(PokemonImageCreditRepository::class);
        $this->service = new ImageCreditsService($this->repository);
    }

    #[Test]
    public function getAllGroupedBySourceGroupsRowsBySourceAndSortsByImageCountDescending(): void
    {
        $imageA1 = ['pokemon_slug' => 'a1', 'pokemon_name' => 'A1', 'pokemon_french_name' => 'A1fr', 'pokemon_icon' => 'a1', 'size' => 'small', 'is_shiny' => false];
        $imageA2 = ['pokemon_slug' => 'a2', 'pokemon_name' => 'A2', 'pokemon_french_name' => 'A2fr', 'pokemon_icon' => 'a2', 'size' => 'big', 'is_shiny' => false];
        $imageB1 = ['pokemon_slug' => 'b1', 'pokemon_name' => 'B1', 'pokemon_french_name' => 'B1fr', 'pokemon_icon' => 'b1', 'size' => 'small', 'is_shiny' => true];

        $this->repository
            ->expects(self::once())
            ->method('findAllWithPokemon')
            ->willReturn([
                ['source' => 'SourceB', ...$imageB1],
                ['source' => 'SourceA', ...$imageA1],
                ['source' => 'SourceA', ...$imageA2],
            ])
        ;

        $result = $this->service->getAllGroupedBySource();

        self::assertSame(
            [
                ['source' => 'SourceA', 'images' => [$imageA1, $imageA2]],
                ['source' => 'SourceB', 'images' => [$imageB1]],
            ],
            $result,
        );
    }

    #[Test]
    public function getAllGroupedBySourceBreaksImageCountTiesAlphabeticallyBySource(): void
    {
        $image = ['pokemon_slug' => 'x', 'pokemon_name' => 'X', 'pokemon_french_name' => 'Xfr', 'pokemon_icon' => 'x', 'size' => 'small', 'is_shiny' => false];

        $this->repository
            ->expects(self::once())
            ->method('findAllWithPokemon')
            ->willReturn([
                ['source' => 'Zebra', ...$image],
                ['source' => 'Alpha', ...$image],
            ])
        ;

        $result = $this->service->getAllGroupedBySource();

        self::assertSame('Alpha', $result[0]['source']);
        self::assertSame('Zebra', $result[1]['source']);
    }

    #[Test]
    public function getAllGroupedBySourceReturnsEmptyArrayWhenRepositoryHasNoRows(): void
    {
        $this->repository
            ->expects(self::once())
            ->method('findAllWithPokemon')
            ->willReturn([])
        ;

        self::assertSame([], $this->service->getAllGroupedBySource());
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/ImageCreditsServiceTest.php`
Expected: FAIL — `getAllGroupedBySource()` does not exist.

- [ ] **Step 3: Implement `getAllGroupedBySource()`**

Replace the body of `src/Service/ImageCreditsService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\PokemonImageCreditRepository;

class ImageCreditsService
{
    public function __construct(
        private readonly PokemonImageCreditRepository $repository,
    ) {}

    /**
     * @return array<array{
     *   source: string,
     *   images: array<array{
     *     pokemon_slug: string,
     *     pokemon_name: string,
     *     pokemon_french_name: string,
     *     pokemon_icon: string,
     *     size: string,
     *     is_shiny: bool,
     *   }>,
     * }>
     */
    public function getAllGroupedBySource(): array
    {
        $rows = $this->repository->findAllWithPokemon();

        /** @var array<string, array{source: string, images: array<array<string, mixed>>}> $grouped */
        $grouped = [];
        foreach ($rows as $row) {
            $source = $row['source'];
            unset($row['source']);

            $grouped[$source]['source'] ??= $source;
            $grouped[$source]['images'][] = $row;
        }

        $groups = array_values($grouped);

        usort(
            $groups,
            static function (array $a, array $b): int {
                $countComparison = count($b['images']) <=> count($a['images']);

                return 0 !== $countComparison ? $countComparison : $a['source'] <=> $b['source'];
            },
        );

        return $groups;
    }
}
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/ImageCreditsServiceTest.php`
Expected: PASS (3 tests).

---

## Task 4: `pokenini-api` — new grouped DTOs and factory

**Files:**
- Create: `src/DTO/Response/ImageCreditImageResponse.php`
- Create: `src/DTO/Response/ImageCreditGroupResponse.php`
- Create: `src/Factory/ImageCreditGroupResponseFactory.php`
- Delete: `src/Factory/ImageCreditResponseFactory.php`
- Delete: `tests/src/Unit/Factory/ImageCreditResponseFactoryTest.php`
- Create (replaces the deleted one): `tests/src/Unit/Factory/ImageCreditGroupResponseFactoryTest.php`

**Interfaces:**
- Consumes: `ImageCreditsService::getAllGroupedBySource()`'s return shape (Task 3).
- Produces: `ImageCreditGroupResponseFactory::fromGroupedRows(array $groups): ImageCreditGroupResponse[]`, where `ImageCreditGroupResponse` has public readonly `string $credit` and `ImageCreditImageResponse[] $images`, and `ImageCreditImageResponse` has public readonly `string $pokemonSlug, $pokemonName, $pokemonFrenchName, $pokemonIcon, $size` and `bool $isShiny`.

**Do not touch** `src/DTO/Response/ImageCreditResponse.php` or `src/Entity`-level `ImageCreditResponse` usages inside `AlbumPokemonResponseFactory`/`ElectionPokemonResponseFactory`/`ElectionEloResponseFactory` — that DTO remains exactly as-is; it backs the per-image embedded credit fields on `PokemonDataResponse`, which are unrelated to this feature.

- [ ] **Step 1: Delete the obsolete factory test**

Delete `tests/src/Unit/Factory/ImageCreditResponseFactoryTest.php` (it covers `ImageCreditResponseFactory`, which Step 4 deletes — its only caller, `ImageCreditsController`, is rewired in Task 5).

- [ ] **Step 2: Write the new factory test**

Create `tests/src/Unit/Factory/ImageCreditGroupResponseFactoryTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Factory;

use App\DTO\Response\ImageCreditGroupResponse;
use App\DTO\Response\ImageCreditImageResponse;
use App\Factory\ImageCreditGroupResponseFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ImageCreditGroupResponseFactory::class)]
final class ImageCreditGroupResponseFactoryTest extends TestCase
{
    #[Test]
    public function fromGroupedRowsBuildsOneGroupPerSourceWithItsImages(): void
    {
        $groups = ImageCreditGroupResponseFactory::fromGroupedRows([
            [
                'source' => 'PokéSprite - https://github.com/msikma/pokesprite',
                'images' => [
                    [
                        'pokemon_slug' => 'bulbasaur',
                        'pokemon_name' => 'Bulbasaur',
                        'pokemon_french_name' => 'Bulbizarre',
                        'pokemon_icon' => 'bulbasaur',
                        'size' => 'small',
                        'is_shiny' => false,
                    ],
                ],
            ],
        ]);

        self::assertCount(1, $groups);
        self::assertInstanceOf(ImageCreditGroupResponse::class, $groups[0]);
        self::assertSame('PokéSprite - https://github.com/msikma/pokesprite', $groups[0]->credit);
        self::assertCount(1, $groups[0]->images);

        $image = $groups[0]->images[0];
        self::assertInstanceOf(ImageCreditImageResponse::class, $image);
        self::assertSame('bulbasaur', $image->pokemonSlug);
        self::assertSame('Bulbasaur', $image->pokemonName);
        self::assertSame('Bulbizarre', $image->pokemonFrenchName);
        self::assertSame('bulbasaur', $image->pokemonIcon);
        self::assertSame('small', $image->size);
        self::assertFalse($image->isShiny);
    }

    #[Test]
    public function fromGroupedRowsBuildsMultipleImagesPerGroup(): void
    {
        $groups = ImageCreditGroupResponseFactory::fromGroupedRows([
            [
                'source' => 'Serebii - https://serebii.net',
                'images' => [
                    ['pokemon_slug' => 'a', 'pokemon_name' => 'A', 'pokemon_french_name' => 'Afr', 'pokemon_icon' => 'a', 'size' => 'small', 'is_shiny' => false],
                    ['pokemon_slug' => 'b', 'pokemon_name' => 'B', 'pokemon_french_name' => 'Bfr', 'pokemon_icon' => 'b', 'size' => 'big', 'is_shiny' => true],
                ],
            ],
        ]);

        self::assertCount(2, $groups[0]->images);
        self::assertSame('a', $groups[0]->images[0]->pokemonSlug);
        self::assertSame('b', $groups[0]->images[1]->pokemonSlug);
        self::assertTrue($groups[0]->images[1]->isShiny);
    }

    #[Test]
    public function fromGroupedRowsHandlesEmptyArray(): void
    {
        self::assertSame([], ImageCreditGroupResponseFactory::fromGroupedRows([]));
    }
}
```

- [ ] **Step 3: Run the test to verify it fails**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Factory/ImageCreditGroupResponseFactoryTest.php`
Expected: FAIL — class `App\Factory\ImageCreditGroupResponseFactory` not found.

- [ ] **Step 4: Create the DTOs and factory, delete the obsolete factory**

Create `src/DTO/Response/ImageCreditImageResponse.php`. This DTO needs explicit `#[SerializedName]` on every multi-word property: unlike `pokenini-web`'s serializer config, `pokenini-api` has no global camelCase-to-snake_case name converter (confirmed: no `name_converter` in `config/`, and every existing multi-word DTO property — e.g. `PokemonLabelsResponse::$frenchName` — carries an explicit `#[SerializedName('french_name')]`). Without it, JSON output would be `pokemonSlug` instead of `pokemon_slug`, breaking every fixture in this plan.

```php
<?php

declare(strict_types=1);

namespace App\DTO\Response;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class ImageCreditImageResponse
{
    public function __construct(
        #[SerializedName('pokemon_slug')]
        public readonly string $pokemonSlug,
        #[SerializedName('pokemon_name')]
        public readonly string $pokemonName,
        #[SerializedName('pokemon_french_name')]
        public readonly string $pokemonFrenchName,
        #[SerializedName('pokemon_icon')]
        public readonly string $pokemonIcon,
        public readonly string $size,
        #[SerializedName('is_shiny')]
        public readonly bool $isShiny,
    ) {}
}
```

Create `src/DTO/Response/ImageCreditGroupResponse.php`:

```php
<?php

declare(strict_types=1);

namespace App\DTO\Response;

final class ImageCreditGroupResponse
{
    /**
     * @param ImageCreditImageResponse[] $images
     */
    public function __construct(
        public readonly string $credit,
        public readonly array $images,
    ) {}
}
```

Create `src/Factory/ImageCreditGroupResponseFactory.php`:

```php
<?php

declare(strict_types=1);

namespace App\Factory;

use App\DTO\Response\ImageCreditGroupResponse;
use App\DTO\Response\ImageCreditImageResponse;

final class ImageCreditGroupResponseFactory
{
    /**
     * @param array{
     *   source: string,
     *   images: array<array{
     *     pokemon_slug: string,
     *     pokemon_name: string,
     *     pokemon_french_name: string,
     *     pokemon_icon: string,
     *     size: string,
     *     is_shiny: bool,
     *   }>,
     * } $group
     */
    public static function fromGroupedRow(array $group): ImageCreditGroupResponse
    {
        return new ImageCreditGroupResponse(
            credit: $group['source'],
            images: array_map(self::buildImage(...), $group['images']),
        );
    }

    /**
     * @param array<array{
     *   source: string,
     *   images: array<array{
     *     pokemon_slug: string,
     *     pokemon_name: string,
     *     pokemon_french_name: string,
     *     pokemon_icon: string,
     *     size: string,
     *     is_shiny: bool,
     *   }>,
     * }> $groups
     *
     * @return ImageCreditGroupResponse[]
     */
    public static function fromGroupedRows(array $groups): array
    {
        return array_map(self::fromGroupedRow(...), $groups);
    }

    /**
     * @param array{pokemon_slug: string, pokemon_name: string, pokemon_french_name: string, pokemon_icon: string, size: string, is_shiny: bool} $row
     */
    private static function buildImage(array $row): ImageCreditImageResponse
    {
        return new ImageCreditImageResponse(
            pokemonSlug: $row['pokemon_slug'],
            pokemonName: $row['pokemon_name'],
            pokemonFrenchName: $row['pokemon_french_name'],
            pokemonIcon: $row['pokemon_icon'],
            size: $row['size'],
            isShiny: $row['is_shiny'],
        );
    }
}
```

Delete `src/Factory/ImageCreditResponseFactory.php`.

- [ ] **Step 5: Run the test to verify it passes**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Factory/ImageCreditGroupResponseFactoryTest.php`
Expected: PASS (3 tests).

---

## Task 5: `pokenini-api` — wire the controller to the new shape

**Files:**
- Modify: `src/Controller/ImageCreditsController.php`
- Modify (replace test): `tests/src/Integration/Controller/ImageCreditsControllerTest.php`

**Interfaces:**
- Consumes: `ImageCreditsService::getAllGroupedBySource()` (Task 3), `ImageCreditGroupResponseFactory::fromGroupedRows()` (Task 4).
- Produces: `GET /credits` now returns JSON shaped as
  `[{"credit": "...", "images": [{"pokemon_slug": "...", "pokemon_name": "...", "pokemon_french_name": "...", "pokemon_icon": "...", "size": "small"|"big", "is_shiny": bool}, ...]}, ...]`.

- [ ] **Step 1: Replace the controller test**

Overwrite `tests/src/Integration/Controller/ImageCreditsControllerTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Controller\ImageCreditsController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(ImageCreditsController::class)]
final class ImageCreditsControllerTest extends WebTestCase
{
    #[Test]
    public function getReturnsSuccessfulJsonResponse(): void
    {
        $client = self::createClient();
        $client->request('GET', '/credits', [], [], [
            'PHP_AUTH_USER' => 'web',
            'PHP_AUTH_PW' => 'douze',
        ]);

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/json');
    }

    #[Test]
    public function getReturnsCreditsGroupedBySourceWithTheirImagesFromFixtures(): void
    {
        $client = self::createClient();
        $client->request('GET', '/credits', [], [], [
            'PHP_AUTH_USER' => 'web',
            'PHP_AUTH_PW' => 'douze',
        ]);

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);

        /** @var array<int, array{credit: string, images: array<int, array<string, mixed>>}> $data */
        $data = json_decode($content, associative: true);

        self::assertCount(4, $data);

        // Sorted by image count descending: PokéSprite has 2 images (bulbasaur small
        // regular + ivysaur big regular, see fixtures/pokemon_image_credits.yaml), the
        // other 3 sources have 1 image each and tie-break alphabetically.
        self::assertSame('PokéSprite - https://github.com/msikma/pokesprite', $data[0]['credit']);
        self::assertCount(2, $data[0]['images']);
        self::assertSame('Bulbapedia - https://bulbapedia.bulbagarden.net', $data[1]['credit']);
        self::assertSame('PokemonDB - https://pokemondb.net/sprites/bulbasaur', $data[2]['credit']);
        self::assertSame('Serebii - https://serebii.net', $data[3]['credit']);

        self::assertContains(
            [
                'pokemon_slug' => 'bulbasaur',
                'pokemon_name' => 'Bulbasaur',
                'pokemon_french_name' => 'Bulbizarre',
                'pokemon_icon' => 'bulbasaur',
                'size' => 'small',
                'is_shiny' => false,
            ],
            $data[0]['images'],
        );
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/ImageCreditsControllerTest.php`
Expected: FAIL — response still contains the old flat `{"credit": "..."}` shape (no `images` key), assertions on `$data[0]['images']` error out.

- [ ] **Step 3: Update the controller**

Replace `src/Controller/ImageCreditsController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\Response\ImageCreditGroupResponse;
use App\Factory\ImageCreditGroupResponseFactory;
use App\Service\ImageCreditsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Attribute\Serialize;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/credits')]
final class ImageCreditsController extends AbstractController
{
    /** @return ImageCreditGroupResponse[] */
    #[Route(path: '', methods: ['GET'])]
    #[Serialize]
    public function get(ImageCreditsService $service): array
    {
        return ImageCreditGroupResponseFactory::fromGroupedRows($service->getAllGroupedBySource());
    }
}
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/ImageCreditsControllerTest.php`
Expected: PASS (2 tests).

- [ ] **Step 5: Run the full `pokenini-api` suite and quality gate**

Run: `make tests && make quality && make measures`
Expected: all green. This confirms nothing in `AlbumPokemonResponseFactory`/`ElectionPokemonResponseFactory`/`ElectionEloResponseFactory` (which still use the untouched `ImageCreditResponse`) broke, and that the deleted `ImageCreditResponseFactory`/old repository method have no remaining references (PHPStan/Psalm would otherwise fail on dangling `use` statements or dead code).

---

## Task 6: `pokenini-back` — update fixtures and tests for the new shape

**Files:**
- Modify: `tests/resources/moco/Api/responses/credits.json`
- Modify: `tests/resources/functional/controller/Credits/all.json`
- Modify: `tests/resources/unit/service/api/credits.json`
- Modify: `src/Service/Api/GetCreditsApiService.php` (PHPDoc only, no logic change)
- Modify: `tests/src/Unit/Service/Api/GetCreditsApiServiceTest.php`

**Interfaces:**
- Consumes: nothing new — `CreditsController`/`GetCreditsApiService` are an untyped pass-through (`JsonResponse($service->get())` / `JsonDecoder::decode($json)`), so no production code change is needed beyond the PHPDoc type.
- Produces: `GET /credits` on `pokenini-back` now proxies the grouped shape unchanged.

- [ ] **Step 1: Update the Moco response fixture**

Replace `tests/resources/moco/Api/responses/credits.json` with the grouped shape (mirrors Task 5's fixture-derived output, order matches the sort-by-count-descending rule):

```json
[
    {
        "credit": "PokéSprite - https://github.com/msikma/pokesprite",
        "images": [
            {
                "pokemon_slug": "bulbasaur",
                "pokemon_name": "Bulbasaur",
                "pokemon_french_name": "Bulbizarre",
                "pokemon_icon": "bulbasaur",
                "size": "small",
                "is_shiny": false
            },
            {
                "pokemon_slug": "ivysaur",
                "pokemon_name": "Ivysaur",
                "pokemon_french_name": "Herbizarre",
                "pokemon_icon": "ivysaur",
                "size": "big",
                "is_shiny": false
            }
        ]
    },
    {
        "credit": "Bulbapedia - https://bulbapedia.bulbagarden.net",
        "images": [
            {
                "pokemon_slug": "ivysaur",
                "pokemon_name": "Ivysaur",
                "pokemon_french_name": "Herbizarre",
                "pokemon_icon": "ivysaur",
                "size": "small",
                "is_shiny": false
            }
        ]
    },
    {
        "credit": "PokemonDB - https://pokemondb.net/sprites/bulbasaur",
        "images": [
            {
                "pokemon_slug": "bulbasaur",
                "pokemon_name": "Bulbasaur",
                "pokemon_french_name": "Bulbizarre",
                "pokemon_icon": "bulbasaur",
                "size": "big",
                "is_shiny": false
            }
        ]
    },
    {
        "credit": "Serebii - https://serebii.net",
        "images": [
            {
                "pokemon_slug": "venusaur",
                "pokemon_name": "Venusaur",
                "pokemon_french_name": "Florizarre",
                "pokemon_icon": "venusaur",
                "size": "small",
                "is_shiny": false
            }
        ]
    }
]
```

- [ ] **Step 2: Mirror the same content into the functional expected fixture**

Copy the exact same JSON content from Step 1 into `tests/resources/functional/controller/Credits/all.json` (the controller pass-through means the response is byte-identical to the Moco fixture).

- [ ] **Step 3: Update the unit-test-only fixture**

Replace `tests/resources/unit/service/api/credits.json` with a smaller 2-group version (this fixture is only used to assert the service's cache/pass-through behavior in isolation, not exact content matching):

```json
[
    {
        "credit": "PokéSprite - https://github.com/msikma/pokesprite",
        "images": [
            {
                "pokemon_slug": "bulbasaur",
                "pokemon_name": "Bulbasaur",
                "pokemon_french_name": "Bulbizarre",
                "pokemon_icon": "bulbasaur",
                "size": "small",
                "is_shiny": false
            }
        ]
    },
    {
        "credit": "PokemonDB - https://pokemondb.net",
        "images": []
    }
]
```

- [ ] **Step 4: Update the unit test assertions**

In `tests/src/Unit/Service/Api/GetCreditsApiServiceTest.php`, update `testGet()`'s assertions to match the new fixture shape (the rest of the file — HTTP client mock setup — is unchanged):

```php
    public function testGet(): void
    {
        $credits = $this->getService()->get();

        self::assertCount(2, $credits);
        self::assertSame('PokéSprite - https://github.com/msikma/pokesprite', $credits[0]['credit']);
        self::assertCount(1, $credits[0]['images']);
        self::assertSame('bulbasaur', $credits[0]['images'][0]['pokemon_slug']);

        /** @var string $value */
        $value = $this->cache->getItem('credits')->get();
        self::assertNotEmpty($value);
        self::assertJson($value);
    }
```

- [ ] **Step 5: Update the PHPDoc on the service (no logic change)**

In `src/Service/Api/GetCreditsApiService.php`, update the two `@return`/`@var` docblocks:

```php
    /**
     * @return array<int, array{credit: string, images: array<int, array{pokemon_slug: string, pokemon_name: string, pokemon_french_name: string, pokemon_icon: string, size: string, is_shiny: bool}>}>
     */
    public function get(): array
    {
        $key = KeyMaker::getCreditsKey();

        $json = $this->cache->get($key, function () {
            return $this->requestContent(
                'GET',
                '/credits',
            );
        });

        /** @var array<int, array{credit: string, images: array<int, array{pokemon_slug: string, pokemon_name: string, pokemon_french_name: string, pokemon_icon: string, size: string, is_shiny: bool}>}> */
        return JsonDecoder::decode($json);
    }
```

- [ ] **Step 6: Run the affected tests**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/Api/GetCreditsApiServiceTest.php tests/src/Integration/Credits/CreditsTest.php`
Expected: PASS — `CreditsTest` needs no code change (it only diffs against `Credits/all.json`, updated in Step 2), `GetCreditsApiServiceTest` passes with the Step 4 assertions.

- [ ] **Step 7: Run the full `pokenini-back` suite and quality gate**

Run: `make tests && make quality && make measures`
Expected: all green.

---

## Task 7: `pokenini-web` — make `PokemonCredit`'s extraction helpers reusable

**Files:**
- Modify: `src/ResponseObject/Common/PokemonCredit.php`
- Modify: `tests/src/Unit/ResponseObject/Common/PokemonCreditTest.php`

**Interfaces:**
- Consumes: nothing new.
- Produces: `PokemonCredit::extractName(string $credit, ?string $url): string` and `PokemonCredit::extractUrl(string $credit): ?string` become `public static` (currently `private static`), so `CreditGroup` (Task 8) can reuse the same name/URL parsing without duplicating the regex or instantiating a throwaway `PokemonCredit`.

- [ ] **Step 1: Add a test exercising the methods directly**

Add these two test methods to `tests/src/Unit/ResponseObject/Common/PokemonCreditTest.php` (append inside the existing `final class PokemonCreditTest extends TestCase` body, alongside the 3 existing test methods):

```php
    public function testExtractUrlIsPubliclyCallable(): void
    {
        $this->assertSame(
            'https://github.com/msikma/pokesprite',
            PokemonCredit::extractUrl('PokéSprite - https://github.com/msikma/pokesprite'),
        );
    }

    public function testExtractNameIsPubliclyCallable(): void
    {
        $this->assertSame(
            'PokéSprite',
            PokemonCredit::extractName('PokéSprite - https://github.com/msikma/pokesprite', 'https://github.com/msikma/pokesprite'),
        );
    }
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `docker compose exec php php vendor/bin/phpunit --filter "testExtractUrlIsPubliclyCallable|testExtractNameIsPubliclyCallable" tests/src/Unit/ResponseObject/Common/PokemonCreditTest.php`
Expected: FAIL — `Call to private method App\ResponseObject\Common\PokemonCredit::extractUrl() from scope App\Tests\...`.

- [ ] **Step 3: Widen visibility**

In `src/ResponseObject/Common/PokemonCredit.php`, change both method signatures from `private static` to `public static` (no other change — the class stays exactly as-is otherwise):

```php
    public static function extractUrl(string $credit): ?string
    {
        if (1 === preg_match('/https?:\/\/\S+/', $credit, $matches)) {
            return $matches[0];
        }

        return null;
    }

    public static function extractName(string $credit, ?string $url): string
    {
        if (null === $url) {
            return $credit;
        }

        $name = trim(str_replace($url, '', $credit), " \t\n\r\0\x0B-");

        return '' !== $name ? $name : $credit;
    }
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/ResponseObject/Common/PokemonCreditTest.php`
Expected: PASS (5 tests total).

---

## Task 8: `pokenini-web` — new `CreditImage`/`CreditGroup` ResponseObjects

**Files:**
- Create: `src/ResponseObject/Common/CreditImage.php`
- Create: `src/ResponseObject/Common/CreditGroup.php`
- Create: `tests/src/Unit/ResponseObject/Common/CreditImageTest.php`
- Create: `tests/src/Unit/ResponseObject/Common/CreditGroupTest.php`

**Interfaces:**
- Consumes: `PokemonCredit::extractName()`/`extractUrl()` (Task 7, now public).
- Produces: `CreditImage` with getters `getPokemonSlug(): string`, `getPokemonName(): string`, `getPokemonFrenchName(): string`, `getPokemonIcon(): string`, `getSize(): string`, `isShiny(): bool`. `CreditGroup` with getters `getName(): string`, `getUrl(): ?string`, `getImages(): CreditImage[]` — this is what Task 9's services deserialize into and Task 10's template renders.

- [ ] **Step 1: Write the `CreditImage` test**

Create `tests/src/Unit/ResponseObject/Common/CreditImageTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Common;

use App\ResponseObject\Common\CreditImage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(CreditImage::class)]
final class CreditImageTest extends TestCase
{
    public function testGettersExposeConstructorValues(): void
    {
        $image = new CreditImage(
            pokemonSlug: 'bulbasaur',
            pokemonName: 'Bulbasaur',
            pokemonFrenchName: 'Bulbizarre',
            pokemonIcon: 'bulbasaur',
            size: 'small',
            isShiny: true,
        );

        $this->assertSame('bulbasaur', $image->getPokemonSlug());
        $this->assertSame('Bulbasaur', $image->getPokemonName());
        $this->assertSame('Bulbizarre', $image->getPokemonFrenchName());
        $this->assertSame('bulbasaur', $image->getPokemonIcon());
        $this->assertSame('small', $image->getSize());
        $this->assertTrue($image->isShiny());
    }
}
```

- [ ] **Step 2: Write the `CreditGroup` test**

Create `tests/src/Unit/ResponseObject/Common/CreditGroupTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Common;

use App\ResponseObject\Common\CreditGroup;
use App\ResponseObject\Common\CreditImage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(CreditGroup::class)]
final class CreditGroupTest extends TestCase
{
    public function testGettersExtractNameAndUrlAndExposeImages(): void
    {
        $image = new CreditImage(
            pokemonSlug: 'bulbasaur',
            pokemonName: 'Bulbasaur',
            pokemonFrenchName: 'Bulbizarre',
            pokemonIcon: 'bulbasaur',
            size: 'small',
            isShiny: false,
        );

        $group = new CreditGroup(
            credit: 'PokéSprite - https://github.com/msikma/pokesprite',
            images: [$image],
        );

        $this->assertSame('PokéSprite', $group->getName());
        $this->assertSame('https://github.com/msikma/pokesprite', $group->getUrl());
        $this->assertSame([$image], $group->getImages());
    }

    public function testGetUrlReturnsNullWhenCreditHasNoUrl(): void
    {
        $group = new CreditGroup(credit: 'PokéSprite', images: []);

        $this->assertNull($group->getUrl());
        $this->assertSame('PokéSprite', $group->getName());
    }
}
```

- [ ] **Step 3: Run the tests to verify they fail**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/ResponseObject/Common/CreditImageTest.php tests/src/Unit/ResponseObject/Common/CreditGroupTest.php`
Expected: FAIL — classes `App\ResponseObject\Common\CreditImage` / `CreditGroup` not found.

- [ ] **Step 4: Create `CreditImage`**

Create `src/ResponseObject/Common/CreditImage.php`:

```php
<?php

declare(strict_types=1);

namespace App\ResponseObject\Common;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class CreditImage
{
    public function __construct(
        #[SerializedName('pokemon_slug')]
        private readonly string $pokemonSlug,
        #[SerializedName('pokemon_name')]
        private readonly string $pokemonName,
        #[SerializedName('pokemon_french_name')]
        private readonly string $pokemonFrenchName,
        #[SerializedName('pokemon_icon')]
        private readonly string $pokemonIcon,
        private readonly string $size,
        #[SerializedName('is_shiny')]
        private readonly bool $isShiny,
    ) {}

    public function getPokemonSlug(): string
    {
        return $this->pokemonSlug;
    }

    public function getPokemonName(): string
    {
        return $this->pokemonName;
    }

    public function getPokemonFrenchName(): string
    {
        return $this->pokemonFrenchName;
    }

    public function getPokemonIcon(): string
    {
        return $this->pokemonIcon;
    }

    public function getSize(): string
    {
        return $this->size;
    }

    public function isShiny(): bool
    {
        return $this->isShiny;
    }
}
```

- [ ] **Step 5: Create `CreditGroup`**

Create `src/ResponseObject/Common/CreditGroup.php`:

```php
<?php

declare(strict_types=1);

namespace App\ResponseObject\Common;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class CreditGroup
{
    /**
     * @param CreditImage[] $images
     */
    public function __construct(
        #[SerializedName('credit')]
        private readonly string $credit,
        private readonly array $images,
    ) {}

    public function getName(): string
    {
        return PokemonCredit::extractName($this->credit, $this->getUrl());
    }

    public function getUrl(): ?string
    {
        return PokemonCredit::extractUrl($this->credit);
    }

    /**
     * @return CreditImage[]
     */
    public function getImages(): array
    {
        return $this->images;
    }
}
```

- [ ] **Step 6: Run the tests to verify they pass**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/ResponseObject/Common/CreditImageTest.php tests/src/Unit/ResponseObject/Common/CreditGroupTest.php`
Expected: PASS (3 tests total).

---

## Task 9: `pokenini-web` — thread `CreditGroup` through both credit services

**Files:**
- Modify: `src/Service/Back/GetCreditsService.php`
- Modify: `src/Service/GetCreditsService.php`
- Modify: `tests/src/Unit/Service/Back/GetCreditsServiceTest.php`
- Modify: `tests/src/Unit/Service/GetCreditsServiceTest.php`
- Modify: `tests/resources/unit/service/back/credits.json`
- Modify: `tests/resources/moco/Back/responses/credits.json`

**Interfaces:**
- Consumes: `CreditGroup`/`CreditImage` (Task 8).
- Produces: `Service\Back\GetCreditsService::get(): CreditGroup[]` and `Service\GetCreditsService::get(): CreditGroup[]` (cache key/tag/pool unchanged — still `'credits'` / `cache.labels`).

- [ ] **Step 1: Update the back-service unit fixture**

Replace `tests/resources/unit/service/back/credits.json`:

```json
[
    {
        "credit": "PokéSprite - https://github.com/msikma/pokesprite",
        "images": [
            {
                "pokemon_slug": "bulbasaur",
                "pokemon_name": "Bulbasaur",
                "pokemon_french_name": "Bulbizarre",
                "pokemon_icon": "bulbasaur",
                "size": "small",
                "is_shiny": false
            }
        ]
    },
    {
        "credit": "PokemonDB - https://pokemondb.net",
        "images": []
    }
]
```

- [ ] **Step 2: Update `Service\Back\GetCreditsService`'s test**

Replace `tests/src/Unit/Service/Back/GetCreditsServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\ResponseObject\Common\CreditGroup;
use App\ResponseObject\Common\CreditImage;
use App\Security\UserTokenServiceInterface;
use App\Service\Back\AbstractBackService;
use App\Service\Back\GetCreditsService;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @internal
 */
#[CoversClass(GetCreditsService::class)]
final class GetCreditsServiceTest extends AbstractTestBackService
{
    public const ENDPOINT = 'credits';
    public const RESPONSE_CONTENT = '/app/tests/resources/unit/service/back/credits.json';

    public function testGet(): void
    {
        $json = (new Filesystem())->readFile(self::RESPONSE_CONTENT);

        $credits = [
            new CreditGroup(
                credit: 'PokéSprite - https://github.com/msikma/pokesprite',
                images: [
                    new CreditImage(
                        pokemonSlug: 'bulbasaur',
                        pokemonName: 'Bulbasaur',
                        pokemonFrenchName: 'Bulbizarre',
                        pokemonIcon: 'bulbasaur',
                        size: 'small',
                        isShiny: false,
                    ),
                ],
            ),
            new CreditGroup(credit: 'PokemonDB - https://pokemondb.net', images: []),
        ];

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer
            ->expects($this->once())
            ->method('deserialize')
            ->with(
                $json,
                CreditGroup::class.'[]',
                'json',
            )
            ->willReturn($credits)
        ;

        /** @var GetCreditsService $service */
        $service = $this->getServiceWithLoggedUser(
            'GET',
            $json,
            self::ENDPOINT,
            [],
            $serializer,
        );

        $object = $service->get();

        $this->assertCount(2, $object);
        $this->assertSame('PokéSprite', $object[0]->getName());
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
        return new GetCreditsService(
            $logger,
            $client,
            $url,
            $cafilePath,
            $userTokenService,
            $serializer,
        );
    }
}
```

- [ ] **Step 3: Update the caching `GetCreditsService`'s test**

Replace `tests/src/Unit/Service/GetCreditsServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\ResponseObject\Common\CreditGroup;
use App\Service\Back\GetCreditsService as BackGetCreditsService;
use App\Service\GetCreditsService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;

/**
 * @internal
 */
#[CoversClass(GetCreditsService::class)]
final class GetCreditsServiceTest extends TestCase
{
    public function testGet(): void
    {
        $credits = [new CreditGroup(credit: 'PokéSprite - https://github.com/msikma/pokesprite', images: [])];

        $backService = $this->createMock(BackGetCreditsService::class);
        $backService
            ->expects($this->once())
            ->method('get')
            ->willReturn($credits)
        ;

        $service = new GetCreditsService($backService, new TagAwareAdapter(new ArrayAdapter()));

        $this->assertSame($credits, $service->get());
    }

    public function testCacheIsInvalidatedByCreditsTag(): void
    {
        $credits = [new CreditGroup(credit: 'PokéSprite - https://github.com/msikma/pokesprite', images: [])];

        $backService = $this->createMock(BackGetCreditsService::class);
        $backService
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturn($credits)
        ;

        $cache = new TagAwareAdapter(new ArrayAdapter());
        $service = new GetCreditsService($backService, $cache);

        $service->get();
        $cache->invalidateTags(['credits']);
        $service->get();
    }
}
```

- [ ] **Step 4: Run the tests to verify they fail**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/Back/GetCreditsServiceTest.php tests/src/Unit/Service/GetCreditsServiceTest.php`
Expected: FAIL — `App\ResponseObject\Common\CreditGroup` used in the test but the production services still deserialize/type-hint `PokemonCredit`.

- [ ] **Step 5: Update `Service\Back\GetCreditsService`**

Replace `src/Service/Back/GetCreditsService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\ResponseObject\Common\CreditGroup;

class GetCreditsService extends AbstractBackService
{
    /**
     * @return CreditGroup[]
     */
    public function get(): array
    {
        $json = $this->requestContent(
            'GET',
            '/credits'
        );

        /** @var CreditGroup[] */
        return $this->serializer->deserialize($json, CreditGroup::class.'[]', 'json');
    }
}
```

- [ ] **Step 6: Update `Service\GetCreditsService`**

Replace `src/Service/GetCreditsService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Service;

use App\ResponseObject\Common\CreditGroup;
use App\Service\Back\GetCreditsService as BackGetCreditsService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class GetCreditsService
{
    public function __construct(
        private readonly BackGetCreditsService $getService,
        #[Autowire(service: 'cache.labels')]
        private readonly TagAwareCacheInterface $creditsCache,
    ) {}

    /**
     * @return CreditGroup[]
     */
    public function get(): array
    {
        return $this->creditsCache->get('credits', function (ItemInterface $item): array {
            $item->tag(['credits']);

            return $this->getService->get();
        });
    }
}
```

- [ ] **Step 7: Update the Moco fixture used by the integration test (Task 10 depends on it)**

Replace `tests/resources/moco/Back/responses/credits.json` with the same 4-group content used in Task 6 Step 1 (`pokenini-back`'s Moco fixture) — this is `pokenini-web`'s own copy that its `moco.back` container serves:

```json
[
    {
        "credit": "PokéSprite - https://github.com/msikma/pokesprite",
        "images": [
            {
                "pokemon_slug": "bulbasaur",
                "pokemon_name": "Bulbasaur",
                "pokemon_french_name": "Bulbizarre",
                "pokemon_icon": "bulbasaur",
                "size": "small",
                "is_shiny": false
            },
            {
                "pokemon_slug": "ivysaur",
                "pokemon_name": "Ivysaur",
                "pokemon_french_name": "Herbizarre",
                "pokemon_icon": "ivysaur",
                "size": "big",
                "is_shiny": false
            }
        ]
    },
    {
        "credit": "Bulbapedia - https://bulbapedia.bulbagarden.net",
        "images": [
            {
                "pokemon_slug": "ivysaur",
                "pokemon_name": "Ivysaur",
                "pokemon_french_name": "Herbizarre",
                "pokemon_icon": "ivysaur",
                "size": "small",
                "is_shiny": false
            }
        ]
    },
    {
        "credit": "PokemonDB - https://pokemondb.net/sprites/bulbasaur",
        "images": [
            {
                "pokemon_slug": "bulbasaur",
                "pokemon_name": "Bulbasaur",
                "pokemon_french_name": "Bulbizarre",
                "pokemon_icon": "bulbasaur",
                "size": "big",
                "is_shiny": false
            }
        ]
    },
    {
        "credit": "Serebii - https://serebii.net",
        "images": [
            {
                "pokemon_slug": "venusaur",
                "pokemon_name": "Venusaur",
                "pokemon_french_name": "Florizarre",
                "pokemon_icon": "venusaur",
                "size": "small",
                "is_shiny": false
            }
        ]
    }
]
```

- [ ] **Step 8: Run the tests to verify they pass**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/Back/GetCreditsServiceTest.php tests/src/Unit/Service/GetCreditsServiceTest.php`
Expected: PASS (3 tests total).

---

## Task 10: `pokenini-web` — render grouped credits with expandable detail

**Files:**
- Modify: `templates/Credits/index.html.twig`
- Modify: `translations/messages+intl-icu.en.yaml`
- Modify: `translations/messages+intl-icu.fr.yaml`
- Modify: `public/css/base.css`
- Modify: `tests/src/Integration/Controller/Credits/CreditsTest.php`

**Interfaces:**
- Consumes: `CreditGroup[]` passed as the `credits` template variable by `CreditsController` (unchanged — it already does `$this->render('Credits/index.html.twig', ['credits' => $service->get()])`); the global Twig sprite-URL variables `pokemonIconUrl`/`pokemonImageUrl` (format strings, `dir` then `icon`, see `config/packages/twig.yaml`); the existing translation keys `album.icon.title.regular` / `album.icon.title.shiny` (reused for the regular/shiny label).
- Produces: the rendered `/{_locale}/credits` page — no PHP interface changes.

- [ ] **Step 1: Update the integration test first**

Replace `tests/src/Integration/Controller/Credits/CreditsTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Credits;

use App\Controller\CreditsController;
use App\Tests\Common\Traits\TestNavTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[Group('api-mocked-testing')]
#[CoversClass(CreditsController::class)]
final class CreditsTest extends WebTestCase
{
    use TestNavTrait;

    public function testIndex(): void
    {
        $client = self::createClient();

        $crawler = $client->request('GET', '/fr/credits');

        $this->assertResponseIsSuccessful();

        $this->assertSame('Pokénini Crédits', $crawler->filter('title')->text());
        $this->assertSame('Crédits', $crawler->filter('h1')->text());

        $items = $crawler->filter('.list-group-item');
        $this->assertCount(4, $items);

        // Sorted by image count descending: PokéSprite (2 images) comes first,
        // see tests/resources/moco/Back/responses/credits.json.
        $first = $items->eq(0);
        $this->assertSame('PokéSprite', $first->filter('.credit-source-link')->text());
        $this->assertSame('https://github.com/msikma/pokesprite', $first->filter('.credit-source-link')->attr('href'));
        $this->assertStringContainsString('2', $first->filter('.credit-detail-toggle')->text());

        $detailItems = $first->filter('.credit-detail-list li');
        $this->assertCount(2, $detailItems);
        $this->assertStringContainsString('Bulbizarre', $detailItems->eq(0)->text());
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Credits/CreditsTest.php`
Expected: FAIL — the current template has no `.credit-source-link`/`.credit-detail-toggle`/`.credit-detail-list` elements, and only 3 `.list-group-item` (from the old 3-entry fixture the test previously used implicitly) vs the new 4-group fixture.

- [ ] **Step 3: Add translation keys**

In `translations/messages+intl-icu.en.yaml`, replace the existing `credit:` block (currently just `credit:\n  tooltip: "Image credit"`) with:

```yaml
credit:
  tooltip: "Image credit"
  detail:
    count: "{count, plural, one {# image} other {# images}}"
    size:
      small: "small sprite"
      big: "big sprite"
```

In `translations/messages+intl-icu.fr.yaml`, replace the existing `credit:` block (currently just `credit:\n  tooltip: "Crédit image"`) with:

```yaml
credit:
  tooltip: "Crédit image"
  detail:
    count: "{count, plural, one {# image} other {# images}}"
    size:
      small: "petit sprite"
      big: "grand sprite"
```

- [ ] **Step 4: Rewrite the template**

Replace `templates/Credits/index.html.twig`:

```twig
{% set locale = app.request.locale %}

{% extends 'base.html.twig' %}
{% use '_nav.html.twig' %}

{% block title %}Pokénini {{ 'title.credits'|trans }}{% endblock title %}

{% block container %}
  <div class="row justify-content-center">
    <div class="col-6">
      <h1>{{ 'title.credits'|trans }}</h1>

      <ul class="list-group">
        {% for group in credits %}
          <li class="list-group-item">
            {% if group.url is not null %}
              <a class="credit-source-link" href="{{ group.url }}" target="_blank" rel="noopener">{{ group.name }}</a>
            {% else %}
              <span class="credit-source-link">{{ group.name }}</span>
            {% endif %}

            <button
              class="btn btn-link p-0 credit-detail-toggle"
              type="button"
              data-bs-toggle="collapse"
              data-bs-target="#credit-detail-{{ loop.index0 }}"
              aria-expanded="false"
              aria-controls="credit-detail-{{ loop.index0 }}"
            >
              {{ 'credit.detail.count'|trans({count: group.images|length}) }}
            </button>

            <ul class="collapse credit-detail-list list-unstyled" id="credit-detail-{{ loop.index0 }}">
              {% for image in group.images %}
                {% set pokemonName = locale is same as('fr') ? image.pokemonFrenchName : image.pokemonName %}
                {% set dir = image.shiny ? 'shiny' : 'regular' %}
                {% set thumbnailUrl = image.size is same as('big') ? pokemonImageUrl|format(dir, image.pokemonIcon) : pokemonIconUrl|format(dir, image.pokemonIcon) %}

                <li
                  data-bs-toggle="tooltip"
                  data-bs-html="true"
                  data-bs-title="{{ '<img src="%s" alt="" class="credit-detail-thumbnail">'|format(thumbnailUrl) }}"
                >
                  {{ pokemonName }} — {{ ('credit.detail.size.'~image.size)|trans }}, {{ ('album.icon.title.'~dir)|trans }}
                </li>
              {% endfor %}
            </ul>
          </li>
        {% endfor %}
      </ul>
    </div>
  </div>
{% endblock container %}
```

Note: Twig property access `image.shiny` calls `CreditImage::isShiny()` (Twig tries `isX()` after `getX()` fails, matching the boolean-getter convention already used on `PokemonCredit`-adjacent code elsewhere in this codebase).

- [ ] **Step 5: Add thumbnail/toggle styling**

Append to `public/css/base.css`:

```css
.credit-detail-toggle {
    font-size: .875rem;
}
.credit-detail-list {
    margin-top: .5rem;
    margin-bottom: 0;
}
.credit-detail-thumbnail {
    max-width: 48px;
    max-height: 48px;
}
```

- [ ] **Step 6: Run the test to verify it passes**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Credits/CreditsTest.php`
Expected: PASS.

- [ ] **Step 7: Manually verify in a browser**

Start the stack (`make start` if not already running) and visit `http://localhost/fr/credits`: confirm 4 list items sorted with PokéSprite first showing "(2 images)", clicking the count expands a 2-line list ("Bulbizarre — petit sprite, Normal" and "Herbizarre — grand sprite, Normal"), and hovering a line shows a tooltip with the actual sprite thumbnail. Also check `http://localhost/en/credits` for the English labels.

- [ ] **Step 8: Run the full `pokenini-web` suite and quality gate**

Run: `make tests && make quality && make measures`
Expected: all green.

---

## Self-Review Notes

- **Spec coverage:** Task 1–5 cover §1 (`pokenini-api` data model/query/DTO/controller); Task 6 covers §2/§3's `pokenini-back` half (§3 point 2, the `/credits` endpoint — §3 point 1, the embedded per-image credit, is explicitly out of scope and untouched); Tasks 7–10 cover §4 (`pokenini-web` display). Testing requirements from spec §5 are folded into each task's own test steps rather than a separate task, since each layer's tests are only meaningful once that layer's code exists.
- **Placeholder scan:** no TBD/TODO; every step has concrete code or an exact command.
- **Type consistency:** `getAllGroupedBySource()`'s row shape (Task 3) matches `findAllWithPokemon()`'s row shape (Task 2) minus the `source` key (extracted as the group key); `ImageCreditGroupResponseFactory` (Task 4) consumes exactly that shape; `CreditGroup`/`CreditImage` (Task 8) property names match what `Service\Back\GetCreditsService` (Task 9) deserializes and what the template (Task 10) reads (`image.pokemonFrenchName`, `image.shiny`, `image.pokemonIcon`, `image.size`, `group.url`, `group.name`, `group.images`).
