<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\ColorGame;

use App\ResponseObject\ColorGame\ColorGameDexSummary;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ColorGameDexSummary::class)]
final class ColorGameDexSummaryTest extends TestCase
{
    #[Test]
    public function getters(): void
    {
        $object = new ColorGameDexSummary('demo', 'Demo', 'Démo');

        $this->assertSame('demo', $object->getSlug());
        $this->assertSame('Demo', $object->getName());
        $this->assertSame('Démo', $object->getFrenchName());
        $this->assertSame(['en' => 'Demo', 'fr' => 'Démo'], $object->getNames());
    }

    #[Test]
    public function inASingleLanguage(): void
    {
        $object = new ColorGameDexSummary('demo', 'Démo', null);

        $this->assertNull($object->getFrenchName());
        $this->assertSame(['*' => 'Démo'], $object->getNames());
    }
}
