<?php

declare(strict_types=1);

namespace App\Tests\Unit\Twig;

use App\Service\AppVersionService;
use App\Twig\AppExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Twig\Node\Node;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * @internal
 */
#[CoversClass(AppExtension::class)]
final class AppExtensionTest extends TestCase
{
    public function testGetFilters(): void
    {
        $extension = new AppExtension($this->createStub(AppVersionService::class));

        $filters = $extension->getFilters();

        $this->assertCount(3, $filters);

        $ksortFilter = $filters[0];

        $this->assertInstanceOf(TwigFilter::class, $ksortFilter);
        $this->assertEquals('ksort', $ksortFilter->getName());
        $this->assertEquals([], $ksortFilter->getSafe(new Node()));

        /** @var mixed[] $ksortFilterCallable */
        $ksortFilterCallable = $ksortFilter->getCallable();
        $this->assertCount(2, $ksortFilterCallable);
        $this->assertInstanceOf(AppExtension::class, $ksortFilterCallable[0]);
        $this->assertEquals('ksort', $ksortFilterCallable[1]);

        $sha1Filter = $filters[1];

        $this->assertInstanceOf(TwigFilter::class, $sha1Filter);
        $this->assertEquals('sha1', $sha1Filter->getName());
        $this->assertEquals([], $sha1Filter->getSafe(new Node()));

        /** @var mixed[] $sha1FilterCallable */
        $sha1FilterCallable = $sha1Filter->getCallable();
        $this->assertCount(2, $sha1FilterCallable);
        $this->assertInstanceOf(AppExtension::class, $sha1FilterCallable[0]);
        $this->assertEquals('sha1', $sha1FilterCallable[1]);

        $htmlNl2brFilter = $filters[2];

        $this->assertInstanceOf(TwigFilter::class, $htmlNl2brFilter);
        $this->assertEquals('htmlNl2br', $htmlNl2brFilter->getName());
        $this->assertEquals(['html'], $htmlNl2brFilter->getSafe(new Node()));

        /** @var mixed[] $htmlNl2brCallable */
        $htmlNl2brCallable = $htmlNl2brFilter->getCallable();
        $this->assertCount(2, $htmlNl2brCallable);
        $this->assertInstanceOf(AppExtension::class, $htmlNl2brCallable[0]);
        $this->assertEquals('htmlNl2br', $htmlNl2brCallable[1]);
    }

    public function testGetFunctions(): void
    {
        $extension = new AppExtension($this->createStub(AppVersionService::class));

        $functions = $extension->getFunctions();

        $this->assertCount(1, $functions);

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
        $extension = new AppExtension($this->createStub(AppVersionService::class));

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
        $extension = new AppExtension($this->createStub(AppVersionService::class));

        $this->assertSame('0b9c2625dc21ef05f6ad4ddf47c5f203837aa32c', $extension->sha1('toto'));
        $this->assertSame('f7e79ca8eb0b31ee4d5d6c181416667ffee528ed', $extension->sha1('titi'));
    }

    public function testHtmlNl2br(): void
    {
        $extension = new AppExtension($this->createStub(AppVersionService::class));

        $this->assertSame("a<br>\nb", $extension->htmlNl2br("a\nb"));
    }

    public function testVersion(): void
    {
        $versionService = $this->createMock(AppVersionService::class);
        $versionService
            ->expects($this->once())
            ->method('getVersion')
            ->with('version')
            ->willReturn('1.2.12')
        ;

        $extension = new AppExtension($versionService);

        $this->assertSame('1.2.12', $extension->getVersion());
    }

    public function testVersionDelegatesToService(): void
    {
        $versionService = $this->createMock(AppVersionService::class);
        $versionService
            ->expects($this->once())
            ->method('getVersion')
            ->with('changelog')
            ->willReturn('some-value')
        ;

        $extension = new AppExtension($versionService);

        $this->assertSame('some-value', $extension->getVersion('changelog'));
    }
}
