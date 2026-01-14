<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class TrustedHeaderClientIpEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', PHP_INT_MAX],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if ($this->isPlatformShProxy($request) && $request->headers->get('Client-Cdn') === 'fastly') {
            Request::setTrustedProxies(['REMOTE_ADDR'], Request::getTrustedHeaderSet());
        }
    }

    private function isPlatformShProxy(Request $request): bool
    {
        return null !== $request->server->get('PLATFORM_RELATIONSHIPS');
    }
}
