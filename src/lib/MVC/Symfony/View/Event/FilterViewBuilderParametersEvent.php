<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\MVC\Symfony\View\Event;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * An event that collects the parameters the ViewBuilder will be provided to build View objects.
 */
class FilterViewBuilderParametersEvent extends Event
{
    /** @var Request */
    private $request;

    /**
     * Parameters the ViewBuilder will use.
     *
     * @var ParameterBag
     */
    private $parameters;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->parameters = new ParameterBag();
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Returns the ParameterBag that holds the ViewBuilder's parameters.
     *
     * @return ParameterBag
     */
    public function getParameters()
    {
        return $this->parameters;
    }
}
