<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\ColorGame;

use App\ResponseObject\ColorGame\ColorGameChoice;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ColorGameChoice::class)]
final class ColorGameChoiceTest extends TestCase
{
    #[Test]
    public function getters(): void
    {
        $object = new ColorGameChoice(2, ['#000000', '#FFFFFF'], ['black', 'white'], ['noir', 'blanc']);

        $this->assertSame(2, $object->getIndex());
        $this->assertSame(['#000000', '#FFFFFF'], $object->getCodes());
        $this->assertSame(['black', 'white'], $object->getNames());
        $this->assertSame(['noir', 'blanc'], $object->getFrenchNames());
        $this->assertSame(['en' => ['black', 'white'], 'fr' => ['noir', 'blanc']], $object->getNamesByLocale());
    }

    #[Test]
    public function inASingleLanguage(): void
    {
        $object = new ColorGameChoice(2, ['#000000', '#FFFFFF'], ['noir', 'blanc'], null);

        $this->assertNull($object->getFrenchNames());
        $this->assertSame(['*' => ['noir', 'blanc']], $object->getNamesByLocale());
    }
}
