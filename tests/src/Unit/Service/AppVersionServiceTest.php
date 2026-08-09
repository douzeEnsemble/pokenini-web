<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\AppVersionService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

require_once __DIR__.'/AppVersionServiceFilemtimeStub.php';

/**
 * @internal
 */
#[CoversClass(AppVersionService::class)]
final class AppVersionServiceTest extends TestCase
{
    public static bool $forceFilemtimeFailure = false;
    public static int $filemtimeCallCount = 0;

    #[\Override]
    protected function tearDown(): void
    {
        self::$forceFilemtimeFailure = false;
        self::$filemtimeCallCount = 0;
    }

    #[Test]
    public function getVersionReturnsFileContent(): void
    {
        file_put_contents(self::getDir().'/resources/metadata/version', '1.2.12');

        $service = new AppVersionService(
            self::getDir(),
            new ArrayAdapter(),
            new Filesystem(),
        );

        $this->assertSame('1.2.12', $service->getVersion());
    }

    #[Test]
    public function getVersionReturnsFallbackForMissingFile(): void
    {
        $service = new AppVersionService(
            self::getDir(),
            new ArrayAdapter(),
            new Filesystem(),
        );

        $this->assertSame('0.0.toto', $service->getVersion('non_existent_file'));
    }

    #[Test]
    public function getVersionIsCached(): void
    {
        $tmpDir = sys_get_temp_dir().'/pokenini_version_test_'.uniqid();
        mkdir($tmpDir.'/resources/metadata', 0o755, true);
        file_put_contents($tmpDir.'/resources/metadata/version', '1.0.0');

        $service = new AppVersionService($tmpDir, new ArrayAdapter(), new Filesystem());

        $this->assertSame('1.0.0', $service->getVersion());
        $this->assertSame('1.0.0', $service->getVersion()); // same mtime → cache hit

        unlink($tmpDir.'/resources/metadata/version');
        rmdir($tmpDir.'/resources/metadata');
        rmdir($tmpDir.'/resources');
        rmdir($tmpDir);
    }

    #[Test]
    public function getVersionInvalidatedOnFileChange(): void
    {
        $tmpDir = sys_get_temp_dir().'/pokenini_version_test_'.uniqid();
        mkdir($tmpDir.'/resources/metadata', 0o755, true);
        file_put_contents($tmpDir.'/resources/metadata/version', '1.0.0');

        $service = new AppVersionService($tmpDir, new ArrayAdapter(), new Filesystem());

        $this->assertSame('1.0.0', $service->getVersion());

        // Simulate deployment: new content + bumped mtime
        file_put_contents($tmpDir.'/resources/metadata/version', '2.0.0');
        touch($tmpDir.'/resources/metadata/version', time() + 1);

        $this->assertSame('2.0.0', $service->getVersion());

        unlink($tmpDir.'/resources/metadata/version');
        rmdir($tmpDir.'/resources/metadata');
        rmdir($tmpDir.'/resources');
        rmdir($tmpDir);
    }

    #[Test]
    public function getVersionCachesPerFilename(): void
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

    #[Test]
    public function getVersionUsesCorrectCacheKey(): void
    {
        $tmpDir = sys_get_temp_dir().'/pokenini_version_test_'.uniqid();
        mkdir($tmpDir.'/resources/metadata', 0o755, true);
        file_put_contents($tmpDir.'/resources/metadata/version', '1.0.0');
        $mtime = filemtime($tmpDir.'/resources/metadata/version');

        $cache = $this->createMock(CacheInterface::class);
        $cache
            ->expects($this->once())
            ->method('get')
            ->with('app_version_version_'.(false !== $mtime ? $mtime : ''), $this->isCallable())
            ->willReturn('1.0.0')
        ;

        $service = new AppVersionService($tmpDir, $cache, new Filesystem());

        $this->assertSame('1.0.0', $service->getVersion());

        unlink($tmpDir.'/resources/metadata/version');
        rmdir($tmpDir.'/resources/metadata');
        rmdir($tmpDir.'/resources');
        rmdir($tmpDir);
    }

    #[Test]
    public function getVersionIncludesFilenameInCacheKey(): void
    {
        $tmpDir = sys_get_temp_dir().'/pokenini_version_test_'.uniqid();
        mkdir($tmpDir.'/resources/metadata', 0o755, true);
        file_put_contents($tmpDir.'/resources/metadata/changelog', '2024-01-01');
        $mtime = filemtime($tmpDir.'/resources/metadata/changelog');

        $cache = $this->createMock(CacheInterface::class);
        $cache
            ->expects($this->once())
            ->method('get')
            ->with('app_version_changelog_'.(false !== $mtime ? $mtime : ''), $this->isCallable())
            ->willReturn('2024-01-01')
        ;

        $service = new AppVersionService($tmpDir, $cache, new Filesystem());

        $this->assertSame('2024-01-01', $service->getVersion('changelog'));

        unlink($tmpDir.'/resources/metadata/changelog');
        rmdir($tmpDir.'/resources/metadata');
        rmdir($tmpDir.'/resources');
        rmdir($tmpDir);
    }

    #[Test]
    public function getVersionCallbackReadsFile(): void
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

        $service = new AppVersionService(self::getDir(), $cache, new Filesystem());

        $this->assertSame('1.2.12', $service->getVersion());
    }

    #[Test]
    public function getUpdatedAtReturnsFileMtime(): void
    {
        file_put_contents(self::getDir().'/resources/metadata/version', '1.2.12');
        $expectedMtime = filemtime(self::getDir().'/resources/metadata/version');

        $service = new AppVersionService(
            self::getDir(),
            new ArrayAdapter(),
            new Filesystem(),
        );

        $this->assertSame('1.2.12', $service->getVersion());
        $this->assertSame($expectedMtime, $service->getUpdatedAt()?->getTimestamp());
    }

    #[Test]
    public function getUpdatedAtReturnsNullForMissingFile(): void
    {
        $service = new AppVersionService(self::getDir(), new ArrayAdapter(), new Filesystem());

        $this->assertNull($service->getUpdatedAt('non_existent_file'));
        $this->assertSame(0, self::$filemtimeCallCount, 'getUpdatedAt() must return early on a missing file, without calling filemtime().');
    }

    #[Test]
    public function getUpdatedAtReturnsNullWhenFilemtimeFails(): void
    {
        file_put_contents(self::getDir().'/resources/metadata/version', '1.2.12');

        self::$forceFilemtimeFailure = true;

        $service = new AppVersionService(self::getDir(), new ArrayAdapter(), new Filesystem());

        $this->assertNull($service->getUpdatedAt());
    }

    private static function getDir(): string
    {
        return dirname(__DIR__, 4);
    }
}
