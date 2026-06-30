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
