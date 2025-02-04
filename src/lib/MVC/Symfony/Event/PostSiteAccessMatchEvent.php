<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\MVC\Symfony\Event;

use Ibexa\Core\MVC\Symfony\SiteAccess;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event is triggered after SiteAccess matching process and allows further control on it and the associated request.
 */
class PostSiteAccessMatchEvent extends Event
{
    /** @var \Ibexa\Core\MVC\Symfony\SiteAccess */
    private $siteAccess;

    /** @var \Symfony\Component\HttpFoundation\Request */
    private $request;

    /**
     * The request type the kernel is currently processing.  One of
     * HttpKernelInterface::MASTER_REQUEST and HttpKernelInterface::SUB_REQUEST.
     *
     * @var int
     */
    private $requestType;

    public function __construct(SiteAccess $siteAccess, Request $request, $requestType)
    {
        $this->siteAccess = $siteAccess;
        $this->request = $request;
        $this->requestType = $requestType;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Returns matched SiteAccess instance.
     *
     * @return \Ibexa\Core\MVC\Symfony\SiteAccess
     */
    public function getSiteAccess()
    {
        return $this->siteAccess;
    }

    /**
     * Returns the request type the kernel is currently processing.
     *
     * @return int  One of HttpKernelInterface::MASTER_REQUEST and
     *                  HttpKernelInterface::SUB_REQUEST
     */
    public function getRequestType()
    {
        return $this->requestType;
    }
}
