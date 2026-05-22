<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class AppVersionService
{
    public function __construct(
        private readonly string $projectDir,
        #[Autowire(service: 'cache.app_version')]
        private readonly CacheInterface $cache,
    ) {}

    public function getVersion(string $filename = 'version'): string
    {
        return $this->cache->get('app_version_'.$filename, function (ItemInterface $item) use ($filename): string {
            unset($item);

            $filePath = $this->projectDir.'/resources/metadata/'.$filename;

            return is_file($filePath) ? (string) file_get_contents($filePath) : '0.0.toto';
        });
    }
}
