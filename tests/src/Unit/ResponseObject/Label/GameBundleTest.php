<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Label;

use App\ResponseObject\Label\GameBundle;
use App\ResponseObject\Label\Generation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(GameBundle::class)]
final class GameBundleTest extends TestCase
{
    #[Test]
    public function constructor(): void
    {
        $object = new GameBundle(
            'Toto',
            'Tautaux',
            'toto',
            new Generation('gen_y'),
        );

        $this->assertSame('Toto', $object->getName());
        $this->assertSame('Tautaux', $object->getFrenchName());
        $this->assertSame(['en' => 'Toto', 'fr' => 'Tautaux'], $object->getNames());
        $this->assertSame('toto', $object->getSlug());
        $this->assertSame('gen_y', $object->getGeneration()->getSlug());
    }

    #[Test]
    public function inASingleLanguage(): void
    {
        $object = new GameBundle('Rouge, Vert', null, 'rgby', new Generation('1'));

        $this->assertSame('Rouge, Vert', $object->getName());
        $this->assertNull($object->getFrenchName());
        $this->assertSame(['*' => 'Rouge, Vert'], $object->getNames());
    }
}
