<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\MVC\Symfony\Routing;

use Symfony\Component\HttpFoundation\ParameterBag;

class RouteReference
{
    private ParameterBag $params;

    /** @var mixed Route name or resource (e.g. Location object). */
    private $route;

    public function __construct($route, array $params = [])
    {
        $this->route = $route;
        $this->params = new ParameterBag($params);
    }

    /**
     * @param mixed $route
     */
    public function setRoute($route): void
    {
        $this->route = $route;
    }

    /**
     * @return mixed
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * @return array
     */
    public function getParams(): array
    {
        return $this->params->all();
    }

    /**
     * Sets a route parameter.
     *
     * @param string $parameterName
     * @param mixed $value
     */
    public function set(string $parameterName, $value): void
    {
        $this->params->set($parameterName, $value);
    }

    /**
     * Returns a route parameter.
     *
     * @param string $parameterName
     * @param mixed $defaultValue
     * @param bool $deep
     *
     * @return mixed
     */
    public function get(string $parameterName, $defaultValue = null, $deep = false): mixed
    {
        return $this->params->get($parameterName, $defaultValue, $deep);
    }

    public function has(string $parameterName): bool
    {
        return $this->params->has($parameterName);
    }

    /**
     * Removes a route parameter.
     *
     * @param string $parameterName
     */
    public function remove(string $parameterName): void
    {
        $this->params->remove($parameterName);
    }
}
