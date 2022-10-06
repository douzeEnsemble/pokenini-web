<?php

declare(strict_types=1);

namespace App\Tests\Unit\Twig;

use App\Twig\AppExtension;
use PHPUnit\Framework\TestCase;
use Twig\TwigFunction;

class AppExtensionTest extends TestCase
{
    public function testGetFunctions(): void
    {
        $extension = new AppExtension('/srv/app');

        $functions = $extension->getFunctions();

        $this->assertIsArray($functions);

        foreach ($functions as $function) {
            $this->assertInstanceOf(TwigFunction::class, $function);
        }
    }

    public function testFileExists(): void
    {
        $extension = new AppExtension('/srv/app');

        $this->assertTrue($extension->bannerFileExists('default'));
        $this->assertFalse($extension->bannerFileExists('thespoon'));
    }
}
