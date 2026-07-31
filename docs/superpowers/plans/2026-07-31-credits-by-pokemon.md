# Credits By Pokemon Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the by-source Credits page with one that lists every Pokémon species (including those with no credit at all) and shows, per Pokémon, an expandable detail of its up to 4 image-credit slots (small/big × regular/shiny).

**Architecture:** `pokenini-api`'s `/credits` endpoint currently joins `pokemon_image_credit` to `pokemon` filtered to `WHERE source IS NOT NULL`, then groups in PHP by source string. This plan replaces that with a query rooted on `pokemon` (using the same 4-way `LEFT JOIN` pattern `PokedexRepository` already uses for trainer/dex-scoped queries, but unscoped) that returns one row per species regardless of credit, threads the new per-Pokémon shape through `pokenini-back` (pure pass-through, no code change beyond docblocks/fixtures), and re-renders `pokenini-web`'s Credits page as a per-Pokémon collapsible list.

**Tech Stack:** Symfony 8 / PHP ≥ 8.5 across all three repos, Doctrine DBAL (raw SQL) in `pokenini-api`, Symfony Serializer in `pokenini-web`, Twig + Bootstrap 5 (collapse component) for display.

## Global Constraints

- `declare(strict_types=1)` in every PHP file (all three repos).
- Entities/DTOs/Controllers/test classes are `final`; Repository/Service classes are NOT `final` (PHPUnit mocking).
- Every test class: `/** @internal */` docblock + `#[CoversClass(...)]`; `pokenini-api` tests use `#[Test]`-attributed methods (not `test`-prefixed names); `pokenini-back`/`pokenini-web` tests use `test`-prefixed method names (no `#[Test]` attribute) — match each repo's existing convention exactly (see per-task examples, copied from each repo's current files).
- `pokenini-api` DTOs need an explicit `#[SerializedName(...)]` on every constructor property whose JSON key differs from the camelCase PHP name (no global name converter is configured in any of the 3 repos — confirmed by every existing multi-word DTO property carrying one, e.g. `PokemonDataResponse::$smallRegularCredit` on the API side and `PokemonData::$smallRegularCredit` on the web side, both shown as reference in Task 3 and Task 8).
- 100% coverage + 100% Mutation Score Index (Infection) required in all three repos (`make measures` / `make coverage` + `make infection`); PHPStan level 9; Psalm strict; Deptrac clean; PHP CS Fixer clean.
- Integration tests use Moco fixtures (never mock the HTTP client) in `pokenini-back` and `pokenini-web`.
- **Do not run `git commit` at any point while executing this plan.** The user's standing instruction is to never commit proactively — leave all changes staged/unstaged for the user to review and commit themselves. Each task ends with "verify tests pass", not "commit".
- No database migration is needed — `pokemon_image_credit` already has every column this feature needs.
- The per-image credit badge shown on Album/Election pages (`_image_macros.html.twig`, `PokemonCredit` on `pokenini-web`, `ImageCreditResponse`/`PokemonDataResponse` on `pokenini-api`) is **out of scope and must not change** — only the standalone `GET /credits` list endpoint and the Credits page change. `creditBadge()` (the macro inside `_image_macros.html.twig`) is reused as-is, not modified.
- Implementation refinement vs. the approved spec: the spec's "sprite-thumbnail tooltip" detail is replaced by showing the Pokémon's real regular-form sprite icon directly at the row header (reusing the existing `pokemon-icon` CSS class and `pokemonIconUrl` Twig global already used by `_image_macros.html.twig`) — this is simpler now that Pokémon (not source) is the top-level grouping, and needs no new CSS or JS. Behavior/scope is otherwise unchanged from the spec.

---

## Task 1: `pokenini-api` — repository query rooted on every Pokémon species

**Files:**
- Modify: `src/Repository/PokemonImageCreditRepository.php`
- Modify (replace test): `tests/src/Integration/Repository/PokemonImageCreditRepositoryTest.php`

**Interfaces:**
- Consumes: `pokemon` + `pokemon_image_credit` tables (via `$this->getEntityManager()->getConnection()`).
- Produces: `PokemonImageCreditRepository::findAllPokemonWithCredits(): array` returning
  `array<array{pokemon_slug: string, pokemon_name: string, pokemon_french_name: string, pokemon_icon: string, small_regular_credit: ?string, small_shiny_credit: ?string, big_regular_credit: ?string, big_shiny_credit: ?string}>`,
  one row per species (**including species with zero credited slots**), ordered by `p.national_dex_number, p.family_order`. This replaces `findAllWithPokemon()`, which is removed (its only caller, `ImageCreditsService::getAllGroupedBySource()`, is replaced in Task 2).

