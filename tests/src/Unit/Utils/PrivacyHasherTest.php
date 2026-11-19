<?php

declare(strict_types=1);

namespace App\Tests\Unit\Utils;

use App\Utils\PrivacyHasher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(PrivacyHasher::class)]
final class PrivacyHasherTest extends TestCase
{
    #[Test]
    public function hash(): void
    {
        // Stable on purpose: the same hash is computed by the other apps and expected in the fixtures.
        $this->assertSame('0b9c2625dc21ef05f6ad4ddf47c5f203837aa32c', PrivacyHasher::hash('toto'));
        $this->assertSame('f7e79ca8eb0b31ee4d5d6c181416667ffee528ed', PrivacyHasher::hash('titi'));
    }
}
