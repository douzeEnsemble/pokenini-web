<?php

declare(strict_types=1);

namespace App\Tests\Unit\DTO;

use App\DTO\LastRoute;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(LastRoute::class)]
class LastRouteTest extends TestCase
{
    public function testConstruct(): void
    {
        $lastRoute = new LastRoute('douze', ['un' => '1', 'deux' => '2']);

        $this->assertEquals('douze', $lastRoute->route);
        $this->assertEquals(['un' => '1', 'deux' => '2'], $lastRoute->routeParams);
    }
}
