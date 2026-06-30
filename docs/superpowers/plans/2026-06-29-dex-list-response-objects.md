# Dex List Response Objects Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace `string[][]` return type of `GetAlbumDexListService::get()` with typed `DexListItem[]` ResponseObjects, matching the actual nested JSON format returned by the `/album/dex` endpoint.

**Architecture:** Three new final classes under `ResponseObject/Album/` mirror the nested JSON structure (`dex`, `settings`, `flags`). The service switches from `JsonDecoder::decode()` to `$this->serializer->deserialize()` (already available via `AbstractBackService`). The Twig macro and all fixtures (unit + Moco) are updated to match.

**Tech Stack:** Symfony 8 / PHP ≥ 8.5, Symfony Serializer with `#[SerializedName]`, PHPUnit, Moco HTTP fixtures, Twig

## Global Constraints

- Every PHP file must have `declare(strict_types=1)` at the top
- ResponseObject classes are `final`
- Test classes are `final`, carry `/** @internal */` and `#[CoversClass(...)]`
- Run all commands inside the Docker container: `docker compose exec php ...`
- No PHPStan/Psalm baselines need updating (new classes are typed correctly)
- Do NOT commit anything (user manages git)

---

### Task 1: `DexListItemRef` — sub-object for the `dex` key

**Files:**
- Create: `src/ResponseObject/Album/DexListItemRef.php`
- Create: `tests/src/Unit/ResponseObject/Album/DexListItemRefTest.php`

**Interfaces:**
- Produces: `DexListItemRef(slug: string)` with `getSlug(): string` — consumed by Task 3

- [ ] **Step 1: Write the failing test**

Create `tests/src/Unit/ResponseObject/Album/DexListItemRefTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Album;

use App\ResponseObject\Album\DexListItemRef;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(DexListItemRef::class)]
final class DexListItemRefTest extends TestCase
{
    public function testGetters(): void
    {
        $ref = new DexListItemRef(slug: 'homepokemongo');

        $this->assertSame('homepokemongo', $ref->getSlug());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Unit/ResponseObject/Album/DexListItemRefTest.php
```

Expected: FAIL — class `App\ResponseObject\Album\DexListItemRef` not found.

- [ ] **Step 3: Implement `DexListItemRef`**

Create `src/ResponseObject/Album/DexListItemRef.php`:

```php
<?php

declare(strict_types=1);

namespace App\ResponseObject\Album;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class DexListItemRef
{
    public function __construct(
        #[SerializedName('slug')]
        private readonly string $slug,
    ) {}

    public function getSlug(): string
    {
        return $this->slug;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Unit/ResponseObject/Album/DexListItemRefTest.php
```

Expected: OK (1 test, 1 assertion).

---

### Task 2: `DexListItemSettings` — sub-object for the `settings` key

**Files:**
- Create: `src/ResponseObject/Album/DexListItemSettings.php`
- Create: `tests/src/Unit/ResponseObject/Album/DexListItemSettingsTest.php`

**Interfaces:**
- Produces: `DexListItemSettings(name: string, frenchName: string, slug: string, displayTemplate: string)` with corresponding getters — consumed by Task 3

- [ ] **Step 1: Write the failing test**

Create `tests/src/Unit/ResponseObject/Album/DexListItemSettingsTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Album;

use App\ResponseObject\Album\DexListItemSettings;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(DexListItemSettings::class)]
final class DexListItemSettingsTest extends TestCase
{
    public function testGetters(): void
    {
        $settings = new DexListItemSettings(
            name: 'Sword, Shield',
            frenchName: 'Épée, Bouclier',
            slug: 'swordshield',
            displayTemplate: 'box',
        );

        $this->assertSame('Sword, Shield', $settings->getName());
        $this->assertSame('Épée, Bouclier', $settings->getFrenchName());
        $this->assertSame('swordshield', $settings->getSlug());
        $this->assertSame('box', $settings->getDisplayTemplate());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Unit/ResponseObject/Album/DexListItemSettingsTest.php
```

Expected: FAIL — class not found.

- [ ] **Step 3: Implement `DexListItemSettings`**

Create `src/ResponseObject/Album/DexListItemSettings.php`:

```php
<?php

declare(strict_types=1);

namespace App\ResponseObject\Album;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class DexListItemSettings
{
    public function __construct(
        #[SerializedName('name')]
        private readonly string $name,
        #[SerializedName('french_name')]
        private readonly string $frenchName,
        #[SerializedName('slug')]
        private readonly string $slug,
        #[SerializedName('display_template')]
        private readonly string $displayTemplate,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getFrenchName(): string
    {
        return $this->frenchName;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getDisplayTemplate(): string
    {
        return $this->displayTemplate;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Unit/ResponseObject/Album/DexListItemSettingsTest.php
```

Expected: OK (1 test, 4 assertions).

---

### Task 3: `DexListItem` — top-level item

**Files:**
- Create: `src/ResponseObject/Album/DexListItem.php`
- Create: `tests/src/Unit/ResponseObject/Album/DexListItemTest.php`

**Interfaces:**
- Consumes: `DexListItemRef` (Task 1), `DexListItemSettings` (Task 2), `DexFlags` (existing at `src/ResponseObject/Album/DexFlags.php`)
- Produces: `DexListItem(dex: DexListItemRef, settings: DexListItemSettings, flags: DexFlags)` with `getDex()`, `getSettings()`, `getFlags()` — consumed by Tasks 4 and 5

- [ ] **Step 1: Write the failing test**

Create `tests/src/Unit/ResponseObject/Album/DexListItemTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Album;

use App\ResponseObject\Album\DexFlags;
use App\ResponseObject\Album\DexListItem;
use App\ResponseObject\Album\DexListItemRef;
use App\ResponseObject\Album\DexListItemSettings;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(DexListItem::class)]
final class DexListItemTest extends TestCase
{
    public function testGetters(): void
    {
        $ref = new DexListItemRef(slug: 'swordshield');
        $settings = new DexListItemSettings(
            name: 'Sword, Shield',
            frenchName: 'Épée, Bouclier',
            slug: 'swordshield',
            displayTemplate: 'box',
        );
        $flags = new DexFlags(
            isShiny: false,
            isPrivate: false,
            isOnHome: true,
            isDisplayForm: true,
            isReleased: true,
            isPremium: false,
            isCustom: false,
        );

        $item = new DexListItem(dex: $ref, settings: $settings, flags: $flags);

        $this->assertSame($ref, $item->getDex());
        $this->assertSame($settings, $item->getSettings());
        $this->assertSame($flags, $item->getFlags());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Unit/ResponseObject/Album/DexListItemTest.php
```

