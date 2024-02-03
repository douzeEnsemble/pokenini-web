<?php

declare(strict_types=1);

namespace App\Tests\Unit\Utils;

use App\Twig\AppExtension;
use PHPUnit\Framework\TestCase;

class AppExtensionTest extends TestCase
{
    public function testKsort(): void
    {
        $extension = new AppExtension();

        $data = [
            'b' => 1,
            'a' => 2,
            'c' => 3,
        ];

        $this->assertEquals([
            'a' => 2,
            'b' => 1,
            'c' => 3,
        ], $extension->appKsort($data));
    }
}