The fixture set (`fixtures/pokemons.yaml`, 26 species) + `fixtures/pokemon_image_credits.yaml` (5 credit rows, 4 with a non-null `source`, 1 with a null `source` on `douze`) gives exactly the cases needed: fully/partially credited species (bulbasaur, ivysaur, venusaur), a species with a credit row whose `source` is null (`douze` — its slot must still show as `null`, not the string `null`), and species with **no** `pokemon_image_credit` row at all (e.g. `charmander`).

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
    public function findAllPokemonWithCreditsReturnsOneRowPerSpeciesOrderedByNationalDexNumber(): void
    {
        $repo = self::getContainer()->get(PokemonImageCreditRepository::class);

        $result = $repo->findAllPokemonWithCredits();

        // 26 species in fixtures/pokemons.yaml, including ones with zero
        // pokemon_image_credit rows at all — this query must return all of them.
        self::assertCount(26, $result);
        self::assertSame('bulbasaur', $result[0]['pokemon_slug']);
        self::assertSame('ivysaur', $result[1]['pokemon_slug']);
        self::assertSame('venusaur', $result[2]['pokemon_slug']);
        self::assertSame('douze', $result[25]['pokemon_slug']);
    }

    #[Test]
    public function findAllPokemonWithCreditsReturnsEachOfTheFourSlotsWithTheirIndividualSources(): void
    {
        $repo = self::getContainer()->get(PokemonImageCreditRepository::class);

        $result = $repo->findAllPokemonWithCredits();

        self::assertSame(
            [
                'pokemon_slug' => 'bulbasaur',
                'pokemon_name' => 'Bulbasaur',
                'pokemon_french_name' => 'Bulbizarre',
                'pokemon_icon' => 'bulbasaur',
                'small_regular_credit' => 'PokéSprite - https://github.com/msikma/pokesprite',
                'small_shiny_credit' => null,
                'big_regular_credit' => 'PokemonDB - https://pokemondb.net/sprites/bulbasaur',
                'big_shiny_credit' => null,
            ],
            $result[0],
        );
    }

    #[Test]
    public function findAllPokemonWithCreditsReturnsNullForASpeciesWithNoCreditRowAtAll(): void
    {
        $repo = self::getContainer()->get(PokemonImageCreditRepository::class);

        $result = $repo->findAllPokemonWithCredits();

        $charmander = self::findRowBySlug($result, 'charmander');
        self::assertSame(
            [
                'pokemon_slug' => 'charmander',
                'pokemon_name' => 'Charmander',
                'pokemon_french_name' => 'Salamèche',
                'pokemon_icon' => 'charmander',
                'small_regular_credit' => null,
                'small_shiny_credit' => null,
                'big_regular_credit' => null,
                'big_shiny_credit' => null,
            ],
            $charmander,
        );
    }

    #[Test]
    public function findAllPokemonWithCreditsReturnsNullForASpeciesWhoseOnlyCreditRowHasANullSource(): void
    {
        $repo = self::getContainer()->get(PokemonImageCreditRepository::class);

        $result = $repo->findAllPokemonWithCredits();

        $douze = self::findRowBySlug($result, 'douze');
        self::assertSame(null, $douze['small_regular_credit']);
        self::assertSame(null, $douze['small_shiny_credit']);
        self::assertSame(null, $douze['big_regular_credit']);
        self::assertSame(null, $douze['big_shiny_credit']);
    }

    /**
     * @param array<array{pokemon_slug: string, pokemon_name: string, pokemon_french_name: string, pokemon_icon: string, small_regular_credit: ?string, small_shiny_credit: ?string, big_regular_credit: ?string, big_shiny_credit: ?string}> $rows
     *
     * @return array{pokemon_slug: string, pokemon_name: string, pokemon_french_name: string, pokemon_icon: string, small_regular_credit: ?string, small_shiny_credit: ?string, big_regular_credit: ?string, big_shiny_credit: ?string}
     */
    private static function findRowBySlug(array $rows, string $slug): array
    {
        foreach ($rows as $row) {
            if ($row['pokemon_slug'] === $slug) {
                return $row;
            }
        }

        self::fail(\sprintf('No row found for slug "%s"', $slug));
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/Repository/PokemonImageCreditRepositoryTest.php`
Expected: FAIL — `findAllPokemonWithCredits()` does not exist (`Error: Call to undefined method`).

- [ ] **Step 3: Implement `findAllPokemonWithCredits()`**

Replace `src/Repository/PokemonImageCreditRepository.php`:

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
     *   pokemon_slug: string,
     *   pokemon_name: string,
     *   pokemon_french_name: string,
     *   pokemon_icon: string,
     *   small_regular_credit: ?string,
     *   small_shiny_credit: ?string,
     *   big_regular_credit: ?string,
     *   big_shiny_credit: ?string,
     * }>
     */
    public function findAllPokemonWithCredits(): array
    {
        $sql = <<<'SQL'
            SELECT      p.slug AS pokemon_slug,
                        p.name AS pokemon_name,
                        p.french_name AS pokemon_french_name,
                        p.icon_name AS pokemon_icon,
                        pic_sr.source AS small_regular_credit,
                        pic_ss.source AS small_shiny_credit,
                        pic_br.source AS big_regular_credit,
                        pic_bs.source AS big_shiny_credit
            FROM        pokemon AS p
                LEFT JOIN pokemon_image_credit AS pic_sr ON p.id = pic_sr.pokemon_id AND pic_sr.size = 'small' AND pic_sr.is_shiny = false
                LEFT JOIN pokemon_image_credit AS pic_ss ON p.id = pic_ss.pokemon_id AND pic_ss.size = 'small' AND pic_ss.is_shiny = true
                LEFT JOIN pokemon_image_credit AS pic_br ON p.id = pic_br.pokemon_id AND pic_br.size = 'big'   AND pic_br.is_shiny = false
                LEFT JOIN pokemon_image_credit AS pic_bs ON p.id = pic_bs.pokemon_id AND pic_bs.size = 'big'   AND pic_bs.is_shiny = true
            ORDER BY    p.national_dex_number, p.family_order
            SQL;

        /** @var array<array{pokemon_slug: string, pokemon_name: string, pokemon_french_name: string, pokemon_icon: string, small_regular_credit: ?string, small_shiny_credit: ?string, big_regular_credit: ?string, big_shiny_credit: ?string}> */
        return $this->getEntityManager()->getConnection()->fetchAllAssociative($sql);
    }
}
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/Repository/PokemonImageCreditRepositoryTest.php`
Expected: PASS (4 tests).

---

## Task 2: `pokenini-api` — service delegates straight to the repository

**Files:**
- Modify: `src/Service/ImageCreditsService.php`
- Modify (replace test): `tests/src/Unit/Service/ImageCreditsServiceTest.php`

**Interfaces:**
- Consumes: `PokemonImageCreditRepository::findAllPokemonWithCredits(): array` (Task 1's return shape).
- Produces: `ImageCreditsService::getAllByPokemon(): array`, same shape, no transformation (the SQL already produces one row per Pokémon — the old by-source grouping logic is gone, there is nothing left to group). Replaces `getAllGroupedBySource()`.

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
    public function getAllByPokemonReturnsTheRepositoryRowsUnchanged(): void
    {
        $rows = [
            [
                'pokemon_slug' => 'bulbasaur',
                'pokemon_name' => 'Bulbasaur',
                'pokemon_french_name' => 'Bulbizarre',
                'pokemon_icon' => 'bulbasaur',
                'small_regular_credit' => 'PokéSprite - https://github.com/msikma/pokesprite',
                'small_shiny_credit' => null,
                'big_regular_credit' => null,
                'big_shiny_credit' => null,
            ],
        ];

        $this->repository
            ->expects(self::once())
            ->method('findAllPokemonWithCredits')
            ->willReturn($rows)
        ;

        self::assertSame($rows, $this->service->getAllByPokemon());
    }

    #[Test]
    public function getAllByPokemonReturnsEmptyArrayWhenRepositoryHasNoRows(): void
    {
        $this->repository
            ->expects(self::once())
            ->method('findAllPokemonWithCredits')
            ->willReturn([])
        ;

        self::assertSame([], $this->service->getAllByPokemon());
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/ImageCreditsServiceTest.php`
Expected: FAIL — `getAllByPokemon()` does not exist.

- [ ] **Step 3: Implement `getAllByPokemon()`**

Replace `src/Service/ImageCreditsService.php`:

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
     *   pokemon_slug: string,
     *   pokemon_name: string,
     *   pokemon_french_name: string,
     *   pokemon_icon: string,
     *   small_regular_credit: ?string,
     *   small_shiny_credit: ?string,
     *   big_regular_credit: ?string,
     *   big_shiny_credit: ?string,
     * }>
     */
    public function getAllByPokemon(): array
    {
        return $this->repository->findAllPokemonWithCredits();
    }
}
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/ImageCreditsServiceTest.php`
Expected: PASS (2 tests).

---

## Task 3: `pokenini-api` — new `PokemonCreditResponse` DTO and factory, delete the by-source ones

**Files:**
- Create: `src/DTO/Response/PokemonCreditResponse.php`
- Create: `src/Factory/PokemonCreditResponseFactory.php`
- Create: `tests/src/Unit/Factory/PokemonCreditResponseFactoryTest.php`
- Delete: `src/DTO/Response/ImageCreditGroupResponse.php`
- Delete: `src/DTO/Response/ImageCreditImageResponse.php`
- Delete: `src/Factory/ImageCreditGroupResponseFactory.php`
- Delete: `tests/src/Unit/DTO/Response/ImageCreditGroupResponseTest.php`
- Delete: `tests/src/Unit/DTO/Response/ImageCreditImageResponseTest.php`
- Delete: `tests/src/Unit/Factory/ImageCreditGroupResponseFactoryTest.php`

**Interfaces:**
- Consumes: `ImageCreditsService::getAllByPokemon()`'s return shape (Task 2); the existing `ImageCreditResponse{public readonly string $credit}` DTO (`src/DTO/Response/ImageCreditResponse.php`, **untouched** — it's shared with `PokemonDataResponse` and stays exactly as-is).
- Produces: `PokemonCreditResponseFactory::fromRows(array $rows): PokemonCreditResponse[]`, where `PokemonCreditResponse` has public readonly `string $pokemonSlug, $pokemonName, $pokemonFrenchName, $pokemonIcon` and `?ImageCreditResponse $smallRegularCredit, $smallShinyCredit, $bigRegularCredit, $bigShinyCredit`.

**Do not touch** `src/DTO/Response/ImageCreditResponse.php`, `AlbumPokemonResponseFactory`, `ElectionPokemonResponseFactory`, `ElectionEloResponseFactory`, or `PokemonDataResponse` — those are unrelated to this feature and keep using `ImageCreditResponse` for the embedded per-image credit fields exactly as before.

- [ ] **Step 1: Delete the obsolete by-source DTOs, factory and their tests**

Delete these 5 files (all superseded — nothing else references them once Task 4 rewires the controller):
- `src/DTO/Response/ImageCreditGroupResponse.php`
- `src/DTO/Response/ImageCreditImageResponse.php`
- `src/Factory/ImageCreditGroupResponseFactory.php`
- `tests/src/Unit/DTO/Response/ImageCreditGroupResponseTest.php`
- `tests/src/Unit/DTO/Response/ImageCreditImageResponseTest.php`
- `tests/src/Unit/Factory/ImageCreditGroupResponseFactoryTest.php`

- [ ] **Step 2: Write the new factory test**

Create `tests/src/Unit/Factory/PokemonCreditResponseFactoryTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Factory;

use App\DTO\Response\ImageCreditResponse;
use App\DTO\Response\PokemonCreditResponse;
use App\Factory\PokemonCreditResponseFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(PokemonCreditResponseFactory::class)]
final class PokemonCreditResponseFactoryTest extends TestCase
{
    #[Test]
    public function fromRowsBuildsOneResponsePerRowWrappingNonNullCreditsOnly(): void
    {
        $responses = PokemonCreditResponseFactory::fromRows([
            [
                'pokemon_slug' => 'bulbasaur',
                'pokemon_name' => 'Bulbasaur',
                'pokemon_french_name' => 'Bulbizarre',
                'pokemon_icon' => 'bulbasaur',
                'small_regular_credit' => 'PokéSprite - https://github.com/msikma/pokesprite',
                'small_shiny_credit' => null,
                'big_regular_credit' => 'PokemonDB - https://pokemondb.net/sprites/bulbasaur',
                'big_shiny_credit' => null,
            ],
        ]);

        self::assertCount(1, $responses);
        $response = $responses[0];
        self::assertInstanceOf(PokemonCreditResponse::class, $response);
        self::assertSame('bulbasaur', $response->pokemonSlug);
        self::assertSame('Bulbasaur', $response->pokemonName);
        self::assertSame('Bulbizarre', $response->pokemonFrenchName);
        self::assertSame('bulbasaur', $response->pokemonIcon);

        self::assertInstanceOf(ImageCreditResponse::class, $response->smallRegularCredit);
        self::assertSame('PokéSprite - https://github.com/msikma/pokesprite', $response->smallRegularCredit->credit);
        self::assertNull($response->smallShinyCredit);
        self::assertInstanceOf(ImageCreditResponse::class, $response->bigRegularCredit);
        self::assertSame('PokemonDB - https://pokemondb.net/sprites/bulbasaur', $response->bigRegularCredit->credit);
        self::assertNull($response->bigShinyCredit);
    }

    #[Test]
    public function fromRowsBuildsAllFourNullCreditsForASpeciesWithNoCredit(): void
    {
        $responses = PokemonCreditResponseFactory::fromRows([
            [
                'pokemon_slug' => 'charmander',
                'pokemon_name' => 'Charmander',
                'pokemon_french_name' => 'Salamèche',
                'pokemon_icon' => 'charmander',
                'small_regular_credit' => null,
                'small_shiny_credit' => null,
                'big_regular_credit' => null,
                'big_shiny_credit' => null,
            ],
        ]);

        self::assertNull($responses[0]->smallRegularCredit);
        self::assertNull($responses[0]->smallShinyCredit);
        self::assertNull($responses[0]->bigRegularCredit);
        self::assertNull($responses[0]->bigShinyCredit);
    }

    #[Test]
    public function fromRowsHandlesEmptyArray(): void
    {
        self::assertSame([], PokemonCreditResponseFactory::fromRows([]));
    }
}
```

- [ ] **Step 3: Run the test to verify it fails**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Factory/PokemonCreditResponseFactoryTest.php`
Expected: FAIL — class `App\Factory\PokemonCreditResponseFactory` not found.

- [ ] **Step 4: Create the DTO and factory**

Create `src/DTO/Response/PokemonCreditResponse.php`:

```php
<?php

declare(strict_types=1);

namespace App\DTO\Response;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class PokemonCreditResponse
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
        #[SerializedName('small_regular_credit')]
        public readonly ?ImageCreditResponse $smallRegularCredit,
        #[SerializedName('small_shiny_credit')]
        public readonly ?ImageCreditResponse $smallShinyCredit,
        #[SerializedName('big_regular_credit')]
        public readonly ?ImageCreditResponse $bigRegularCredit,
        #[SerializedName('big_shiny_credit')]
        public readonly ?ImageCreditResponse $bigShinyCredit,
    ) {}
}
```

Create `src/Factory/PokemonCreditResponseFactory.php`:

```php
<?php

