<?php

declare(strict_types=1);

namespace App\EventListener;

use App\DTO\LastRoute;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class ResponseListener
{
    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            // don't do anything if it's not the main request
            return;
        }

        // Only if in firewall, so no debug/profiler routes
        if ($event->getRequest()->get('_security_firewall_run')) {
            /** @var string $routeName */
            $routeName = $event->getRequest()->get('_route', '');
            /** @var string[] $routeParams */
            $routeParams = $event->getRequest()->get('_route_params', []);

            $this->requestStack->getSession()->set(
                'last_route',
                new LastRoute(
                    $routeName,
                    $routeParams
                )
            );
        }
    }
}
