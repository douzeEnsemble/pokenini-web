<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Common;

use App\ResponseObject\Common\NamesMap;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(NamesMap::class)]
final class NamesMapTest extends TestCase
{
    #[Test]
    public function fromTwoLanguages(): void
    {
        $this->assertSame(['en' => 'Fighting', 'fr' => 'Combat'], NamesMap::from('Fighting', 'Combat'));
    }

    #[Test]
    public function fromASingleResolvedLanguage(): void
    {
        $this->assertSame(['*' => 'Combat'], NamesMap::from('Combat', null));
    }
}
