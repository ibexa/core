<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\MVC\Symfony\EventListener;

use Ibexa\Core\MVC\Symfony\Event\PostSiteAccessMatchEvent;
use Ibexa\Core\MVC\Symfony\MVCEvents;
use Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Ibexa\Core\MVC\Symfony\SiteAccess\Router as SiteAccessRouter;
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessServiceInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * kernel.request listener, triggers SiteAccess matching.
 * Should be triggered as early as possible.
 */
class SiteAccessMatchListener implements EventSubscriberInterface
{
    protected SiteAccess\Router $siteAccessRouter;

    protected EventDispatcherInterface $eventDispatcher;

    private SerializerInterface $serializer;

    private SiteAccessServiceInterface $siteAccessService;

    public function __construct(
        SiteAccessRouter $siteAccessRouter,
        EventDispatcherInterface $eventDispatcher,
        SerializerInterface $serializer,
        SiteAccessServiceInterface $siteAccessService
    ) {
        $this->siteAccessRouter = $siteAccessRouter;
        $this->eventDispatcher = $eventDispatcher;
        $this->serializer = $serializer;
        $this->siteAccessService = $siteAccessService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Should take place just after FragmentListener (priority 48) in order to get rebuilt request attributes in case of subrequest
            KernelEvents::REQUEST => ['onKernelRequest', 45],
        ];
    }

    /**
     * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // We have a serialized siteaccess object from a fragment (sub-request), we need to get it back.
        if ($request->attributes->has('serialized_siteaccess')) {
            /** @var \Ibexa\Core\MVC\Symfony\SiteAccess $siteAccess */
            $siteAccess = $this->serializer->deserialize(
                $request->attributes->get('serialized_siteaccess'),
                SiteAccess::class,
                'json',
                [
                    'serialized_siteaccess_matcher' => $request->attributes->get('serialized_siteaccess_matcher'),
                    'serialized_siteaccess_sub_matchers' => $request->attributes->get('serialized_siteaccess_sub_matchers'),
                ]
            );
            $request->attributes->set(
                'siteaccess',
                $siteAccess
            );
            $request->attributes->remove('serialized_siteaccess');
        } elseif (!$request->attributes->has('siteaccess')) {
            // Get SiteAccess from original request if present ("_ez_original_request" attribute), or current request otherwise.
            // "_ez_original_request" attribute is present in the case of user context hash generation (aka "user hash request").
            $request->attributes->set(
                'siteaccess',
                $this->getSiteAccessFromRequest($request->attributes->get('_ez_original_request', $request))
            );
        }

        $siteaccess = $request->attributes->get('siteaccess');
        if ($siteaccess instanceof SiteAccess) {
            $siteAccessEvent = new PostSiteAccessMatchEvent($siteaccess, $request, $event->getRequestType());
            $this->eventDispatcher->dispatch($siteAccessEvent, MVCEvents::SITEACCESS);

            // SiteAccessService establishes the base of its stack by reacting to this same event
            // for MAIN_REQUEST (see SiteAccessService::onSiteAccessMatch()); a sub-request's
            // SiteAccess is a nested change on top of that, so push it the same way any other
            // caller would, via changeSiteAccess(). SiteAccessRestoreListener pops it back off via
            // restoreSiteAccess() once this sub-request finishes.
            if ($event->getRequestType() === HttpKernelInterface::SUB_REQUEST) {
                $this->siteAccessService->changeSiteAccess($siteaccess);
            }
        }
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Ibexa\Core\MVC\Symfony\SiteAccess
     */
    private function getSiteAccessFromRequest(Request $request)
    {
        return $this->siteAccessRouter->match(
            new SimplifiedRequest(
                $request->getScheme(),
                $request->getHost(),
                $request->getPort(),
                $request->getPathInfo(),
                $request->query->all(),
                $request->getLanguages(),
                $request->headers->all()
            )
        );
    }
}
