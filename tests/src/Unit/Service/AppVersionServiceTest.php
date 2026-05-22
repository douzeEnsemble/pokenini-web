<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\AppVersionService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @internal
 */
#[CoversClass(AppVersionService::class)]
final class AppVersionServiceTest extends TestCase
{
    public function testGetVersionReturnsFileContent(): void
    {
        $service = new AppVersionService(dirname(__DIR__, 4), new ArrayAdapter(), new Filesystem());

        $this->assertSame('1.2.12', $service->getVersion());
    }

    public function testGetVersionReturnsFallbackForMissingFile(): void
    {
        $service = new AppVersionService(dirname(__DIR__, 4), new ArrayAdapter(), new Filesystem());

        $this->assertSame('0.0.toto', $service->getVersion('non_existent_file'));
    }

    public function testGetVersionIsCached(): void
    {
        $tmpDir = sys_get_temp_dir().'/pokenini_version_test_'.uniqid();
        mkdir($tmpDir.'/resources/metadata', 0o755, true);
        file_put_contents($tmpDir.'/resources/metadata/version', '1.0.0');

        $service = new AppVersionService($tmpDir, new ArrayAdapter(), new Filesystem());

        $this->assertSame('1.0.0', $service->getVersion());

        file_put_contents($tmpDir.'/resources/metadata/version', '2.0.0');

        $this->assertSame('1.0.0', $service->getVersion());

        unlink($tmpDir.'/resources/metadata/version');
        rmdir($tmpDir.'/resources/metadata');
        rmdir($tmpDir.'/resources');
        rmdir($tmpDir);
    }

    public function testGetVersionCachesPerFilename(): void
    {
        $tmpDir = sys_get_temp_dir().'/pokenini_version_test_'.uniqid();
        mkdir($tmpDir.'/resources/metadata', 0o755, true);
        file_put_contents($tmpDir.'/resources/metadata/version', '1.0.0');
        file_put_contents($tmpDir.'/resources/metadata/changelog', '2024-01-01');

        $service = new AppVersionService($tmpDir, new ArrayAdapter(), new Filesystem());

        $this->assertSame('1.0.0', $service->getVersion('version'));
        $this->assertSame('2024-01-01', $service->getVersion('changelog'));

        unlink($tmpDir.'/resources/metadata/version');
        unlink($tmpDir.'/resources/metadata/changelog');
        rmdir($tmpDir.'/resources/metadata');
        rmdir($tmpDir.'/resources');
        rmdir($tmpDir);
    }

    public function testGetVersionUsesCorrectCacheKey(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache
            ->expects($this->once())
            ->method('get')
            ->with('app_version_version', $this->isCallable())
            ->willReturn('1.0.0')
        ;

        $service = new AppVersionService('/tmp', $cache, new Filesystem());

        $this->assertSame('1.0.0', $service->getVersion());
    }

    public function testGetVersionIncludesFilenameInCacheKey(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache
            ->expects($this->once())
            ->method('get')
            ->with('app_version_changelog', $this->isCallable())
            ->willReturn('2024-01-01')
        ;

        $service = new AppVersionService('/tmp', $cache, new Filesystem());

        $this->assertSame('2024-01-01', $service->getVersion('changelog'));
    }

    public function testGetVersionCallbackReadsFile(): void
    {
        $item = $this->createStub(ItemInterface::class);

        $cache = $this->createStub(CacheInterface::class);
        $cache
            ->method('get')
            ->willReturnCallback(function (string $key, callable $callback) use ($item): string {
                unset($key);

                /** @var string */
                return $callback($item);
            })
        ;

        $service = new AppVersionService(dirname(__DIR__, 4), $cache, new Filesystem());

        $this->assertSame('1.2.12', $service->getVersion());
    }
}
