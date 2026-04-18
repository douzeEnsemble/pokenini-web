<?php

declare(strict_types=1);

namespace App\Tests\Unit\DTO;

use App\DTO\DexFiltersRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[CoversClass(DexFiltersRequest::class)]
final class DexFiltersRequestTest extends TestCase
{
    public function testDexFiltersFromRequestEmpty(): void
    {
        $request = new Request([]);

        $filters = DexFiltersRequest::DexFiltersFromRequest($request);

        $this->assertNull($filters->privacy->value);
        $this->assertNull($filters->homepaged->value);
        $this->assertNull($filters->released->value);
        $this->assertNull($filters->shiny->value);
        $this->assertNull($filters->premium->value);
    }

    public function testDexFiltersFromRequest(): void
    {
        $request = new Request([
            'p' => '1',
            'h' => '0',
            'r' => '1',
            's' => '0',
            'm' => '0',
        ]);

        $filters = DexFiltersRequest::DexFiltersFromRequest($request);

        $this->assertTrue($filters->privacy->value);
        $this->assertFalse($filters->homepaged->value);
        $this->assertTrue($filters->released->value);
        $this->assertFalse($filters->shiny->value);
        $this->assertFalse($filters->premium->value);
    }
}
