<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Helper;

use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessRouterInterface;
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessServiceInterface;

class ContentPreviewHelper
{
    /** @var SiteAccessRouterInterface */
    protected $siteAccessRouter;

    private SiteAccessServiceInterface $siteAccessService;

    /** @var bool */
    private $previewActive = false;

    /** @var Content */
    private $previewedContent;

    /** @var Location */
    private $previewedLocation;

    public function __construct(
        SiteAccessRouterInterface $siteAccessRouter,
        SiteAccessServiceInterface $siteAccessService
    ) {
        $this->siteAccessRouter = $siteAccessRouter;
        $this->siteAccessService = $siteAccessService;
    }

    /**
     * Return original SiteAccess.
     */
    public function getOriginalSiteAccess(): ?SiteAccess
    {
        return $this->siteAccessService->getCurrent();
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
        return $this->siteAccessService->changeSiteAccess($this->siteAccessRouter->matchByName($siteAccessName));
    }

    /**
     * Restores original config scope.
     */
    public function restoreConfigScope(): ?SiteAccess
    {
        return $this->siteAccessService->restoreSiteAccess();
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
