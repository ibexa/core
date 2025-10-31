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
    private ?string $trustedHeaderName;

    public function __construct(
        ?string $trustedHeaderName
    ) {
        $this->trustedHeaderName = $trustedHeaderName;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', PHP_INT_MAX],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        $trustedProxies = Request::getTrustedProxies();
        $trustedHeaderSet = Request::getTrustedHeaderSet();

        $trustedHeaderName = $this->trustedHeaderName;

        if (null === $trustedHeaderName) {
            return;
        }

        $trustedClientIp = $request->headers->get($trustedHeaderName);

        if (null !== $trustedClientIp) {
            if ($trustedHeaderSet !== -1) {
                $trustedHeaderSet |= Request::HEADER_X_FORWARDED_FOR;
            }
            $request->headers->set('X_FORWARDED_FOR', $trustedClientIp);
        }

        Request::setTrustedProxies($trustedProxies, $trustedHeaderSet);
    }
}