declare(strict_types=1);

namespace App\Factory;

use App\DTO\Response\ImageCreditResponse;
use App\DTO\Response\PokemonCreditResponse;

final class PokemonCreditResponseFactory
{
    /**
     * @param array<array{
     *   pokemon_slug: string,
     *   pokemon_name: string,
     *   pokemon_french_name: string,
     *   pokemon_icon: string,
     *   small_regular_credit: ?string,
     *   small_shiny_credit: ?string,
     *   big_regular_credit: ?string,
     *   big_shiny_credit: ?string,
     * }> $rows
     *
     * @return PokemonCreditResponse[]
     */
    public static function fromRows(array $rows): array
    {
        return array_map(self::fromRow(...), $rows);
    }

    /**
     * @param array{
     *   pokemon_slug: string,
     *   pokemon_name: string,
     *   pokemon_french_name: string,
     *   pokemon_icon: string,
     *   small_regular_credit: ?string,
     *   small_shiny_credit: ?string,
     *   big_regular_credit: ?string,
     *   big_shiny_credit: ?string,
     * } $row
     */
    private static function fromRow(array $row): PokemonCreditResponse
    {
        return new PokemonCreditResponse(
            pokemonSlug: $row['pokemon_slug'],
            pokemonName: $row['pokemon_name'],
            pokemonFrenchName: $row['pokemon_french_name'],
            pokemonIcon: $row['pokemon_icon'],
            smallRegularCredit: self::buildCredit($row['small_regular_credit']),
            smallShinyCredit: self::buildCredit($row['small_shiny_credit']),
            bigRegularCredit: self::buildCredit($row['big_regular_credit']),
            bigShinyCredit: self::buildCredit($row['big_shiny_credit']),
        );
    }

