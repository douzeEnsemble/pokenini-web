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
        $extension = new AppExtension('/srv');

        $functions = $extension->getFunctions();

        $this->assertIsArray($functions);

        foreach ($functions as $function) {
            $this->assertInstanceOf(TwigFunction::class, $function);
        }
    }

    public function testIsBannerFileExists(): void
    {
        $extension = new AppExtension('/srv');

        $this->assertTrue($extension->isBannerFileExists('default'));
        $this->assertFalse($extension->isBannerFileExists('thespoon'));
    }

    public function testGetDexBanner(): void
    {
        $extension = new AppExtension('/srv');

        $this->assertEquals('/img/dex/banner/default.png', $extension->getDexBanner('default'));
        $this->assertEquals('/img/dex/banner/home.png', $extension->getDexBanner('home'));
        $this->assertEquals('/img/dex/banner/default.png', $extension->getDexBanner('thespoon'));
    }
}
