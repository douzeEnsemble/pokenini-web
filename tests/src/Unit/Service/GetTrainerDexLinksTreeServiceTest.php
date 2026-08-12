<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\ResponseObject\Album\DexFlags;
use App\ResponseObject\Album\DexListItem;
use App\ResponseObject\Album\DexListItemRef;
use App\ResponseObject\Album\DexListItemSettings;
use App\ResponseObject\Album\TrainerDexLink;
use App\Service\Back\GetTrainerDexListService;
use App\Service\Back\TrainerDexLinkService;
use App\Service\GetTrainerDexLinksTreeService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal
 *
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
#[CoversClass(GetTrainerDexLinksTreeService::class)]
final class GetTrainerDexLinksTreeServiceTest extends TestCase
{
    #[Test]
    public function getTreeBuildsEdgesAcrossAllDexes(): void
    {
        $swordshield = $this->createDexListItem('swordshield');
        $scarletviolet = $this->createDexListItem('scarletviolet');
        $legendsarceus = $this->createDexListItem('legendsarceus');

        $getTrainerDexListService = $this->createMock(GetTrainerDexListService::class);
        $getTrainerDexListService
            ->expects($this->once())
            ->method('get')
            ->willReturn([$swordshield, $scarletviolet, $legendsarceus])
        ;

        $trainerDexLinkService = $this->createMock(TrainerDexLinkService::class);
        $trainerDexLinkService
            ->expects($this->exactly(3))
            ->method('list')
            ->willReturnMap([
                ['swordshield', [$this->createLink('link-1', 'to', 'scarletviolet')]],
                ['scarletviolet', [$this->createLink('link-2', 'both', 'legendsarceus')]],
                ['legendsarceus', []],
            ])
        ;

        $service = new GetTrainerDexLinksTreeService($getTrainerDexListService, $trainerDexLinkService);
        $tree = $service->getTree();

        $edges = $tree->getEdges();
        $this->assertCount(2, $edges);

        $this->assertSame('link-1', $edges[0]->getId());
        $this->assertSame('to', $edges[0]->getMode());
        $this->assertSame($swordshield, $edges[0]->getFrom());
        $this->assertSame($scarletviolet, $edges[0]->getTo());

        $this->assertSame('link-2', $edges[1]->getId());
        $this->assertSame('both', $edges[1]->getMode());
        $this->assertSame($scarletviolet, $edges[1]->getFrom());
        $this->assertSame($legendsarceus, $edges[1]->getTo());
    }

    #[Test]
    public function getTreeSwapsFromAndToWhenDirectionIsFrom(): void
    {
        $swordshield = $this->createDexListItem('swordshield');
        $scarletviolet = $this->createDexListItem('scarletviolet');

        $getTrainerDexListService = $this->createMock(GetTrainerDexListService::class);
        $getTrainerDexListService
            ->expects($this->once())
            ->method('get')
            ->willReturn([$swordshield, $scarletviolet])
        ;

        $trainerDexLinkService = $this->createMock(TrainerDexLinkService::class);
        $trainerDexLinkService
            ->expects($this->exactly(2))
            ->method('list')
            ->willReturnMap([
                ['swordshield', [$this->createLink('link-1', 'from', 'scarletviolet')]],
                ['scarletviolet', []],
            ])
        ;

        $service = new GetTrainerDexLinksTreeService($getTrainerDexListService, $trainerDexLinkService);
        $edges = $service->getTree()->getEdges();

        $this->assertCount(1, $edges);
        // direction 'from' means the link flows from the target back onto the source dex
        // being iterated, so from/to end up swapped relative to the reported source.
        $this->assertSame('to', $edges[0]->getMode());
        $this->assertSame($scarletviolet, $edges[0]->getFrom());
        $this->assertSame($swordshield, $edges[0]->getTo());
    }