    private static function buildCredit(?string $credit): ?ImageCreditResponse
    {
        return null !== $credit ? new ImageCreditResponse($credit) : null;
    }
}
```

- [ ] **Step 5: Run the test to verify it passes**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Factory/PokemonCreditResponseFactoryTest.php`
Expected: PASS (3 tests).

---

## Task 4: `pokenini-api` — wire the controller to the new shape

**Files:**
- Modify: `src/Controller/ImageCreditsController.php`
- Modify (replace test): `tests/src/Integration/Controller/ImageCreditsControllerTest.php`

**Interfaces:**
- Consumes: `ImageCreditsService::getAllByPokemon()` (Task 2), `PokemonCreditResponseFactory::fromRows()` (Task 3).
- Produces: `GET /credits` now returns JSON shaped as
  `[{"pokemon_slug": "...", "pokemon_name": "...", "pokemon_french_name": "...", "pokemon_icon": "...", "small_regular_credit": {"credit": "..."}|null, "small_shiny_credit": ..., "big_regular_credit": ..., "big_shiny_credit": ...}, ...]`, one entry per species, ordered by national dex number.

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
    public function getReturnsOneEntryPerSpeciesOrderedByNationalDexNumberFromFixtures(): void
    {
        $client = self::createClient();
        $client->request('GET', '/credits', [], [], [
            'PHP_AUTH_USER' => 'web',
            'PHP_AUTH_PW' => 'douze',
        ]);

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);

        /** @var array<int, array{pokemon_slug: string, pokemon_name: string, pokemon_french_name: string, pokemon_icon: string, small_regular_credit: ?array{credit: string}, small_shiny_credit: ?array{credit: string}, big_regular_credit: ?array{credit: string}, big_shiny_credit: ?array{credit: string}}> $data */
        $data = json_decode($content, associative: true);

        self::assertCount(26, $data);
        self::assertSame('bulbasaur', $data[0]['pokemon_slug']);
        self::assertSame(
            'PokéSprite - https://github.com/msikma/pokesprite',
            $data[0]['small_regular_credit']['credit'] ?? null,
        );
        self::assertNull($data[0]['small_shiny_credit']);

        $charmander = self::findEntryBySlug($data, 'charmander');
        self::assertNull($charmander['small_regular_credit']);
        self::assertNull($charmander['small_shiny_credit']);
        self::assertNull($charmander['big_regular_credit']);
        self::assertNull($charmander['big_shiny_credit']);
    }

    /**
     * @param array<int, array{pokemon_slug: string, small_regular_credit: ?array{credit: string}, small_shiny_credit: ?array{credit: string}, big_regular_credit: ?array{credit: string}, big_shiny_credit: ?array{credit: string}}> $data
     *
     * @return array{pokemon_slug: string, small_regular_credit: ?array{credit: string}, small_shiny_credit: ?array{credit: string}, big_regular_credit: ?array{credit: string}, big_shiny_credit: ?array{credit: string}}
     */
    private static function findEntryBySlug(array $data, string $slug): array
    {
        foreach ($data as $entry) {
            if ($entry['pokemon_slug'] === $slug) {
                return $entry;
            }
        }

        self::fail(\sprintf('No entry found for slug "%s"', $slug));
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/ImageCreditsControllerTest.php`
Expected: FAIL — response still contains the old grouped `{"credit": "...", "images": [...]}` shape.

- [ ] **Step 3: Update the controller**

Replace `src/Controller/ImageCreditsController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\Response\PokemonCreditResponse;
use App\Factory\PokemonCreditResponseFactory;
use App\Service\ImageCreditsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Attribute\Serialize;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/credits')]
final class ImageCreditsController extends AbstractController
{
    /** @return PokemonCreditResponse[] */
    #[Route(path: '', methods: ['GET'])]
    #[Serialize]
    public function get(ImageCreditsService $service): array
    {
        return PokemonCreditResponseFactory::fromRows($service->getAllByPokemon());
    }
}
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/ImageCreditsControllerTest.php`
Expected: PASS (2 tests).

---

## Task 5: `pokenini-api` — full suite and quality gate

**Files:** none (verification-only task).

- [ ] **Step 1: Run the full test suite**

Run: `make tests`
Expected: all green. This confirms nothing in `AlbumPokemonResponseFactory`/`ElectionPokemonResponseFactory`/`ElectionEloResponseFactory` (which still use the untouched `ImageCreditResponse`) broke, and that the deleted `ImageCreditGroupResponse`/`ImageCreditImageResponse`/`ImageCreditGroupResponseFactory`/old repository+service methods have no remaining references.

- [ ] **Step 2: Run quality and measures gates**

Run: `make quality && make measures`
Expected: all green (PHPStan level 9, Psalm strict, Deptrac, PHP CS Fixer, 100% coverage, 100% MSI).

---

## Task 6: `pokenini-back` — fixtures and docblocks for the new shape

**Files:**
- Modify: `tests/resources/moco/Api/responses/credits.json`
- Modify: `tests/resources/functional/controller/Credits/all.json`
- Modify: `tests/resources/unit/service/api/credits.json`
- Modify: `src/Service/Api/GetCreditsApiService.php` (PHPDoc only, no logic change)
- Modify: `tests/src/Unit/Service/Api/GetCreditsApiServiceTest.php`

**Interfaces:**
- Consumes: nothing new — `CreditsController`/`GetCreditsApiService` are an untyped pass-through (`JsonResponse($service->get())` / `JsonDecoder::decode($json)`), so no production **logic** change is needed, only the PHPDoc type and the fixtures it describes.
- Produces: `GET /credits` on `pokenini-back` proxies the new per-Pokémon shape unchanged. Cache key (`KeyMaker::getCreditsKey()` = `'credits_v2'`) and the `labels`-bucket invalidator are unaffected — they cache/invalidate an opaque JSON string, indifferent to its shape.

Fixture design: 3 illustrative Pokémon covering the 3 UI states the web template must render — fully credited (bulbasaur, 4/4 slots), partially credited (ivysaur, 1/4), and uncredited (venusaur, 0/4). These don't need to match `pokenini-api`'s real fixture data — `pokenini-back`/`pokenini-web` never talk to a real database, they only replay whatever JSON Moco is configured to return.

- [ ] **Step 1: Update the Moco response fixture**

Replace `tests/resources/moco/Api/responses/credits.json`:

```json
[
    {
        "pokemon_slug": "bulbasaur",
        "pokemon_name": "Bulbasaur",
        "pokemon_french_name": "Bulbizarre",
        "pokemon_icon": "bulbasaur",
        "small_regular_credit": { "credit": "PokéSprite - https://github.com/msikma/pokesprite" },
        "small_shiny_credit": { "credit": "PokéSprite - https://github.com/msikma/pokesprite" },
        "big_regular_credit": { "credit": "PokemonDB - https://pokemondb.net/sprites/bulbasaur" },
        "big_shiny_credit": { "credit": "Bulbapedia - https://bulbapedia.bulbagarden.net" }
    },
    {
        "pokemon_slug": "ivysaur",
        "pokemon_name": "Ivysaur",
        "pokemon_french_name": "Herbizarre",
        "pokemon_icon": "ivysaur",
        "small_regular_credit": { "credit": "Serebii - https://serebii.net" },
        "small_shiny_credit": null,
        "big_regular_credit": null,
        "big_shiny_credit": null
    },
    {
        "pokemon_slug": "venusaur",
        "pokemon_name": "Venusaur",
        "pokemon_french_name": "Florizarre",
        "pokemon_icon": "venusaur",
        "small_regular_credit": null,
        "small_shiny_credit": null,
        "big_regular_credit": null,
        "big_shiny_credit": null
    }
]
```

- [ ] **Step 2: Mirror the same content into the functional expected fixture**

Copy the exact same JSON content from Step 1 into `tests/resources/functional/controller/Credits/all.json` (the controller pass-through means the response is byte-identical to the Moco fixture).

- [ ] **Step 3: Update the unit-test-only fixture**

Replace `tests/resources/unit/service/api/credits.json` with a smaller 2-entry version (this fixture only asserts the service's cache/pass-through behavior in isolation, not exact content matching):

```json
[
    {
        "pokemon_slug": "bulbasaur",
        "pokemon_name": "Bulbasaur",
        "pokemon_french_name": "Bulbizarre",
        "pokemon_icon": "bulbasaur",
        "small_regular_credit": { "credit": "PokéSprite - https://github.com/msikma/pokesprite" },
        "small_shiny_credit": null,
        "big_regular_credit": null,
        "big_shiny_credit": null
    },
    {
        "pokemon_slug": "venusaur",
        "pokemon_name": "Venusaur",
        "pokemon_french_name": "Florizarre",
        "pokemon_icon": "venusaur",
        "small_regular_credit": null,
        "small_shiny_credit": null,
        "big_regular_credit": null,
        "big_shiny_credit": null
    }
]
```

- [ ] **Step 4: Update the unit test assertions**

In `tests/src/Unit/Service/Api/GetCreditsApiServiceTest.php`, replace the body of `testGet()` (the rest of the file — HTTP client mock setup — is unchanged):

```php
    public function testGet(): void
    {
        $credits = $this->getService()->get();

        self::assertCount(2, $credits);
        self::assertSame('bulbasaur', $credits[0]['pokemon_slug']);
        self::assertSame('PokéSprite - https://github.com/msikma/pokesprite', $credits[0]['small_regular_credit']['credit']);
        self::assertNull($credits[1]['small_regular_credit']);

        /** @var string $value */
        $value = $this->cache->getItem('credits_v2')->get();
        self::assertNotEmpty($value);
        self::assertJson($value);
    }
```

- [ ] **Step 5: Update the PHPDoc on the service (no logic change)**

In `src/Service/Api/GetCreditsApiService.php`, update the two `@return`/`@var` docblocks:

```php
    /**
     * @return array<int, array{pokemon_slug: string, pokemon_name: string, pokemon_french_name: string, pokemon_icon: string, small_regular_credit: ?array{credit: string}, small_shiny_credit: ?array{credit: string}, big_regular_credit: ?array{credit: string}, big_shiny_credit: ?array{credit: string}}>
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

        /** @var array<int, array{pokemon_slug: string, pokemon_name: string, pokemon_french_name: string, pokemon_icon: string, small_regular_credit: ?array{credit: string}, small_shiny_credit: ?array{credit: string}, big_regular_credit: ?array{credit: string}, big_shiny_credit: ?array{credit: string}}> */
        return JsonDecoder::decode($json);
    }
