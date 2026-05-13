<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Event\AdminActionSucceededEvent;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[AsEventListener]
final class LabelsCacheInvalidationListener
{
    public function __construct(
        #[Autowire(service: 'cache.labels')]
        private readonly TagAwareCacheInterface $labelsCache,
    ) {}

    public function __invoke(AdminActionSucceededEvent $event): void
    {
        if ('labels' === $event->name) {
            $this->labelsCache->invalidateTags(['labels']);
        }
    }
}