    #[Test]
    public function getTreeKeepsBidirectionalModeWhenPairIsReportedAgainFromTheOtherSide(): void
    {
        $swordshield = $this->createDexListItem('swordshield');
        $scarletviolet = $this->createDexListItem('scarletviolet');

        $getTrainerDexListService = $this->createMock(GetTrainerDexListService::class);
        $getTrainerDexListService
            ->expects($this->once())
            ->method('get')
            ->willReturn([$swordshield, $scarletviolet])
        ;

        $trainerDexLinkService = $this->createMock(TrainerDexLinkService::class);
        $trainerDexLinkService
            ->expects($this->exactly(2))
            ->method('list')
            ->willReturnMap([
                ['swordshield', [$this->createLink('link-1', 'both', 'scarletviolet')]],
                ['scarletviolet', [$this->createLink('link-2', 'to', 'swordshield')]],
            ])
        ;

        $service = new GetTrainerDexLinksTreeService($getTrainerDexListService, $trainerDexLinkService);
        $edges = $service->getTree()->getEdges();

        $this->assertCount(1, $edges);
        $this->assertSame('link-1', $edges[0]->getId());
        $this->assertSame('both', $edges[0]->getMode());
    }

    #[Test]
    public function getTreeStillProcessesLaterLinksAfterASkippedDuplicate(): void
    {
        // The dedup skip must `continue` the inner loop, not abort it — otherwise every
        // link reported after the duplicate, for that same source dex, would be lost too.
        $aaa = $this->createDexListItem('aaa');
        $bbb = $this->createDexListItem('bbb');
        $ccc = $this->createDexListItem('ccc');

        $getTrainerDexListService = $this->createMock(GetTrainerDexListService::class);
        $getTrainerDexListService
            ->expects($this->once())
            ->method('get')
            ->willReturn([$aaa, $bbb, $ccc])
        ;

        $trainerDexLinkService = $this->createMock(TrainerDexLinkService::class);
        $trainerDexLinkService
            ->expects($this->exactly(3))
            ->method('list')
            ->willReturnMap([
                ['aaa', [$this->createLink('link-ab', 'both', 'bbb')]],
                ['bbb', [
                    // Reports the already-recorded aaa<->bbb pair again: skipped as a duplicate.
                    $this->createLink('link-ba', 'to', 'aaa'),
                    // A distinct pair, reported right after the skipped duplicate.
                    $this->createLink('link-bc', 'to', 'ccc'),
                ]],
                ['ccc', []],
            ])
        ;

        $service = new GetTrainerDexLinksTreeService($getTrainerDexListService, $trainerDexLinkService);
        $edges = $service->getTree()->getEdges();

        $this->assertCount(2, $edges);
        $this->assertSame('link-ab', $edges[0]->getId());
        $this->assertSame('link-bc', $edges[1]->getId());
        $this->assertSame($bbb, $edges[1]->getFrom());
        $this->assertSame($ccc, $edges[1]->getTo());
    }

    #[Test]
    public function getTreeIgnoresHttpExceptionsFromASingleDex(): void
    {
        $swordshield = $this->createDexListItem('swordshield');
        $scarletviolet = $this->createDexListItem('scarletviolet');

        $getTrainerDexListService = $this->createMock(GetTrainerDexListService::class);
        $getTrainerDexListService
            ->expects($this->once())
            ->method('get')
            ->willReturn([$swordshield, $scarletviolet])
        ;

        $httpException = new class('http error') extends \RuntimeException implements HttpExceptionInterface {
            #[\Override]
            public function getResponse(): ResponseInterface
            {
                throw new \LogicException('not needed for this test');
            }
        };

        $trainerDexLinkService = $this->createMock(TrainerDexLinkService::class);
        $trainerDexLinkService
            ->expects($this->exactly(2))
            ->method('list')
            ->willReturnCallback(function (string $dexSlug) use ($httpException) {
                if ('swordshield' === $dexSlug) {
                    throw $httpException;
                }

                return [];
            })
        ;

        $service = new GetTrainerDexLinksTreeService($getTrainerDexListService, $trainerDexLinkService);
        $tree = $service->getTree();

        $this->assertTrue($tree->isEmpty());
    }

