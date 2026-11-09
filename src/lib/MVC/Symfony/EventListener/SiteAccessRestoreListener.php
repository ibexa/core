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
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessServiceInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Restores the parent request's SiteAccess when a sub-request finishes:
 * - for SiteAccessService, by calling restoreSiteAccess(), popping the entry
 *   SiteAccessMatchListener pushed via changeSiteAccess() when this sub-request started.
 * - for not-yet-migrated listeners still reacting to MVCEvents::SITEACCESS directly (mutating
 *   shared state for every sub-request), by re-dispatching that event. Mirrors
 *   {@see RouterListener::onKernelFinishRequest()}.
 */
final class SiteAccessRestoreListener implements EventSubscriberInterface
{
    private RequestStack $requestStack;

    private EventDispatcherInterface $eventDispatcher;

    private SiteAccessServiceInterface $siteAccessService;

    public function __construct(
        RequestStack $requestStack,
        EventDispatcherInterface $eventDispatcher,
        SiteAccessServiceInterface $siteAccessService
    ) {
        $this->requestStack = $requestStack;
        $this->eventDispatcher = $eventDispatcher;
        $this->siteAccessService = $siteAccessService;
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

        // Only undo what SiteAccessMatchListener pushed for this exact sub-request. A sub-request
        // can carry a SiteAccess without one being pushed, e.g. when a MVCEvents::SITEACCESS
        // listener threw, and popping then would drop the parent request's SiteAccess instead.
        $attributes = $event->getRequest()->attributes;
        if ($attributes->getBoolean(SiteAccessMatchListener::SITEACCESS_PUSHED_ATTRIBUTE)) {
            $attributes->remove(SiteAccessMatchListener::SITEACCESS_PUSHED_ATTRIBUTE);
            $this->siteAccessService->restoreSiteAccess();
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
