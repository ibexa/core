<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\MVC\Symfony\EventListener;

use Ibexa\Core\MVC\Symfony\Event\PostSiteAccessMatchEvent;
use Ibexa\Core\MVC\Symfony\MVCEvents;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Restores the parent request's SiteAccess when a sub-request finishes, by re-dispatching
 * MVCEvents::SITEACCESS — its listeners mutate shared state for every sub-request. Mirrors
 * {@see \Symfony\Component\HttpKernel\EventListener\RouterListener::onKernelFinishRequest()}.
 */
final class SiteAccessRestoreListener implements EventSubscriberInterface
{
    private RequestStack $requestStack;

    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        RequestStack $requestStack,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->requestStack = $requestStack;
        $this->eventDispatcher = $eventDispatcher;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::FINISH_REQUEST => ['onKernelFinishRequest', 0],
        ];
    }

    public function onKernelFinishRequest(FinishRequestEvent $event): void
    {
        if ($event->isMainRequest()) {
            return;
        }

        // The finishing request is still on the stack, so this is the request control returns to
        $parentRequest = $this->requestStack->getParentRequest();
        if ($parentRequest === null) {
            return;
        }

        $siteAccess = $parentRequest->attributes->get('siteaccess');
        if (!$siteAccess instanceof SiteAccess) {
            return;
        }

        $this->eventDispatcher->dispatch(
            new PostSiteAccessMatchEvent($siteAccess, $parentRequest, HttpKernelInterface::SUB_REQUEST),
            MVCEvents::SITEACCESS
        );
    }
}
