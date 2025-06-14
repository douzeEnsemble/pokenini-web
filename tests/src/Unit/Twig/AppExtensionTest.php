<?php

declare(strict_types=1);

namespace App\Tests\Unit\Twig;

use App\Twig\AppExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * @internal
 */
#[CoversClass(AppExtension::class)]
class AppExtensionTest extends TestCase
{
    public function testGetFilters(): void
    {
        $extension = new AppExtension();

        $filters = $extension->getFilters();

        $this->assertCount(2, $filters);

        /** @var TwigFilter $ksortFilter */
        $ksortFilter = $filters[0];

        $this->assertInstanceOf(TwigFilter::class, $ksortFilter);
        $this->assertEquals('ksort', $ksortFilter->getName());

        /** @var mixed[] $ksortFilterCallable */
        $ksortFilterCallable = $ksortFilter->getCallable();
        $this->assertCount(2, $ksortFilterCallable);
        $this->assertInstanceOf(AppExtension::class, $ksortFilterCallable[0]);
        $this->assertEquals('ksort', $ksortFilterCallable[1]);

        /** @var TwigFilter $sha1Filter */
        $sha1Filter = $filters[1];

        $this->assertInstanceOf(TwigFilter::class, $sha1Filter);
        $this->assertEquals('sha1', $sha1Filter->getName());

        /** @var mixed[] $sha1FilterCallable */
        $sha1FilterCallable = $sha1Filter->getCallable();
        $this->assertCount(2, $sha1FilterCallable);
        $this->assertInstanceOf(AppExtension::class, $sha1FilterCallable[0]);
        $this->assertEquals('sha1', $sha1FilterCallable[1]);
    }

    public function testGetFunctions(): void
    {
        $extension = new AppExtension();

        $functions = $extension->getFunctions();

        $this->assertCount(1, $functions);

        /** @var TwigFunction $versionFunction */
        $versionFunction = $functions[0];

        $this->assertInstanceOf(TwigFunction::class, $versionFunction);
        $this->assertEquals('version', $versionFunction->getName());

        /** @var mixed[] $versionFunctionCallable */
        $versionFunctionCallable = $versionFunction->getCallable();
        $this->assertCount(2, $versionFunctionCallable);
        $this->assertInstanceOf(AppExtension::class, $versionFunctionCallable[0]);
        $this->assertEquals('getVersion', $versionFunctionCallable[1]);
    }

    public function testKsort(): void
    {
        $extension = new AppExtension();

        $data = [
            'b' => 1,
            'a' => 2,
            'c' => 3,
        ];

        $this->assertSame(
            [
                'a' => 2,
                'b' => 1,
                'c' => 3,
            ],
            $extension->ksort($data)
        );
    }

    public function testSha1(): void
    {
        $extension = new AppExtension();

        $this->assertSame('0b9c2625dc21ef05f6ad4ddf47c5f203837aa32c', $extension->sha1('toto'));
        $this->assertSame('f7e79ca8eb0b31ee4d5d6c181416667ffee528ed', $extension->sha1('titi'));
    }

    public function testVersion(): void
    {
        $extension = new AppExtension();

        $this->assertSame(
            '0.0.toto',
            $extension->getVersion('non_existent_file'),
        );

        $this->assertSame(
            '1.2.12',
            $extension->getVersion(),
        );
    }
}
