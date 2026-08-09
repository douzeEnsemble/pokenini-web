<?php

declare(strict_types=1);

namespace App\Tests\Unit\ResponseObject\Label;

use App\ResponseObject\Label\Generation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Generation::class)]
final class GenerationTest extends TestCase
{
    #[Test]
    public function constructor(): void
    {
        $object = new Generation('gen_y');

        $this->assertSame('gen_y', $object->getSlug());
    }
}
