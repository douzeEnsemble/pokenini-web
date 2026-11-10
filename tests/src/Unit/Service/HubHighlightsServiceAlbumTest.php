<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\ResponseObject\Album\DexFlags;
use App\ResponseObject\Album\DexListItem;
use App\ResponseObject\Album\DexListItemRef;
use App\ResponseObject\Album\DexListItemSettings;
use App\Service\Back\ColorGameService;
use App\Service\Back\GetAlbumDexListService;
use App\Service\Back\GetElectionDexListService;
use App\Service\Back\ShinyGameService;
use App\Service\Back\WhichShinyGameService;
use App\Service\HubHighlightsService;
use App\Service\LocaleService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @internal
 */
#[CoversClass(HubHighlightsService::class)]
final class HubHighlightsServiceAlbumTest extends TestCase
{
    #[Test]
    public function albums(): void
    {
        $albumService = $this->createMock(GetAlbumDexListService::class);
        $albumService->expects($this->once())
            ->method('get')
            ->with(null)
            ->willReturn([
                self::album('a', false),
                self::album('b', true),
                self::album('c', true),
                self::album('d', false),
                self::album('e', true),
                self::album('f', true),
            ])
        ;

        $albums = $this->service(albumService: $albumService)->getAlbums();

        $this->assertSame(
            ['b', 'c', 'e'],
            array_map(static fn (DexListItem $item): string => $item->getDex()->getSlug(), $albums),
        );
        $this->assertSame([0, 1, 2], array_keys($albums));
    }

    #[Test]
    public function noAlbum(): void
    {
        $albumService = $this->createStub(GetAlbumDexListService::class);
        $albumService->method('get')->willReturn([self::album('a', false)]);

        $this->assertSame([], $this->service(albumService: $albumService)->getAlbums());
    }

    private function service(
        ?GetAlbumDexListService $albumService = null,
        ?GetElectionDexListService $electionService = null,
        ?ColorGameService $colorGameService = null,
        ?ShinyGameService $shinyGameService = null,
        ?WhichShinyGameService $whichShinyGameService = null,
        ?LocaleService $localeService = null,
    ): HubHighlightsService {
        return new HubHighlightsService(
            $albumService ?? $this->createStub(GetAlbumDexListService::class),
            $electionService ?? $this->createStub(GetElectionDexListService::class),
            $colorGameService ?? $this->createStub(ColorGameService::class),
            $shinyGameService ?? $this->createStub(ShinyGameService::class),
            $whichShinyGameService ?? $this->createStub(WhichShinyGameService::class),
            $localeService ?? new LocaleService(new RequestStack(), ['en', 'fr'], 'en'),
        );
    }

    private static function flags(bool $isOnHub): DexFlags
    {
        return new DexFlags(
            isShiny: false,
            isPrivate: false,
            isOnHome: false,
            isDisplayForm: false,
            isReleased: true,
            isPremium: false,
            isCustom: false,
            isOnHub: $isOnHub,
        );
    }

    private static function album(string $slug, bool $isOnHub): DexListItem
    {
        return new DexListItem(
            dex: new DexListItemRef(slug: $slug),
            settings: new DexListItemSettings(
                name: $slug,
                frenchName: $slug,
                slug: $slug,
                displayTemplate: 'box',
            ),
            flags: self::flags($isOnHub),
        );
    }
}