Expected: FAIL — class not found.

- [ ] **Step 3: Implement `DexListItem`**

Create `src/ResponseObject/Album/DexListItem.php`:

```php
<?php

declare(strict_types=1);

namespace App\ResponseObject\Album;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class DexListItem
{
    public function __construct(
        #[SerializedName('dex')]
        private readonly DexListItemRef $dex,
        #[SerializedName('settings')]
        private readonly DexListItemSettings $settings,
        #[SerializedName('flags')]
        private readonly DexFlags $flags,
    ) {}

    public function getDex(): DexListItemRef
    {
        return $this->dex;
    }

    public function getSettings(): DexListItemSettings
    {
        return $this->settings;
    }

    public function getFlags(): DexFlags
    {
        return $this->flags;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Unit/ResponseObject/Album/DexListItemTest.php
```

Expected: OK (1 test, 3 assertions).

---

### Task 4: Update service + unit service test + unit fixtures

**Files:**
- Modify: `src/Service/Back/GetAlbumDexListService.php`
- Modify: `tests/src/Unit/Service/Back/GetAlbumDexListServiceTest.php`
- Modify: `tests/resources/unit/service/back/dex.json`
- Modify: `tests/resources/unit/service/back/dex_123.json`

**Interfaces:**
- Consumes: `DexListItem` (Task 3) with `getDex(): DexListItemRef`, `getSettings(): DexListItemSettings`
- Produces: `GetAlbumDexListService::get(?string $trainerId = null): DexListItem[]` — consumed by Task 5

- [ ] **Step 1: Update unit fixtures to new format**

Replace `tests/resources/unit/service/back/dex.json` entirely:

```json
[
  {
    "dex": { "slug": "homepokemongo" },
    "settings": {
      "name": "Home Pokemon Go",
      "french_name": "Home Pokemon Go",
      "slug": "homepokemongo",
      "display_template": "list-7"
    },
    "flags": {
      "is_shiny": false,
      "is_private": false,
      "is_on_home": false,
      "is_display_form": false,
      "is_released": true,
      "is_premium": false,
      "is_custom": false
    }
  },
  {
    "dex": { "slug": "alpha" },
    "settings": {
      "name": "Alpha",
      "french_name": "Baron",
      "slug": "alpha",
      "display_template": "list-3"
    },
    "flags": {
      "is_shiny": false,
      "is_private": true,
      "is_on_home": false,
      "is_display_form": true,
      "is_released": true,
      "is_premium": false,
      "is_custom": false
    }
  },
  {
    "dex": { "slug": "mega" },
    "settings": {
      "name": "Mega",
      "french_name": "Méga",
      "slug": "mega",
      "display_template": "list-3"
    },
    "flags": {
      "is_shiny": false,
      "is_private": true,
      "is_on_home": false,
      "is_display_form": true,
      "is_released": true,
      "is_premium": false,
      "is_custom": false
    }
  }
]
```

Replace `tests/resources/unit/service/back/dex_123.json` entirely:

```json
[
  {
    "dex": { "slug": "homepokemongo" },
    "settings": {
      "name": "Home Pokemon Go",
      "french_name": "Home Pokemon Go",
      "slug": "homepokemongo",
      "display_template": "list-7"
    },
    "flags": {
      "is_shiny": false,
      "is_private": false,
      "is_on_home": false,
      "is_display_form": false,
      "is_released": true,
      "is_premium": false,
      "is_custom": false
    }
  },
  {
    "dex": { "slug": "alpha" },
    "settings": {
      "name": "Alpha",
      "french_name": "Baron",
      "slug": "alpha",
      "display_template": "list-3"
    },
    "flags": {
      "is_shiny": false,
      "is_private": true,
      "is_on_home": false,
      "is_display_form": true,
      "is_released": true,
      "is_premium": false,
      "is_custom": false
    }
  }
]
```

- [ ] **Step 2: Rewrite the service test**

Replace `tests/src/Unit/Service/Back/GetAlbumDexListServiceTest.php` entirely:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Back;

use App\ResponseObject\Album\DexFlags;
use App\ResponseObject\Album\DexListItem;
use App\ResponseObject\Album\DexListItemRef;
use App\ResponseObject\Album\DexListItemSettings;
use App\Security\UserTokenServiceInterface;
use App\Service\Back\AbstractBackService;
use App\Service\Back\GetAlbumDexListService;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @internal
 */
#[CoversClass(GetAlbumDexListService::class)]
final class GetAlbumDexListServiceTest extends AbstractTestBackService
{
    public function testGet(): void
    {
        $json = '{"doesnt": "matter"}';

        $items = $this->makeItems(['homepokemongo', 'alpha', 'mega']);

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer
            ->expects($this->once())
            ->method('deserialize')
            ->with($json, DexListItem::class.'[]', 'json')
            ->willReturn($items)
        ;

        /** @var GetAlbumDexListService $service */
        $service = $this->getServiceWithLoggedUser('GET', $json, 'album/dex', [], $serializer);

        $this->assertSame(
            ['homepokemongo', 'alpha', 'mega'],
            self::extractSlugs($service->get()),
        );
    }

    public function testGetWithEmptyTrainerId(): void
    {
        $json = '{"doesnt": "matter"}';

        $items = $this->makeItems(['homepokemongo', 'alpha', 'mega']);

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer
            ->expects($this->once())
            ->method('deserialize')
            ->with($json, DexListItem::class.'[]', 'json')
            ->willReturn($items)
        ;

        /** @var GetAlbumDexListService $service */
        $service = $this->getServiceWithoutLoggedUser('GET', $json, 'album/dex', [], $serializer);

        $this->assertSame(
            ['homepokemongo', 'alpha', 'mega'],
            self::extractSlugs($service->get('')),
        );
    }

    public function testGetWithTrainerId(): void
    {
        $json = '{"doesnt": "matter"}';

        $items = $this->makeItems(['homepokemongo', 'alpha']);

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer
            ->expects($this->once())
            ->method('deserialize')
            ->with($json, DexListItem::class.'[]', 'json')
            ->willReturn($items)
        ;

        /** @var GetAlbumDexListService $service */
        $service = $this->getServiceWithoutLoggedUser(
            'GET',
            $json,
            'album/dex',
            ['query' => ['trainer_id' => '123']],
            $serializer,
        );

        $this->assertSame(
            ['homepokemongo', 'alpha'],
            self::extractSlugs($service->get('123')),
        );
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
        return new GetAlbumDexListService(
            $logger,
            $client,
            $url,
            $cafilePath,
            $userTokenService,
            $serializer,
        );
    }

