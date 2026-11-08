<?php

declare(strict_types=1);

namespace App\Tests\Unit\Twig;

use App\Service\LocaleService;
use App\Twig\LocaleExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * @internal
 */
#[CoversClass(LocaleExtension::class)]
final class LocaleExtensionTest extends TestCase
{
    #[Test]
    public function getFunctions(): void
    {
        $service = $this->createStub(LocaleService::class);

        $functions = new LocaleExtension($service)->getFunctions();

        $this->assertCount(1, $functions);
        $this->assertInstanceOf(TwigFunction::class, $functions[0]);
        $this->assertSame('enabled_locales', $functions[0]->getName());
        $this->assertSame([$service, 'locales'], $functions[0]->getCallable());
    }

    #[Test]
    public function getFilters(): void
    {
        $service = $this->createStub(LocaleService::class);

        $filters = new LocaleExtension($service)->getFilters();

        $this->assertCount(3, $filters);

        $this->assertInstanceOf(TwigFilter::class, $filters[0]);
        $this->assertSame('localized', $filters[0]->getName());
        $this->assertSame([$service, 'localize'], $filters[0]->getCallable());

        $this->assertInstanceOf(TwigFilter::class, $filters[1]);
        $this->assertSame('names_by_locale', $filters[1]->getName());
        $this->assertSame([$service, 'ordered'], $filters[1]->getCallable());

        $this->assertInstanceOf(TwigFilter::class, $filters[2]);
        $this->assertSame('localized_list', $filters[2]->getName());
        $this->assertSame([$service, 'localizeList'], $filters[2]->getCallable());
    }
}
