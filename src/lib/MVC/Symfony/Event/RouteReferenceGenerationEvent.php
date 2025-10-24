<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\MVC\Symfony\Event;

use Ibexa\Core\MVC\Symfony\Routing\RouteReference;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched when generating a RouteReference.
 */
class RouteReferenceGenerationEvent extends Event
{
    /** @var RouteReference */
    private $routeReference;

    /** @var Request */
    private $request;

    public function __construct(
        RouteReference $routeReference,
        Request $request
    ) {
        $this->routeReference = $routeReference;
        $this->request = $request;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return RouteReference
     */
    public function getRouteReference()
    {
        return $this->routeReference;
    }

    /**
     * @param RouteReference $routeReference
     */
    public function setRouteReference($routeReference)
    {
        $this->routeReference = $routeReference;
    }
}