    /**
     * @param string[] $slugs
     *
     * @return DexListItem[]
     */
    private function makeItems(array $slugs): array
    {
        return array_map(fn(string $slug) => new DexListItem(
            dex: new DexListItemRef(slug: $slug),
            settings: new DexListItemSettings(
                name: $slug,
                frenchName: $slug,
                slug: $slug,
                displayTemplate: 'box',
            ),
            flags: new DexFlags(
                isShiny: false,
                isPrivate: false,
                isOnHome: false,
                isDisplayForm: false,
                isReleased: true,
                isPremium: false,
                isCustom: false,
            ),
        ), $slugs);
    }

    /**
     * @param DexListItem[] $items
     *
     * @return string[]
     */
    private static function extractSlugs(array $items): array
    {
        return array_map(fn(DexListItem $item) => $item->getSettings()->getSlug(), $items);
    }
}
```

- [ ] **Step 3: Run test to verify it fails (service still uses JsonDecoder)**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/Back/GetAlbumDexListServiceTest.php
```

Expected: FAIL — the service currently calls `JsonDecoder::decode()` and returns `string[][]`, but the test mocks the Serializer.

- [ ] **Step 4: Update the service**

Replace `src/Service/Back/GetAlbumDexListService.php` entirely:

```php
<?php

declare(strict_types=1);

namespace App\Service\Back;

use App\ResponseObject\Album\DexListItem;

class GetAlbumDexListService extends AbstractBackService
{
    /**
     * @return DexListItem[]
     */
    public function get(?string $trainerId = null): array
    {
        $options = (null !== $trainerId && '' !== $trainerId) ? ['query' => ['trainer_id' => $trainerId]] : [];

        $json = $this->requestContent(
            'GET',
            '/album/dex',
            $options,
        );

        /** @var DexListItem[] */
        return $this->serializer->deserialize($json, DexListItem::class.'[]', 'json');
    }
}
```

- [ ] **Step 5: Run unit service test to verify it passes**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/Back/GetAlbumDexListServiceTest.php
```

Expected: OK (3 tests, 6 assertions).

- [ ] **Step 6: Run all unit tests to confirm no regressions**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Unit/
```

Expected: all green.

---

### Task 5: Update Moco fixtures + Twig template + remove dump()

The Moco fixtures feed the integration tests via the mock HTTP server. They must be updated to the new JSON format. The Twig macro and `HomeController` are also updated here.

**Transformation rule** for each entry:
```
OLD: { "slug": "X", "original_slug": "Y", "name": "N", "french_name": "FN", "flags": {...}, "display_template": "T", "region"?: {...} }
NEW: { "dex": { "slug": "X" }, "settings": { "name": "N", "french_name": "FN", "slug": "X", "display_template": "T" }, "flags": {...} }
```
`original_slug` and `region` are dropped. `slug` goes into both `dex.slug` and `settings.slug`.

**Files:**
- Modify: `tests/resources/moco/Back/responses/dex/admin.json`
- Modify: `tests/resources/moco/Back/responses/dex/trainer.json`
- Modify: `tests/resources/moco/Back/responses/dex/356a192b7913b04c54574d18c28d46e6395428ab.json`
- Modify: `tests/resources/moco/Back/responses/dex/da4b9237bacccdf19c0760cab7aec4a8359010b0.json`
- Modify: `tests/resources/moco/Back/responses/dex/b6589fc6ab0dc82cf12099d1c2d40ab994e8410c.json`
- Modify: `tests/resources/moco/Back/responses/dex/159bb9b6d090a313087d2f26135970c2db49ee72.json`
- Modify: `templates/AlbumDexList/_macro.html.twig`
- Modify: `src/Controller/HomeController.php`

- [ ] **Step 1: Update `tests/resources/moco/Back/responses/dex/356a192b7913b04c54574d18c28d46e6395428ab.json`**

This fixture has 3 items (no-on-home trainer). Replace entirely:

```json
[
  {
    "dex": { "slug": "homepokemongo" },
    "settings": {
      "name": "Home Pokemon Go",
      "french_name": "Home Pokemon Go",
      "slug": "homepokemongo",
      "display_template": "list-7"
    },
    "flags": {
      "is_shiny": false,
      "is_private": false,
      "is_on_home": false,
      "is_display_form": false,
      "is_released": true,
      "is_premium": false,
      "is_custom": false
    }
  },
  {
    "dex": { "slug": "alpha" },
    "settings": {
      "name": "Alpha",
      "french_name": "Baron",
      "slug": "alpha",
      "display_template": "list-3"
    },
    "flags": {
      "is_shiny": false,
      "is_private": true,
      "is_on_home": false,
      "is_display_form": true,
      "is_released": true,
      "is_premium": false,
      "is_custom": false
    }
  },
  {
    "dex": { "slug": "mega" },
    "settings": {
      "name": "Mega",
      "french_name": "Méga",
      "slug": "mega",
      "display_template": "list-3"
    },
    "flags": {
      "is_shiny": false,
      "is_private": true,
      "is_on_home": false,
      "is_display_form": true,
      "is_released": true,
      "is_premium": false,
      "is_custom": false
    }
  }
]
```

- [ ] **Step 2: Update `tests/resources/moco/Back/responses/dex/da4b9237bacccdf19c0760cab7aec4a8359010b0.json`**

This fixture has 3 items (some on home). Replace entirely:

```json
[
  {
    "dex": { "slug": "homepokemongo" },
    "settings": {
      "name": "Home Pokemon Go",
      "french_name": "Home Pokemon Go",
      "slug": "homepokemongo",
      "display_template": "list-7"
    },
    "flags": {
      "is_shiny": false,
      "is_private": false,
      "is_on_home": true,
      "is_display_form": false,
      "is_released": true,
      "is_premium": false,
      "is_custom": false
    }
  },
  {
    "dex": { "slug": "alpha" },
    "settings": {
      "name": "Alpha",
      "french_name": "Baron",
      "slug": "alpha",
      "display_template": "list-3"
    },
    "flags": {
      "is_shiny": false,
      "is_private": true,
      "is_on_home": false,
      "is_display_form": true,
      "is_released": true,
      "is_premium": false,
      "is_custom": false
    }
  },
  {
    "dex": { "slug": "mega" },
    "settings": {
      "name": "Mega",
      "french_name": "Méga",
      "slug": "mega",
      "display_template": "list-3"
    },
    "flags": {
      "is_shiny": false,
      "is_private": true,
      "is_on_home": true,
      "is_display_form": true,
      "is_released": true,
      "is_premium": false,
      "is_custom": false
    }
  }
]
```

