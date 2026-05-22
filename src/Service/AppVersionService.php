<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class AppVersionService
{
    public function __construct(
        private readonly string $projectDir,
        #[Autowire(service: 'cache.app_version')]
        private readonly CacheInterface $cache,
        private readonly Filesystem $filesystem,
    ) {}

    public function getVersion(string $filename = 'version'): string
    {
        return $this->cache->get('app_version_'.$filename, function (ItemInterface $item) use ($filename): string {
            unset($item);

            $filePath = $this->projectDir.'/resources/metadata/'.$filename;

            return $this->filesystem->exists($filePath) ? $this->filesystem->readFile($filePath) : '0.0.toto';
        });
    }
}
