<?php

declare(strict_types=1);

namespace App\Tests\Unit\Twig;

use App\Security\User;
use App\Security\UserTokenService;
use App\Twig\AppExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Security;
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

    public function testIsBannerFileExists(): void
    {
        $extension = new AppExtension('/srv/app');

        $this->assertTrue($extension->isBannerFileExists('default'));
        $this->assertFalse($extension->isBannerFileExists('thespoon'));
    }

    public function testGetDexBanner(): void
    {
        $extension = new AppExtension('/srv/app');

        $this->assertEquals('/img/dex/banner/default.png', $extension->getDexBanner('default'));
        $this->assertEquals('/img/dex/banner/home.png', $extension->getDexBanner('home'));
        $this->assertEquals('/img/dex/banner/default.png', $extension->getDexBanner('thespoon'));
    }
}