- [ ] **Step 3: Update `tests/resources/moco/Back/responses/dex/b6589fc6ab0dc82cf12099d1c2d40ab994e8410c.json`**

This fixture is an empty array. No change needed — it already serializes correctly as `[]`.

- [ ] **Step 4: Update `tests/resources/moco/Back/responses/dex/159bb9b6d090a313087d2f26135970c2db49ee72.json`**

This large fixture has 19 items. Apply the transformation rule to every entry. Replace entirely:

```json
[
  {
    "dex": { "slug": "redgreenblueyellow" },
    "settings": { "name": "Red, Green, Blue, Yellow", "french_name": "Rouge, Vert, Bleu, Jaune", "slug": "redgreenblueyellow", "display_template": "box" },
    "flags": { "is_shiny": true, "is_private": true, "is_on_home": true, "is_display_form": true, "is_released": true, "is_premium": false, "is_custom": false }
  },
  {
    "dex": { "slug": "goldsilvercrystal" },
    "settings": { "name": "Gold, Silver, Crystal", "french_name": "Or, Argent, Cristal", "slug": "goldsilvercrystal", "display_template": "box" },
    "flags": { "is_shiny": false, "is_private": false, "is_on_home": true, "is_display_form": true, "is_released": false, "is_premium": false, "is_custom": false }
  },
  {
    "dex": { "slug": "rubysapphireemerald" },
    "settings": { "name": "Ruby, Sapphire, Emerald", "french_name": "Rubis, Saphir, Émeraude", "slug": "rubysapphireemerald", "display_template": "box" },
    "flags": { "is_shiny": false, "is_private": true, "is_on_home": false, "is_display_form": true, "is_released": true, "is_premium": false, "is_custom": false }
  },
  {
    "dex": { "slug": "fireredleafgreen" },
    "settings": { "name": "Red Fire, Leaf Green", "french_name": "Rouge Feu, Vert Feuille", "slug": "fireredleafgreen", "display_template": "box" },
    "flags": { "is_shiny": false, "is_private": true, "is_on_home": false, "is_display_form": true, "is_released": true, "is_premium": false, "is_custom": false }
  },
  {
    "dex": { "slug": "diamondpearlplatinium" },
    "settings": { "name": "Diamond, Pearl, Platinium", "french_name": "Diamant, Perle, Platine", "slug": "diamondpearlplatinium", "display_template": "box" },
    "flags": { "is_shiny": false, "is_private": true, "is_on_home": false, "is_display_form": true, "is_released": true, "is_premium": false, "is_custom": false }
  },
  {
    "dex": { "slug": "heartgoldsoulsilver" },
    "settings": { "name": "Heart Gold, Soul Silver", "french_name": "Or HeartGold, Argent SoulSilver", "slug": "heartgoldsoulsilver", "display_template": "box" },
    "flags": { "is_shiny": false, "is_private": true, "is_on_home": false, "is_display_form": true, "is_released": true, "is_premium": false, "is_custom": false }
  },
  {
    "dex": { "slug": "blackwhite" },
    "settings": { "name": "Black, White", "french_name": "Noire, Blanche", "slug": "blackwhite", "display_template": "box" },
    "flags": { "is_shiny": false, "is_private": true, "is_on_home": false, "is_display_form": true, "is_released": true, "is_premium": false, "is_custom": false }
  },
  {
    "dex": { "slug": "black2white2" },
    "settings": { "name": "Black 2, White 2", "french_name": "Noire 2, Blanche 2", "slug": "black2white2", "display_template": "box" },
    "flags": { "is_shiny": false, "is_private": true, "is_on_home": false, "is_display_form": true, "is_released": true, "is_premium": false, "is_custom": false }
  },
  {
    "dex": { "slug": "xy" },
    "settings": { "name": "X, Y", "french_name": "X, Y", "slug": "xy", "display_template": "box" },
    "flags": { "is_shiny": false, "is_private": true, "is_on_home": false, "is_display_form": true, "is_released": true, "is_premium": false, "is_custom": false }
  },
  {
    "dex": { "slug": "omegarubyalphasapphire" },
    "settings": { "name": "Omega Ruby, Alpha Sapphire", "french_name": "Rubis Oméga, Saphir Alpha", "slug": "omegarubyalphasapphire", "display_template": "box" },
    "flags": { "is_shiny": false, "is_private": true, "is_on_home": false, "is_display_form": true, "is_released": true, "is_premium": false, "is_custom": false }
  },
  {
    "dex": { "slug": "sunmoon" },
    "settings": { "name": "Sun, Moon", "french_name": "Soleil, Lune", "slug": "sunmoon", "display_template": "box" },
    "flags": { "is_shiny": false, "is_private": true, "is_on_home": false, "is_display_form": true, "is_released": true, "is_premium": false, "is_custom": false }
  },
  {
    "dex": { "slug": "ultrasunultramoon" },
    "settings": { "name": "Ultra Sun, Ultra Moon", "french_name": "Ultra Soleil, Ultra Lune", "slug": "ultrasunultramoon", "display_template": "box" },
    "flags": { "is_shiny": false, "is_private": true, "is_on_home": false, "is_display_form": true, "is_released": true, "is_premium": false, "is_custom": false }
  },
  {
    "dex": { "slug": "letsgopikachuletsgoeevee" },
    "settings": { "name": "Let's Go Pikachu, Let's Go Eevee", "french_name": "Let's Go Pikachu, Let's Go Évoli", "slug": "letsgopikachuletsgoeevee", "display_template": "box" },
    "flags": { "is_shiny": false, "is_private": true, "is_on_home": false, "is_display_form": true, "is_released": true, "is_premium": false, "is_custom": false }
  },
  {
    "dex": { "slug": "swordshield" },
    "settings": { "name": "Sword, Shield", "french_name": "Épée, Bouclier", "slug": "swordshield", "display_template": "box" },
    "flags": { "is_shiny": false, "is_private": true, "is_on_home": false, "is_display_form": true, "is_released": true, "is_premium": false, "is_custom": false }
  },
  {
    "dex": { "slug": "brillantdiamondshiningpearl" },
    "settings": { "name": "Brillant Diamond, Shining Pearl", "french_name": "Diamant Étincelant, Perle Scintillante", "slug": "brillantdiamondshiningpearl", "display_template": "box" },
    "flags": { "is_shiny": false, "is_private": true, "is_on_home": false, "is_display_form": true, "is_released": true, "is_premium": false, "is_custom": false }
  },
  {
    "dex": { "slug": "pokemonlegendsarceus" },
    "settings": { "name": "Pokémon Legends Arceus", "french_name": "Légendes Pokémon : Arceus", "slug": "pokemonlegendsarceus", "display_template": "box" },
    "flags": { "is_shiny": false, "is_private": true, "is_on_home": false, "is_display_form": true, "is_released": true, "is_premium": false, "is_custom": false }
  },
  {
    "dex": { "slug": "home" },
    "settings": { "name": "Home", "french_name": "Home", "slug": "home", "display_template": "box" },
    "flags": { "is_shiny": false, "is_private": false, "is_on_home": true, "is_display_form": true, "is_released": true, "is_premium": false, "is_custom": false }
  },
  {
    "dex": { "slug": "homeshiny" },
    "settings": { "name": "Home Shiny", "french_name": "Home Chromatique", "slug": "homeshiny", "display_template": "box" },
    "flags": { "is_shiny": true, "is_private": false, "is_on_home": true, "is_display_form": true, "is_released": true, "is_premium": true, "is_custom": false }
  },
  {
    "dex": { "slug": "homepokemongo" },
    "settings": { "name": "Home Pokemon Go", "french_name": "Home Pokemon Go", "slug": "homepokemongo", "display_template": "list-7" },
    "flags": { "is_shiny": false, "is_private": false, "is_on_home": false, "is_display_form": false, "is_released": false, "is_premium": false, "is_custom": false }
  },
  {
    "dex": { "slug": "alpha" },
    "settings": { "name": "Alpha", "french_name": "Baron", "slug": "alpha", "display_template": "list-3" },
    "flags": { "is_shiny": false, "is_private": false, "is_on_home": true, "is_display_form": true, "is_released": true, "is_premium": true, "is_custom": false }
  },
  {
    "dex": { "slug": "mega" },
    "settings": { "name": "Mega", "french_name": "Méga", "slug": "mega", "display_template": "list-3" },
    "flags": { "is_shiny": false, "is_private": false, "is_on_home": true, "is_display_form": true, "is_released": true, "is_premium": true, "is_custom": false }
  }
]
```

