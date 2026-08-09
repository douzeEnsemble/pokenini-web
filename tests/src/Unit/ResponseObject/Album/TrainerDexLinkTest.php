<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Album;

use App\ResponseObject\Album\TrainerDexLink;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(TrainerDexLink::class)]
final class TrainerDexLinkTest extends TestCase
{
    #[Test]
    public function getters(): void
    {
        $link = new TrainerDexLink(
            id: 'link-1',
            direction: 'both',
            targetDexSlug: 'shiny',
            targetName: 'Shiny Living',
            targetFrenchName: 'Vivarium Chromatique',
        );

        $this->assertSame('link-1', $link->getId());
        $this->assertSame('both', $link->getDirection());
        $this->assertSame('shiny', $link->getTargetDexSlug());
        $this->assertSame('Shiny Living', $link->getTargetName());
        $this->assertSame('Vivarium Chromatique', $link->getTargetFrenchName());
    }
}
