<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Helper;

use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessRouterInterface;
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessServiceInterface;

class ContentPreviewHelper
{
    /** @var \Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessRouterInterface */
    protected $siteAccessRouter;

    private SiteAccessServiceInterface $siteAccessService;

    /** @var bool */
    private $previewActive = false;

    /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Content */
    private $previewedContent;

    /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Location */
    private $previewedLocation;

    public function __construct(SiteAccessRouterInterface $siteAccessRouter, SiteAccessServiceInterface $siteAccessService)
    {
        $this->siteAccessRouter = $siteAccessRouter;
        $this->siteAccessService = $siteAccessService;
    }

    /**
     * Return original SiteAccess.
     *
     * @return \Ibexa\Core\MVC\Symfony\SiteAccess|null
     */
    public function getOriginalSiteAccess()
    {
        return $this->siteAccessService->getCurrent();
    }

    /**
     * Switches configuration scope to $siteAccessName and returns the new SiteAccess to use for preview.
     *
     * @param string $siteAccessName
     *
     * @return \Ibexa\Core\MVC\Symfony\SiteAccess
     */
    public function changeConfigScope($siteAccessName)
    {
        return $this->siteAccessService->changeSiteAccess($this->siteAccessRouter->matchByName($siteAccessName));
    }

    /**
     * Restores original config scope.
     *
     * @return \Ibexa\Core\MVC\Symfony\SiteAccess|null
     */
    public function restoreConfigScope()
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
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content
     */
    public function getPreviewedContent()
    {
        return $this->previewedContent;
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content $previewedContent
     */
    public function setPreviewedContent(Content $previewedContent)
    {
        $this->previewedContent = $previewedContent;
    }

    /**
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Location
     */
    public function getPreviewedLocation()
    {
        return $this->previewedLocation;
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Location $previewedLocation
     */
    public function setPreviewedLocation(Location $previewedLocation)
    {
        $this->previewedLocation = $previewedLocation;
    }
}