- [ ] **Step 5: Update `tests/resources/moco/Back/responses/dex/admin.json`**

Apply the same transformation rule to all 21 entries (same items as `159bb9b6d090...` above, with slightly different flag values). Replace entirely — preserving the exact same `flags` and data as the original, only restructuring:

```json
[
  {
    "dex": { "slug": "redgreenblueyellow" },
    "settings": { "name": "Red, Green, Blue, Yellow", "french_name": "Rouge, Vert, Bleu, Jaune", "slug": "redgreenblueyellow", "display_template": "box" },
    "flags": { "is_shiny": true, "is_private": true, "is_on_home": true, "is_display_form": true, "is_released": true, "is_premium": false, "is_custom": false }
  },
  {
    "dex": { "slug": "goldsilvercrystal" },
    "settings": { "name": "Gold, Silver, Crystal", "french_name": "Or, Argent, Cristal", "slug": "goldsilvercrystal", "display_template": "box" },
    "flags": { "is_shiny": false, "is_private": false, "is_on_home": true, "is_display_form": true, "is_released": false, "is_premium": false, "is_custom": false }
  },
  {
    "dex": { "slug": "rubysapphireemerald" },
    "settings": { "name": "Ruby, Sapphire, Emerald", "french_name": "Rubis, Saphir, Émeraude", "slug": "rubysapphireemerald", "display_template": "box" },
    "flags": { "is_shiny": false, "is_private": true, "is_on_home": false, "is_display_form": true, "is_released": true, "is_premium": false, "is_custom": false }
  },
  {
    "dex": { "slug": "fireredleafgreen" },
    "settings": { "name": "Red Fire, Leaf Green", "french_name": "Rouge Feu, Vert Feuille", "slug": "fireredleafgreen", "display_template": "box" },
    "flags": { "is_shiny": false, "is_private": true, "is_on_home": false, "is_display_form": true, "is_released": true, "is_premium": false, "is_custom": false }
  },
  {
    "dex": { "slug": "diamondpearlplatinium" },
    "settings": { "name": "Diamond, Pearl, Platinium", "french_name": "Diamant, Perle, Platine", "slug": "diamondpearlplatinium", "display_template": "box" },
    "flags": { "is_shiny": false, "is_private": true, "is_on_home": false, "is_display_form": true, "is_released": true, "is_premium": false, "is_custom": false }
  },
  {
    "dex": { "slug": "heartgoldsoulsilver" },
    "settings": { "name": "Heart Gold, Soul Silver", "french_name": "Or HeartGold, Argent SoulSilver", "slug": "heartgoldsoulsilver", "display_template": "box" },
    "flags": { "is_shiny": false, "is_private": true, "is_on_home": false, "is_display_form": true, "is_released": true, "is_premium": false, "is_custom": false }
  },
  {
    "dex": { "slug": "blackwhite" },
    "settings": { "name": "Black, White", "french_name": "Noire, Blanche", "slug": "blackwhite", "display_template": "box" },
    "flags": { "is_shiny": false, "is_private": true, "is_on_home": false, "is_display_form": true, "is_released": true, "is_premium": false, "is_custom": false }
  },
  {
    "dex": { "slug": "black2white2" },
    "settings": { "name": "Black 2, White 2", "french_name": "Noire 2, Blanche 2", "slug": "black2white2", "display_template": "box" },
    "flags": { "is_shiny": false, "is_private": true, "is_on_home": false, "is_display_form": true, "is_released": true, "is_premium": false, "is_custom": false }
  },
  {
    "dex": { "slug": "xy" },
    "settings": { "name": "X, Y", "french_name": "X, Y", "slug": "xy", "display_template": "box" },
    "flags": { "is_shiny": false, "is_private": true, "is_on_home": false, "is_display_form": true, "is_released": true, "is_premium": false, "is_custom": false }
  },
  {
    "dex": { "slug": "omegarubyalphasapphire" },
    "settings": { "name": "Omega Ruby, Alpha Sapphire", "french_name": "Rubis Oméga, Saphir Alpha", "slug": "omegarubyalphasapphire", "display_template": "box" },
    "flags": { "is_shiny": false, "is_private": true, "is_on_home": false, "is_display_form": true, "is_released": true, "is_premium": false, "is_custom": false }
  },
  {
    "dex": { "slug": "sunmoon" },
    "settings": { "name": "Sun, Moon", "french_name": "Soleil, Lune", "slug": "sunmoon", "display_template": "box" },
    "flags": { "is_shiny": false, "is_private": true, "is_on_home": false, "is_display_form": true, "is_released": true, "is_premium": false, "is_custom": false }
  },
  {
    "dex": { "slug": "ultrasunultramoon" },
    "settings": { "name": "Ultra Sun, Ultra Moon", "french_name": "Ultra Soleil, Ultra Lune", "slug": "ultrasunultramoon", "display_template": "box" },
    "flags": { "is_shiny": false, "is_private": true, "is_on_home": false, "is_display_form": true, "is_released": true, "is_premium": false, "is_custom": false }
  },
  {
    "dex": { "slug": "letsgopikachuletsgoeevee" },
    "settings": { "name": "Let's Go Pikachu, Let's Go Eevee", "french_name": "Let's Go Pikachu, Let's Go Évoli", "slug": "letsgopikachuletsgoeevee", "display_template": "box" },
    "flags": { "is_shiny": false, "is_private": true, "is_on_home": false, "is_display_form": true, "is_released": true, "is_premium": false, "is_custom": false }
  },
  {
    "dex": { "slug": "swordshield" },
    "settings": { "name": "Sword, Shield", "french_name": "Épée, Bouclier", "slug": "swordshield", "display_template": "box" },
    "flags": { "is_shiny": false, "is_private": true, "is_on_home": false, "is_display_form": true, "is_released": true, "is_premium": false, "is_custom": false }
  },
  {
    "dex": { "slug": "brillantdiamondshiningpearl" },
    "settings": { "name": "Brillant Diamond, Shining Pearl", "french_name": "Diamant Étincelant, Perle Scintillante", "slug": "brillantdiamondshiningpearl", "display_template": "box" },
    "flags": { "is_shiny": false, "is_private": true, "is_on_home": false, "is_display_form": true, "is_released": true, "is_premium": false, "is_custom": false }
  },
  {
    "dex": { "slug": "pokemonlegendsarceus" },
    "settings": { "name": "Pokémon Legends Arceus", "french_name": "Légendes Pokémon : Arceus", "slug": "pokemonlegendsarceus", "display_template": "box" },
    "flags": { "is_shiny": false, "is_private": true, "is_on_home": false, "is_display_form": true, "is_released": true, "is_premium": false, "is_custom": false }
  },
  {
    "dex": { "slug": "home" },
    "settings": { "name": "Home", "french_name": "Home", "slug": "home", "display_template": "box" },
    "flags": { "is_shiny": false, "is_private": false, "is_on_home": true, "is_display_form": true, "is_released": true, "is_premium": false, "is_custom": false }
  },
  {
    "dex": { "slug": "homeshiny" },
    "settings": { "name": "Home Shiny", "french_name": "Home Chromatique", "slug": "homeshiny", "display_template": "box" },
    "flags": { "is_shiny": true, "is_private": false, "is_on_home": true, "is_display_form": true, "is_released": true, "is_premium": true, "is_custom": false }
  },
  {
    "dex": { "slug": "homepokemongo" },
    "settings": { "name": "Home Pokemon Go", "french_name": "Home Pokemon Go", "slug": "homepokemongo", "display_template": "list-7" },
    "flags": { "is_shiny": false, "is_private": false, "is_on_home": false, "is_display_form": false, "is_released": false, "is_premium": false, "is_custom": false }
  },
  {
    "dex": { "slug": "alpha" },
    "settings": { "name": "Alpha", "french_name": "Baron", "slug": "alpha", "display_template": "list-3" },
    "flags": { "is_shiny": false, "is_private": false, "is_on_home": true, "is_display_form": true, "is_released": true, "is_premium": true, "is_custom": false }
  },
  {
    "dex": { "slug": "mega" },
    "settings": { "name": "Mega", "french_name": "Méga", "slug": "mega", "display_template": "list-3" },
    "flags": { "is_shiny": false, "is_private": false, "is_on_home": true, "is_display_form": true, "is_released": true, "is_premium": true, "is_custom": false }
  }
]
```

