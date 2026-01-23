<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Helper;

use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Core\MVC\Symfony\Event\ScopeChangeEvent;
use Ibexa\Core\MVC\Symfony\MVCEvents;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessAware;
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessRouterInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ContentPreviewHelper implements SiteAccessAware
{
    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var SiteAccessRouterInterface */
    protected $siteAccessRouter;

    /** @var SiteAccess */
    protected $originalSiteAccess;

    /** @var bool */
    private $previewActive = false;

    /** @var Content */
    private $previewedContent;

    /** @var Location */
    private $previewedLocation;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        SiteAccessRouterInterface $siteAccessRouter
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->siteAccessRouter = $siteAccessRouter;
    }

    public function setSiteAccess(?SiteAccess $siteAccess = null)
    {
        $this->originalSiteAccess = $siteAccess;
    }

    /**
     * Return original SiteAccess.
     *
     * @return SiteAccess
     */
    public function getOriginalSiteAccess()
    {
        return $this->originalSiteAccess;
    }

    /**
     * Switches configuration scope to $siteAccessName and returns the new SiteAccess to use for preview.
     *
     * @param string $siteAccessName
     *
     * @return SiteAccess
     */
    public function changeConfigScope($siteAccessName)
    {
        $event = new ScopeChangeEvent($this->siteAccessRouter->matchByName($siteAccessName));
        $this->eventDispatcher->dispatch($event, MVCEvents::CONFIG_SCOPE_CHANGE);

        return $event->getSiteAccess();
    }

    /**
     * Restores original config scope.
     *
     * @return SiteAccess
     */
    public function restoreConfigScope()
    {
        $event = new ScopeChangeEvent($this->originalSiteAccess);
        $this->eventDispatcher->dispatch($event, MVCEvents::CONFIG_SCOPE_RESTORE);

        return $event->getSiteAccess();
    }

    /**
     * @return bool
     */
    public function isPreviewActive()
    {
        return $this->previewActive;
    }

    /**
     * @param bool $previewActive
     */
    public function setPreviewActive($previewActive)
    {
        $this->previewActive = (bool)$previewActive;
        $this->originalSiteAccess = clone $this->originalSiteAccess;
    }

    /**
     * @return Content
     */
    public function getPreviewedContent()
    {
        return $this->previewedContent;
    }

    /**
     * @param Content $previewedContent
     */
    public function setPreviewedContent(Content $previewedContent)
    {
        $this->previewedContent = $previewedContent;
    }

    /**
     * @return Location
     */
    public function getPreviewedLocation()
    {
        return $this->previewedLocation;
    }

    /**
     * @param Location $previewedLocation
     */
    public function setPreviewedLocation(Location $previewedLocation)
    {
        $this->previewedLocation = $previewedLocation;
    }
}