```

- [ ] **Step 6: Run the affected tests**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/Api/GetCreditsApiServiceTest.php tests/src/Integration/Credits/CreditsTest.php`
Expected: PASS — `CreditsTest` needs no code change (it only diffs against `Credits/all.json`, updated in Step 2), `GetCreditsApiServiceTest` passes with the Step 4 assertions.

---

## Task 7: `pokenini-back` — full suite and quality gate

**Files:** none (verification-only task).

- [ ] **Step 1: Run the full test suite**

Run: `make tests`
Expected: all green.

- [ ] **Step 2: Run quality and measures gates**

Run: `make quality && make measures`
Expected: all green.

---

## Task 8: `pokenini-web` — new `PokemonCreditRow` ResponseObject, delete `CreditGroup`/`CreditImage`

**Files:**
- Create: `src/ResponseObject/Common/PokemonCreditRow.php`
- Create: `tests/src/Unit/ResponseObject/Common/PokemonCreditRowTest.php`
- Delete: `src/ResponseObject/Common/CreditGroup.php`
- Delete: `src/ResponseObject/Common/CreditImage.php`
- Delete: `tests/src/Unit/ResponseObject/Common/CreditGroupTest.php`
- Delete: `tests/src/Unit/ResponseObject/Common/CreditImageTest.php`