- [ ] **Step 6: Update `tests/resources/moco/Back/responses/dex/trainer.json`**

This large fixture has 21 items (same entries but different flag values, includes `homepogo` with `is_custom: true`). Replace entirely:

```json
[
  {
    "dex": { "slug": "redgreenblueyellow" },
    "settings": { "name": "Red, Green, Blue, Yellow", "french_name": "Rouge, Vert, Bleu, Jaune", "slug": "redgreenblueyellow", "display_template": "box" },
    "flags": { "is_shiny": false, "is_private": true, "is_on_home": false, "is_display_form": true, "is_released": true, "is_premium": false, "is_custom": false }
  },
  {
    "dex": { "slug": "goldsilvercrystal" },
    "settings": { "name": "Gold, Silver, Crystal", "french_name": "Or, Argent, Cristal", "slug": "goldsilvercrystal", "display_template": "box" },
    "flags": { "is_shiny": false, "is_private": true, "is_on_home": false, "is_display_form": true, "is_released": true, "is_premium": false, "is_custom": false }
  },
  {
    "dex": { "slug": "rubysapphireemerald" },
    "settings": { "name": "Ruby, Sapphire, Emerald", "french_name": "Rubis, Saphir, Émeraude", "slug": "rubysapphireemerald", "display_template": "box" },
    "flags": { "is_shiny": false, "is_private": true, "is_on_home": false, "is_display_form": true, "is_released": true, "is_premium": false, "is_custom": false }
  },
  {
    "dex": { "slug": "fireredleafgreen" },
    "settings": { "name": "Red Fire, Leaf Green", "french_name": "Rouge Feu, Vert Feuille", "slug": "fireredleafgreen", "display_template": "box" },
    "flags": { "is_shiny": false, "is_private": true, "is_on_home": false, "is_display_form": true, "is_released": true, "is_premium": false, "is_custom": false }
  },
  {
    "dex": { "slug": "diamondpearlplatinium" },
    "settings": { "name": "Diamond, Pearl, Platinium", "french_name": "Diamant, Perle, Platine", "slug": "diamondpearlplatinium", "display_template": "box" },
    "flags": { "is_shiny": false, "is_private": true, "is_on_home": false, "is_display_form": true, "is_released": true, "is_premium": false, "is_custom": false }
  },
  {
    "dex": { "slug": "heartgoldsoulsilver" },
    "settings": { "name": "Heart Gold, Soul Silver", "french_name": "Or HeartGold, Argent SoulSilver", "slug": "heartgoldsoulsilver", "display_template": "box" },
    "flags": { "is_shiny": false, "is_private": true, "is_on_home": false, "is_display_form": true, "is_released": true, "is_premium": false, "is_custom": false }
  },
  {
    "dex": { "slug": "blackwhite" },
    "settings": { "name": "Black, White", "french_name": "Noire, Blanche", "slug": "blackwhite", "display_template": "box" },
    "flags": { "is_shiny": false, "is_private": true, "is_on_home": false, "is_display_form": true, "is_released": true, "is_premium": false, "is_custom": false }
  },
  {
    "dex": { "slug": "black2white2" },
    "settings": { "name": "Black 2, White 2", "french_name": "Noire 2, Blanche 2", "slug": "black2white2", "display_template": "box" },
    "flags": { "is_shiny": false, "is_private": true, "is_on_home": false, "is_display_form": true, "is_released": true, "is_premium": false, "is_custom": false }
  },
  {
    "dex": { "slug": "xy" },
    "settings": { "name": "X, Y", "french_name": "X, Y", "slug": "xy", "display_template": "box" },
    "flags": { "is_shiny": false, "is_private": true, "is_on_home": false, "is_display_form": true, "is_released": true, "is_premium": false, "is_custom": false }
  },
  {
    "dex": { "slug": "omegarubyalphasapphire" },
    "settings": { "name": "Omega Ruby, Alpha Sapphire", "french_name": "Rubis Oméga, Saphir Alpha", "slug": "omegarubyalphasapphire", "display_template": "box" },
    "flags": { "is_shiny": false, "is_private": true, "is_on_home": false, "is_display_form": true, "is_released": true, "is_premium": false, "is_custom": false }
  },
  {
    "dex": { "slug": "sunmoon" },
    "settings": { "name": "Sun, Moon", "french_name": "Soleil, Lune", "slug": "sunmoon", "display_template": "box" },
    "flags": { "is_shiny": false, "is_private": true, "is_on_home": false, "is_display_form": true, "is_released": true, "is_premium": false, "is_custom": false }
  },
  {
    "dex": { "slug": "ultrasunultramoon" },
    "settings": { "name": "Ultra Sun, Ultra Moon", "french_name": "Ultra Soleil, Ultra Lune", "slug": "ultrasunultramoon", "display_template": "box" },
    "flags": { "is_shiny": false, "is_private": true, "is_on_home": false, "is_display_form": true, "is_released": true, "is_premium": false, "is_custom": false }
  },
  {
    "dex": { "slug": "letsgopikachuletsgoeevee" },
    "settings": { "name": "Let's Go Pikachu, Let's Go Eevee", "french_name": "Let's Go Pikachu, Let's Go Évoli", "slug": "letsgopikachuletsgoeevee", "display_template": "box" },
    "flags": { "is_shiny": false, "is_private": true, "is_on_home": false, "is_display_form": true, "is_released": true, "is_premium": false, "is_custom": false }
  },
  {
    "dex": { "slug": "swordshield" },
    "settings": { "name": "Sword, Shield", "french_name": "Épée, Bouclier", "slug": "swordshield", "display_template": "box" },
    "flags": { "is_shiny": false, "is_private": false, "is_on_home": true, "is_display_form": true, "is_released": true, "is_premium": false, "is_custom": false }
  },
  {
    "dex": { "slug": "brillantdiamondshiningpearl" },
    "settings": { "name": "Brillant Diamond, Shining Pearl", "french_name": "Diamant Étincelant, Perle Scintillante", "slug": "brillantdiamondshiningpearl", "display_template": "box" },
    "flags": { "is_shiny": false, "is_private": true, "is_on_home": false, "is_display_form": true, "is_released": true, "is_premium": false, "is_custom": false }
  },
  {
    "dex": { "slug": "pokemonlegendsarceus" },
    "settings": { "name": "Pokémon Legends Arceus", "french_name": "Légendes Pokémon : Arceus", "slug": "pokemonlegendsarceus", "display_template": "box" },
    "flags": { "is_shiny": false, "is_private": true, "is_on_home": false, "is_display_form": true, "is_released": true, "is_premium": false, "is_custom": false }
  },
  {
    "dex": { "slug": "home" },
    "settings": { "name": "Home", "french_name": "Home", "slug": "home", "display_template": "box" },
    "flags": { "is_shiny": false, "is_private": false, "is_on_home": true, "is_display_form": true, "is_released": true, "is_premium": false, "is_custom": false }
  },
  {
    "dex": { "slug": "homeshiny" },
    "settings": { "name": "Home\nShiny", "french_name": "Home\nChromatique", "slug": "homeshiny", "display_template": "box" },
    "flags": { "is_shiny": true, "is_private": false, "is_on_home": true, "is_display_form": true, "is_released": true, "is_premium": false, "is_custom": false }
  },
  {
    "dex": { "slug": "homepogo" },
    "settings": { "name": "Home Pokemon Go", "french_name": "Home Pokemon Go", "slug": "homepogo", "display_template": "list-7" },
    "flags": { "is_shiny": false, "is_private": false, "is_on_home": true, "is_display_form": false, "is_released": true, "is_premium": false, "is_custom": true }
  },
  {
    "dex": { "slug": "alpha" },
    "settings": { "name": "Alpha", "french_name": "Baron", "slug": "alpha", "display_template": "list-3" },
    "flags": { "is_shiny": false, "is_private": false, "is_on_home": true, "is_display_form": true, "is_released": true, "is_premium": true, "is_custom": false }
  },
  {
    "dex": { "slug": "mega" },
    "settings": { "name": "Mega", "french_name": "Méga", "slug": "mega", "display_template": "list-3" },
    "flags": { "is_shiny": false, "is_private": false, "is_on_home": true, "is_display_form": true, "is_released": true, "is_premium": true, "is_custom": false }
  }
]
```