    #[Test]
    public function getTreeIgnoresTransportExceptionsFromASingleDex(): void
    {
        $swordshield = $this->createDexListItem('swordshield');

        $getTrainerDexListService = $this->createMock(GetTrainerDexListService::class);
        $getTrainerDexListService
            ->expects($this->once())
            ->method('get')
            ->willReturn([$swordshield])
        ;

        $transportException = new class('transport error') extends \RuntimeException implements TransportExceptionInterface {};

        $trainerDexLinkService = $this->createMock(TrainerDexLinkService::class);
        $trainerDexLinkService
            ->expects($this->once())
            ->method('list')
            ->willReturnCallback(function () use ($transportException): never {
                throw $transportException;
            })
        ;

        $service = new GetTrainerDexLinksTreeService($getTrainerDexListService, $trainerDexLinkService);
        $tree = $service->getTree();

        $this->assertTrue($tree->isEmpty());
    }

    #[Test]
    public function getTreeDropsLinksWhoseTargetDexIsNotInTheTrainerDexList(): void
    {
        $swordshield = $this->createDexListItem('swordshield');

        $getTrainerDexListService = $this->createMock(GetTrainerDexListService::class);
        $getTrainerDexListService
            ->expects($this->once())
            ->method('get')
            ->willReturn([$swordshield])
        ;

        $trainerDexLinkService = $this->createMock(TrainerDexLinkService::class);
        $trainerDexLinkService
            ->expects($this->once())
            ->method('list')
            ->willReturnMap([
                ['swordshield', [$this->createLink('link-1', 'to', 'unknowndex')]],
            ])
        ;

        $service = new GetTrainerDexLinksTreeService($getTrainerDexListService, $trainerDexLinkService);
        $tree = $service->getTree();

        $this->assertTrue($tree->isEmpty());
    }

    #[Test]
    public function getTreeStillProcessesLaterLinksAfterADroppedOne(): void
    {
        // A dex's link list can mix a droppable link (target outside the trainer's own
        // dex list) with a resolvable one. The drop must `continue` the inner loop, not
        // abort it — otherwise every link after the dropped one in that same dex's list
        // would be silently lost too.
        $swordshield = $this->createDexListItem('swordshield');
        $scarletviolet = $this->createDexListItem('scarletviolet');

        $getTrainerDexListService = $this->createMock(GetTrainerDexListService::class);
        $getTrainerDexListService
            ->expects($this->once())
            ->method('get')
            ->willReturn([$swordshield, $scarletviolet])
        ;

        $trainerDexLinkService = $this->createMock(TrainerDexLinkService::class);
        $trainerDexLinkService
            ->expects($this->exactly(2))
            ->method('list')
            ->willReturnMap([
                ['swordshield', [
                    $this->createLink('link-dropped', 'to', 'unknowndex'),
                    $this->createLink('link-kept', 'to', 'scarletviolet'),
                ]],
                ['scarletviolet', []],
            ])
        ;

        $service = new GetTrainerDexLinksTreeService($getTrainerDexListService, $trainerDexLinkService);
        $edges = $service->getTree()->getEdges();

        $this->assertCount(1, $edges);
        $this->assertSame('link-kept', $edges[0]->getId());
        $this->assertSame($swordshield, $edges[0]->getFrom());
        $this->assertSame($scarletviolet, $edges[0]->getTo());
    }

    private function createLink(string $id, string $direction, string $targetDexSlug): TrainerDexLink
    {
        return new TrainerDexLink(
            id: $id,
            direction: $direction,
            targetDexSlug: $targetDexSlug,
            targetName: $targetDexSlug,
            targetFrenchName: $targetDexSlug,
        );
    }

    private function createDexListItem(string $slug): DexListItem
    {
        return new DexListItem(
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
                isOnHome: true,
                isDisplayForm: true,
                isReleased: true,
                isPremium: false,
                isCustom: false,
            ),
        );
    }
}