**Interfaces:**
- Consumes: `PokemonCredit` (`src/ResponseObject/Common/PokemonCredit.php`, **untouched** — same name/URL-extraction value object already used by `PokemonData`'s 4 credit getters).
- Produces: `PokemonCreditRow` with getters `getPokemonSlug(): string`, `getPokemonName(): string`, `getPokemonFrenchName(): string`, `getPokemonIcon(): string`, `getSmallRegularCredit(): ?PokemonCredit`, `getSmallShinyCredit(): ?PokemonCredit`, `getBigRegularCredit(): ?PokemonCredit`, `getBigShinyCredit(): ?PokemonCredit`, plus two convenience methods the template needs: `hasAnyCredit(): bool` and `getCreditCount(): int`. This is what Task 9's services deserialize into and Task 11's template renders.

`PokemonCreditRow`'s constructor mirrors the existing `PokemonData::__construct()` pattern exactly (`src/ResponseObject/Common/PokemonData.php:14-43`) for the 4 credit properties — same `#[SerializedName]` values, same `?PokemonCredit` type — it just drops the dex/game-bundle-specific fields that `PokemonData` carries and this page's API response doesn't send.

- [ ] **Step 1: Delete the obsolete `CreditGroup`/`CreditImage` and their tests**

Delete these 4 files (superseded — nothing else references them once Task 9 rewires both services):
- `src/ResponseObject/Common/CreditGroup.php`
- `src/ResponseObject/Common/CreditImage.php`
- `tests/src/Unit/ResponseObject/Common/CreditGroupTest.php`
- `tests/src/Unit/ResponseObject/Common/CreditImageTest.php`

- [ ] **Step 2: Write the `PokemonCreditRow` test**

Create `tests/src/Unit/ResponseObject/Common/PokemonCreditRowTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Common;

use App\ResponseObject\Common\PokemonCredit;
use App\ResponseObject\Common\PokemonCreditRow;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(PokemonCreditRow::class)]
final class PokemonCreditRowTest extends TestCase
{
    public function testGettersExposeConstructorValues(): void
    {
        $smallRegular = new PokemonCredit(credit: 'PokéSprite - https://github.com/msikma/pokesprite');
        $bigRegular = new PokemonCredit(credit: 'PokemonDB - https://pokemondb.net/sprites/bulbasaur');

        $row = new PokemonCreditRow(
            pokemonSlug: 'bulbasaur',
            pokemonName: 'Bulbasaur',
            pokemonFrenchName: 'Bulbizarre',
            pokemonIcon: 'bulbasaur',
            smallRegularCredit: $smallRegular,
            smallShinyCredit: null,
            bigRegularCredit: $bigRegular,
            bigShinyCredit: null,
        );

        $this->assertSame('bulbasaur', $row->getPokemonSlug());
        $this->assertSame('Bulbasaur', $row->getPokemonName());
        $this->assertSame('Bulbizarre', $row->getPokemonFrenchName());
        $this->assertSame('bulbasaur', $row->getPokemonIcon());
        $this->assertSame($smallRegular, $row->getSmallRegularCredit());
        $this->assertNull($row->getSmallShinyCredit());
        $this->assertSame($bigRegular, $row->getBigRegularCredit());
        $this->assertNull($row->getBigShinyCredit());
    }

    public function testHasAnyCreditIsTrueWhenAtLeastOneSlotIsSet(): void
    {
        $row = new PokemonCreditRow(
            pokemonSlug: 'ivysaur',
            pokemonName: 'Ivysaur',
            pokemonFrenchName: 'Herbizarre',
            pokemonIcon: 'ivysaur',
            smallRegularCredit: new PokemonCredit(credit: 'Serebii - https://serebii.net'),
            smallShinyCredit: null,
            bigRegularCredit: null,
            bigShinyCredit: null,
        );

        $this->assertTrue($row->hasAnyCredit());
        $this->assertSame(1, $row->getCreditCount());
    }

    public function testHasAnyCreditIsFalseWhenAllFourSlotsAreNull(): void
    {
        $row = new PokemonCreditRow(
            pokemonSlug: 'venusaur',
            pokemonName: 'Venusaur',
            pokemonFrenchName: 'Florizarre',
            pokemonIcon: 'venusaur',
            smallRegularCredit: null,
            smallShinyCredit: null,
            bigRegularCredit: null,
            bigShinyCredit: null,
        );

        $this->assertFalse($row->hasAnyCredit());
        $this->assertSame(0, $row->getCreditCount());
    }

    public function testGetCreditCountCountsAllFourSlotsWhenFullyCredited(): void
    {
        $credit = new PokemonCredit(credit: 'PokéSprite - https://github.com/msikma/pokesprite');

        $row = new PokemonCreditRow(
            pokemonSlug: 'bulbasaur',
            pokemonName: 'Bulbasaur',
            pokemonFrenchName: 'Bulbizarre',
            pokemonIcon: 'bulbasaur',
            smallRegularCredit: $credit,
            smallShinyCredit: $credit,
            bigRegularCredit: $credit,
            bigShinyCredit: $credit,
        );

        $this->assertSame(4, $row->getCreditCount());
    }
}
```

- [ ] **Step 3: Run the test to verify it fails**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/ResponseObject/Common/PokemonCreditRowTest.php`
Expected: FAIL — class `App\ResponseObject\Common\PokemonCreditRow` not found.

- [ ] **Step 4: Create `PokemonCreditRow`**

Create `src/ResponseObject/Common/PokemonCreditRow.php`:

```php
<?php

declare(strict_types=1);

namespace App\ResponseObject\Common;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class PokemonCreditRow
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
        #[SerializedName('small_regular_credit')]
        private readonly ?PokemonCredit $smallRegularCredit,
        #[SerializedName('small_shiny_credit')]
        private readonly ?PokemonCredit $smallShinyCredit,
        #[SerializedName('big_regular_credit')]
        private readonly ?PokemonCredit $bigRegularCredit,
        #[SerializedName('big_shiny_credit')]
        private readonly ?PokemonCredit $bigShinyCredit,
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

    public function getSmallRegularCredit(): ?PokemonCredit
    {
        return $this->smallRegularCredit;
    }

    public function getSmallShinyCredit(): ?PokemonCredit
    {
        return $this->smallShinyCredit;
    }

    public function getBigRegularCredit(): ?PokemonCredit
    {
        return $this->bigRegularCredit;
    }

    public function getBigShinyCredit(): ?PokemonCredit
    {
        return $this->bigShinyCredit;
    }

    public function hasAnyCredit(): bool
    {
        return null !== $this->smallRegularCredit
            || null !== $this->smallShinyCredit
            || null !== $this->bigRegularCredit
            || null !== $this->bigShinyCredit;
    }

    public function getCreditCount(): int
    {
        return count(array_filter([
            $this->smallRegularCredit,
            $this->smallShinyCredit,
            $this->bigRegularCredit,
            $this->bigShinyCredit,
        ]));
    }
}
```

- [ ] **Step 5: Run the test to verify it passes**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/ResponseObject/Common/PokemonCreditRowTest.php`
Expected: PASS (4 tests).

---

## Task 9: `pokenini-web` — thread `PokemonCreditRow` through both credit services

**Files:**
- Modify: `src/Service/Back/GetCreditsService.php`
- Modify: `src/Service/GetCreditsService.php`
- Modify: `tests/src/Unit/Service/Back/GetCreditsServiceTest.php`
- Modify: `tests/src/Unit/Service/GetCreditsServiceTest.php`
- Modify: `tests/resources/unit/service/back/credits.json`
- Modify: `tests/resources/moco/Back/responses/credits.json`

**Interfaces:**
- Consumes: `PokemonCreditRow` (Task 8).
- Produces: `Service\Back\GetCreditsService::get(): PokemonCreditRow[]` and `Service\GetCreditsService::get(): PokemonCreditRow[]`. The caching layer's cache key/tag moves from `'credits_v2'` to `'credits_v3'` — the cached payload's PHP class is changing, and bumping the key avoids a Redis-cached `CreditGroup` instance (now deleted) failing to deserialize after deploy.

- [ ] **Step 1: Update the back-service unit fixture**