- [ ] **Step 7: Update the Twig macro**

In `templates/AlbumDexList/_macro.html.twig`, apply these changes:

```twig
{# OLD line 3 #}
{% set url = path('app_'~type~'index_index', {'dexSlug': dex.slug, 't': forcedTrainerId ?: null}) %}
{# NEW line 3 #}
{% set url = path('app_'~type~'index_index', {'dexSlug': dex.dex.slug, 't': forcedTrainerId ?: null}) %}

{# OLD line 7 #}
{% set bannerUrl = dexBannerUrl|format(dex.slug) %}
{# NEW line 7 #}
{% set bannerUrl = dexBannerUrl|format(dex.dex.slug) %}

{# OLD line 19 #}
{% if dex.flags.is_premium %}
{# NEW line 19 #}
{% if dex.flags.isPremium %}

{# OLD line 24 #}
{% if not dex.flags.is_released %}
{# NEW line 24 #}
{% if not dex.flags.isReleased %}

{# OLD line 29 #}
{% if dex.flags.is_custom %}
{# NEW line 29 #}
{% if dex.flags.isCustom %}

{# OLD line 36 #}
{% set titles = (locale is same as('fr') ? dex.french_name : dex.name)|split("\n") %}
{# NEW line 36 #}
{% set titles = (locale is same as('fr') ? dex.settings.frenchName : dex.settings.name)|split("\n") %}

{# OLD line 78 (in listAlbums macro) #}
{% for item in dex|filter(item => item.flags.is_on_home is same as(true)) %}
{# NEW line 78 #}
{% for item in dex|filter(item => item.flags.isOnHome is same as(true)) %}

{# OLD line 87 (in listElections macro) #}
{% for item in dex|filter(item => item.flags.is_on_home is same as(true)) %}
{# NEW line 87 #}
{% for item in dex|filter(item => item.flags.isOnHome is same as(true)) %}
```

