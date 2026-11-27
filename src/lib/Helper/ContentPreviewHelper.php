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
use Symfony\Contracts\Service\ResetInterface;

class ContentPreviewHelper implements SiteAccessAware, ResetInterface
{
    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var SiteAccessRouterInterface */
    protected $siteAccessRouter;

    /** @var SiteAccess */
    protected $originalSiteAccess;

    /**
     * The shared SiteAccess object, updated in place for every request. $originalSiteAccess is replaced
     * with a snapshot of it while a preview is handled.
     */
    private ?SiteAccess $siteAccess = null;

    /** @var bool */
    private $previewActive = false;

    /** @var Content|null */
    private $previewedContent;

    /** @var Location|null */
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
        $this->siteAccess = $siteAccess;
        if ($siteAccess !== null) {
            $this->originalSiteAccess = $siteAccess;
        }
    }

    /**
     * Makes the next request see its own SiteAccess and no preview left active by a previous one.
     */
    public function reset(): void
    {
        if ($this->siteAccess !== null) {
            $this->originalSiteAccess = $this->siteAccess;
        }
        $this->previewActive = false;
        $this->previewedContent = null;
        $this->previewedLocation = null;
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
     * @return Content|null
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
     * @return Location|null
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

class_alias(ContentPreviewHelper::class, 'eZ\Publish\Core\Helper\ContentPreviewHelper');