Replace `tests/resources/unit/service/back/credits.json` (same 3-Pokémon content as Task 6's Moco fixture, since this JSON is what `Service\Back\GetCreditsService` deserializes):

```json
[
    {
        "pokemon_slug": "bulbasaur",
        "pokemon_name": "Bulbasaur",
        "pokemon_french_name": "Bulbizarre",
        "pokemon_icon": "bulbasaur",
        "small_regular_credit": { "credit": "PokéSprite - https://github.com/msikma/pokesprite" },
        "small_shiny_credit": { "credit": "PokéSprite - https://github.com/msikma/pokesprite" },
        "big_regular_credit": { "credit": "PokemonDB - https://pokemondb.net/sprites/bulbasaur" },
        "big_shiny_credit": { "credit": "Bulbapedia - https://bulbapedia.bulbagarden.net" }
    },
    {
        "pokemon_slug": "ivysaur",
        "pokemon_name": "Ivysaur",
        "pokemon_french_name": "Herbizarre",
        "pokemon_icon": "ivysaur",
        "small_regular_credit": { "credit": "Serebii - https://serebii.net" },
        "small_shiny_credit": null,
        "big_regular_credit": null,
        "big_shiny_credit": null
    },
    {
        "pokemon_slug": "venusaur",
        "pokemon_name": "Venusaur",
        "pokemon_french_name": "Florizarre",
        "pokemon_icon": "venusaur",
        "small_regular_credit": null,
        "small_shiny_credit": null,
        "big_regular_credit": null,
        "big_shiny_credit": null
    }
]
```

- [ ] **Step 2: Update `Service\Back\GetCreditsService`'s test**

Replace `tests/src/Unit/Service/Back/GetCreditsServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\ResponseObject\Common\PokemonCreditRow;
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

        $rows = [
            new PokemonCreditRow(
                pokemonSlug: 'bulbasaur',
                pokemonName: 'Bulbasaur',
                pokemonFrenchName: 'Bulbizarre',
                pokemonIcon: 'bulbasaur',
                smallRegularCredit: null,
                smallShinyCredit: null,
                bigRegularCredit: null,
                bigShinyCredit: null,
            ),
            new PokemonCreditRow(
                pokemonSlug: 'venusaur',
                pokemonName: 'Venusaur',
                pokemonFrenchName: 'Florizarre',
                pokemonIcon: 'venusaur',
                smallRegularCredit: null,
                smallShinyCredit: null,
                bigRegularCredit: null,
                bigShinyCredit: null,
            ),
        ];

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer
            ->expects($this->once())
            ->method('deserialize')
            ->with(
                $json,
                PokemonCreditRow::class.'[]',
                'json',
            )
            ->willReturn($rows)
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
        $this->assertSame('bulbasaur', $object[0]->getPokemonSlug());
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

use App\ResponseObject\Common\PokemonCreditRow;
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
        $rows = [new PokemonCreditRow(
            pokemonSlug: 'bulbasaur',
            pokemonName: 'Bulbasaur',
            pokemonFrenchName: 'Bulbizarre',
            pokemonIcon: 'bulbasaur',
            smallRegularCredit: null,
            smallShinyCredit: null,
            bigRegularCredit: null,
            bigShinyCredit: null,
        )];

        $backService = $this->createMock(BackGetCreditsService::class);
        $backService
            ->expects($this->once())
            ->method('get')
            ->willReturn($rows)
        ;

        $service = new GetCreditsService($backService, new TagAwareAdapter(new ArrayAdapter()));

        $this->assertSame($rows, $service->get());
    }

    public function testCacheIsInvalidatedByCreditsTag(): void
    {
        $rows = [new PokemonCreditRow(
            pokemonSlug: 'bulbasaur',
            pokemonName: 'Bulbasaur',
            pokemonFrenchName: 'Bulbizarre',
            pokemonIcon: 'bulbasaur',
            smallRegularCredit: null,
            smallShinyCredit: null,
            bigRegularCredit: null,
            bigShinyCredit: null,
        )];

        $backService = $this->createMock(BackGetCreditsService::class);
        $backService
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturn($rows)
        ;

        $cache = new TagAwareAdapter(new ArrayAdapter());
        $service = new GetCreditsService($backService, $cache);

        $service->get();
        $cache->invalidateTags(['credits_v3']);
        $service->get();
    }
}
```

- [ ] **Step 4: Run the tests to verify they fail**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/Back/GetCreditsServiceTest.php tests/src/Unit/Service/GetCreditsServiceTest.php`
Expected: FAIL — `CreditGroup`/`CreditImage` no longer exist (from Task 8) and `Service\Back\GetCreditsService`/`Service\GetCreditsService` still deserialize into/reference them.

- [ ] **Step 5: Update `Service\Back\GetCreditsService`**

Replace `src/Service/Back/GetCreditsService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\ResponseObject\Common\PokemonCreditRow;

class GetCreditsService extends AbstractBackService
{
    /**
     * @return PokemonCreditRow[]
     */
    public function get(): array
    {
        $json = $this->requestContent(
            'GET',
            '/credits'
        );

        /** @var PokemonCreditRow[] */
        return $this->serializer->deserialize($json, PokemonCreditRow::class.'[]', 'json');
    }
}
```

- [ ] **Step 6: Update `Service\GetCreditsService`**

Replace `src/Service/GetCreditsService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Service;

use App\ResponseObject\Common\PokemonCreditRow;
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
     * @return PokemonCreditRow[]
     */
    public function get(): array
    {
        return $this->creditsCache->get('credits_v3', function (ItemInterface $item): array {
            $item->tag(['credits_v3']);

            return $this->getService->get();
        });
    }
}
```

- [ ] **Step 7: Update the Moco fixture used by the integration test**

Replace `tests/resources/moco/Back/responses/credits.json` with the exact same 3-Pokémon content as Step 1 (`tests/resources/unit/service/back/credits.json`) — Task 12 derives the integration test's assertions from this fixture, so keep them byte-identical.

- [ ] **Step 8: Run the tests to verify they pass**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/Back/GetCreditsServiceTest.php tests/src/Unit/Service/GetCreditsServiceTest.php`
Expected: PASS (2 tests each file).

---

## Task 10: `pokenini-web` — add the "no credit" translation key

**Files:**
- Modify: `translations/messages+intl-icu.fr.yaml`
- Modify: `translations/messages+intl-icu.en.yaml`

**Interfaces:**
- Consumes: nothing.
- Produces: translation key `credit.detail.none`, used by Task 11's template.

- [ ] **Step 1: Add the French key**

In `translations/messages+intl-icu.fr.yaml`, the existing `credit:` block (currently at line 661) is:

```yaml
credit:
  tooltip: "Crédit image"
  detail:
    count: "{count, plural, one {# image} other {# images}}"
    size:
      small: "petit sprite"
      big: "grand sprite"
```

Add a `none` key under `detail`:

```yaml
credit:
  tooltip: "Crédit image"
  detail:
    count: "{count, plural, one {# image} other {# images}}"
    none: "Aucun crédit"
    size:
      small: "petit sprite"
      big: "grand sprite"
```

- [ ] **Step 2: Add the English key**

In `translations/messages+intl-icu.en.yaml`, the existing `credit:` block (currently at line 652) is:

```yaml
credit:
  tooltip: "Image credit"
  detail:
    count: "{count, plural, one {# image} other {# images}}"
    size:
      small: "small sprite"
      big: "big sprite"
```

Add a `none` key under `detail`:

```yaml
credit:
  tooltip: "Image credit"
  detail:
    count: "{count, plural, one {# image} other {# images}}"
    none: "No credit"
    size:
      small: "small sprite"
      big: "big sprite"
```

- [ ] **Step 3: Verify the translation files are still valid YAML**

Run: `docker compose exec php php tools/jsonlint/vendor/bin/jsonlint --help >/dev/null 2>&1; docker compose exec php php bin/console lint:yaml translations/`
Expected: no syntax errors reported for either file.

---

## Task 11: `pokenini-web` — rewrite the Credits template

**Files:**
- Modify: `templates/Credits/index.html.twig`
- Modify: `public/css/base.css`