The complete new file content for `templates/AlbumDexList/_macro.html.twig`:

```twig

{% macro item(type, dex, locale, forcedTrainerId) %}
{% set url = path('app_'~type~'index_index', {'dexSlug': dex.dex.slug, 't': forcedTrainerId ?: null}) %}
<div class="col-lg-3 col-md-4 col-sm-6 col-12 dex-item position-relative">
  <div class="card">
    <a href="{{ url }}">
      {% set bannerUrl = dexBannerUrl|format(dex.dex.slug) %}
      {% set defaultBannerUrl = '/img/banner/default.webp' %}
      <img
        src="{{ bannerUrl }}"
        class="card-img"
        alt=""
        loading="lazy"
        onerror="this.onerror=null;this.src='{{ defaultBannerUrl }}';"
      >
    </a>
    <div class="card-body">
      <div class="position-absolute top-0 end-0 me-2">
        {% if dex.flags.isPremium %}
        <span class="dex_is_premium badge text-bg-success" data-bs-toggle="tooltip" data-bs-title="{{ ('trainer.dex.attributes.is_premium.label')|trans }}">
          <i class="bi bi-patch-plus"></i>
        </span>
        {% endif %}
        {% if not dex.flags.isReleased %}
        <span class="dex_not_is_released badge text-bg-danger" data-bs-toggle="tooltip" data-bs-title="{{ ('trainer.dex.attributes.not.is_released.label')|trans }}">
          <i class="bi bi-lock"></i>
        </span>
        {% endif %}
        {% if dex.flags.isCustom %}
        <span class="dex_is_custom badge text-bg-info" data-bs-toggle="tooltip" data-bs-title="{{ ('trainer.dex.attributes.is_custom.label')|trans }}">
          <i class="bi bi-person"></i>
        </span>
        {% endif %}
      </div>

      {% set titles = (locale is same as('fr') ? dex.settings.frenchName : dex.settings.name)|split("\n") %}
      {% set title = titles.0 %}
      {% set subtitle = titles.1 is defined ? titles.1 : '' %}
      <h2 class="h5 card-title">
        <a href="{{ url }}">
          {{ title }}
        </a>
      </h2>
      {% if subtitle is not empty %}
      <h3 class="h6 card-subtitle mb-2 text-body-secondary">
        <a href="{{ url }}">
          {{ subtitle }}
        </a>
      </h3>
      {% endif %}

      {% if dex.dex_total_count is defined %}
      <span class="badge rounded-pill bg-primary mb-3">
        {{ dex.dex_total_count|number_format(0, '.', ' ') }}
        {{ 'election_dex.dex.total_count_suffixe'|trans }}
      </span>
      {% endif %}

      {% if dex.description is defined %}
      <p class="small text-start text-body-secondary">
        {{ (locale is same as('fr') ? dex.french_description : dex.description) }}
      </p>
      {% endif %}

    </div>
  </div>
</div>
{% endmacro %}

{% macro none() %}
<div class="alert alert-secondary mt-5" role="alert">
    <p>{{ 'home.no_dex'|trans|replace({'<a>': '<a href="'~path('app_trainerindex_index')~'">'})|raw }}</p>
</div>
{% endmacro %}

{% macro listAlbums(dex, locale, forcedTrainerId = null) %}
<div class="row g-lg-4 g-md-4 g-sm-3 g-2">
  {% for item in dex|filter(item => item.flags.isOnHome is same as(true)) %}
  {{ _self.item('album', item, locale, forcedTrainerId) }}
  {% else %}
  {{ _self.none() }}
{% endfor %}
</div>
{% endmacro %}

{% macro listElections(dex, locale, forcedTrainerId = null) %}
<div class="row g-lg-4 g-md-4 g-sm-3 g-2">
  {% for item in dex|filter(item => item.flags.isOnHome is same as(true)) %}
  {{ _self.item('election', item, locale, forcedTrainerId) }}
  {% else %}
  {{ _self.none() }}
{% endfor %}
</div>
{% endmacro %}
```

- [ ] **Step 8: Remove `dump()` from `HomeController`**

In `src/Controller/HomeController.php`, remove the `dump($album);` line (line 33). The file should go from:

```php
        $album = $service->get($demoUserId);

        dump($album);

        return $this->render(
```

to:

```php
        $album = $service->get($demoUserId);

        return $this->render(
```

- [ ] **Step 9: Run integration tests**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Album/Dex/AlbumDexListTest.php
docker compose exec php php vendor/bin/phpunit tests/src/Integration/Controller/Home/HomeTest.php
```

Expected: all green.

- [ ] **Step 10: Run full test suite**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/
```

Expected: all green.

- [ ] **Step 11: Run code quality checks**

```bash
docker compose exec php php tools/phpstan/vendor/bin/phpstan --memory-limit=-1
docker compose exec php php tools/psalm/vendor/bin/psalm --show-info=false --no-cache --taint-analysis
docker compose exec php php tools/php-cs-fixer/vendor/bin/php-cs-fixer fix --dry-run
```

If php-cs-fixer reports issues, run the fixer:

```bash
docker compose exec php php tools/php-cs-fixer/vendor/bin/php-cs-fixer fix
```

Expected: all clean (no new errors).
