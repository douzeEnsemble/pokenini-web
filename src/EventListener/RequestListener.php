<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class RequestListener
{
    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if ($event->getRequest()->query->has('lang')) {
            $this->requestStack->getSession()->set(
                'app.lang',
                $event->getRequest()->query->get('lang')
            );
        }
    }
}