**Interfaces:**
- Consumes: `credits` (a `PokemonCreditRow[]`, from `CreditsController` — unchanged, still passes `'credits' => $service->get()`), `imageMacros.creditBadge(credit)` from `common/Pokemon/_image_macros.html.twig` (unmodified, reused as-is), the `pokemonIconUrl` Twig global (`config/packages/twig.yaml:5`), translation keys `credit.detail.count`, `credit.detail.none`, `credit.detail.size.small`, `credit.detail.size.big`, `album.icon.title.regular`, `album.icon.title.shiny` (all pre-existing except `credit.detail.none`, added in Task 10).
- Produces: the rendered `/{_locale}/credits` page — verified by Task 12's integration test.

- [ ] **Step 1: Replace the template**

Replace `templates/Credits/index.html.twig`:

```twig
{% set locale = app.request.locale %}

{% extends 'base.html.twig' %}
{% use '_nav.html.twig' %}
{% import 'common/Pokemon/_image_macros.html.twig' as imageMacros %}

{% block title %}Pokénini {{ 'title.credits'|trans }}{% endblock title %}

{% block container %}
  <div class="row justify-content-center">
    <div class="col-6">
      <h1>{{ 'title.credits'|trans }}</h1>

      <ul class="list-group">
        {% for row in credits %}
          {% set pokemonName = locale is same as('fr') ? row.pokemonFrenchName : row.pokemonName %}

          <li class="list-group-item">
            <img
              class="pokemon-icon img-fluid me-2"
              src="{{ pokemonIconUrl|format('regular', row.pokemonIcon) }}"
              alt=""
              loading="lazy"
              onerror="this.onerror=null;this.src='/img/pokemon/default_icon.webp';"
            >
            <span class="credit-pokemon-name">{{ pokemonName }}</span>

            {% if row.hasAnyCredit %}
              <button
                class="btn btn-link p-0 credit-detail-toggle"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#credit-detail-{{ loop.index0 }}"
                aria-expanded="false"
                aria-controls="credit-detail-{{ loop.index0 }}"
              >
                {{ 'credit.detail.count'|trans({count: row.creditCount}) }}
              </button>

              <ul class="collapse credit-detail-list list-unstyled" id="credit-detail-{{ loop.index0 }}">
                {% if row.smallRegularCredit is not null %}
                  <li>
                    {{ 'credit.detail.size.small'|trans }}, {{ 'album.icon.title.regular'|trans }}
                    {{ imageMacros.creditBadge(row.smallRegularCredit) }}
                  </li>
                {% endif %}
                {% if row.smallShinyCredit is not null %}
                  <li>
                    {{ 'credit.detail.size.small'|trans }}, {{ 'album.icon.title.shiny'|trans }}
                    {{ imageMacros.creditBadge(row.smallShinyCredit) }}
                  </li>
                {% endif %}
                {% if row.bigRegularCredit is not null %}
                  <li>
                    {{ 'credit.detail.size.big'|trans }}, {{ 'album.icon.title.regular'|trans }}
                    {{ imageMacros.creditBadge(row.bigRegularCredit) }}
                  </li>
                {% endif %}
                {% if row.bigShinyCredit is not null %}
                  <li>
                    {{ 'credit.detail.size.big'|trans }}, {{ 'album.icon.title.shiny'|trans }}
                    {{ imageMacros.creditBadge(row.bigShinyCredit) }}
                  </li>
                {% endif %}
              </ul>
            {% else %}
              <span class="text-muted">{{ 'credit.detail.none'|trans }}</span>
            {% endif %}
          </li>
        {% endfor %}
      </ul>
    </div>
  </div>
{% endblock container %}
```

- [ ] **Step 2: Remove the now-unused thumbnail-tooltip CSS rule**

In `public/css/base.css`, the current block (lines 86-96) is:

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

`.credit-detail-toggle` and `.credit-detail-list` are still used by the new template; `.credit-detail-thumbnail` is dead (the new template shows a real sprite icon via the shared `.pokemon-icon` class instead of a tooltip-embedded thumbnail image). Remove only the `.credit-detail-thumbnail` rule:

```css
.credit-detail-toggle {
    font-size: .875rem;
}
.credit-detail-list {
    margin-top: .5rem;
    margin-bottom: 0;
}
```

- [ ] **Step 3: Verify the Twig template compiles**

Run: `docker compose exec php php bin/console lint:twig templates/Credits/index.html.twig`
Expected: no syntax errors reported. (Full rendering is verified by Task 12's integration test, run next.)

---

## Task 12: `pokenini-web` — update the Credits integration test

**Files:**
- Modify: `tests/src/Integration/Controller/Credits/CreditsTest.php`

**Interfaces:**
- Consumes: the Moco fixture from Task 9 Step 7 (`tests/resources/moco/Back/responses/credits.json` — bulbasaur 4/4 credited, ivysaur 1/4, venusaur 0/4).
- Produces: nothing new — this is the acceptance test proving Task 11's template renders correctly end-to-end.

- [ ] **Step 1: Replace the integration test**

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

        // Order and content come from tests/resources/moco/Back/responses/credits.json:
        // bulbasaur (4/4 slots credited), ivysaur (1/4), venusaur (0/4).
        $items = $crawler->filter('.list-group-item');
        $this->assertCount(3, $items);

        $bulbasaur = $items->eq(0);
        $this->assertStringContainsString('Bulbizarre', $bulbasaur->text());
        $this->assertStringContainsString('4', $bulbasaur->filter('.credit-detail-toggle')->text());
        $this->assertCount(4, $bulbasaur->filter('.credit-detail-list li'));

        $ivysaur = $items->eq(1);
        $this->assertStringContainsString('Herbizarre', $ivysaur->text());
        $this->assertStringContainsString('1', $ivysaur->filter('.credit-detail-toggle')->text());
        $this->assertCount(1, $ivysaur->filter('.credit-detail-list li'));

        $venusaur = $items->eq(2);
        $this->assertStringContainsString('Florizarre', $venusaur->text());
        $this->assertCount(0, $venusaur->filter('.credit-detail-toggle'));
        $this->assertStringContainsString('Aucun crédit', $venusaur->text());
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Credits/CreditsTest.php`
Expected: FAIL — the template from before Task 11 renders the old by-source shape (4 `.list-group-item` sources, no `.credit-detail-toggle` per Pokémon).

- [ ] **Step 3: Run the test to verify it passes**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Credits/CreditsTest.php`
Expected: PASS. (This step comes after Tasks 8-11 are in place — if run immediately after Step 2 with no other changes made yet, it will still fail; only mark this step done once the rest of the plan's `pokenini-web` tasks are complete.)

---

## Task 13: `pokenini-web` — full suite and quality gate

**Files:** none (verification-only task).

- [ ] **Step 1: Run the full test suite**

Run: `make tests`
Expected: all green, including `tests/src/Browser/Album/CreditBadgeTooltipTest.php` (unaffected — `creditBadge()` macro itself didn't change) and `tests/src/Unit/ResponseObject/Common/PokemonCreditTest.php` (unaffected — `PokemonCredit` itself didn't change).

- [ ] **Step 2: Run quality and measures gates**

Run: `make quality && make measures`
Expected: all green (PHPStan level 9, Psalm strict, Deptrac, PHP CS Fixer, 100% coverage, 100% MSI).
