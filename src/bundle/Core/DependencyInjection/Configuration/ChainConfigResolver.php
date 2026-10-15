<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\DependencyInjection\Configuration;

use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\MVC\Exception\ParameterNotFoundException;

class ChainConfigResolver implements ConfigResolverInterface
{
    /** @var ConfigResolverInterface[] */
    protected $resolvers = [];

    /** @var ConfigResolverInterface[] */
    protected $sortedResolvers;

    /**
     * Registers $mapper as a valid mapper to be used in the configuration mapping chain.
     * When this mapper will be called in the chain depends on $priority. The highest $priority is, the earliest the router will be called.
     *
     * @param ConfigResolverInterface $resolver
     * @param int $priority
     */
    public function addResolver(
        ConfigResolverInterface $resolver,
        $priority = 0
    ) {
        $priority = (int)$priority;
        if (!isset($this->resolvers[$priority])) {
            $this->resolvers[$priority] = [];
        }

        $this->resolvers[$priority][] = $resolver;
        $this->sortedResolvers = [];
    }

    /**
     * @return ConfigResolverInterface[]
     */
    public function getAllResolvers()
    {
        if (empty($this->sortedResolvers)) {
            $this->sortedResolvers = $this->sortResolvers();
        }

        return $this->sortedResolvers;
    }

    /**
     * Sort the registered mappers by priority.
     * The highest priority number is the highest priority (reverse sorting).
     *
     * @return ConfigResolverInterface[]
     */
    protected function sortResolvers()
    {
        $sortedResolvers = [];
        krsort($this->resolvers);

        foreach ($this->resolvers as $resolvers) {
            $sortedResolvers = array_merge($sortedResolvers, $resolvers);
        }

        return $sortedResolvers;
    }

    /**
     * @return mixed
     *
     * @throws ParameterNotFoundException
     */
    public function getParameter(
        string $paramName,
        ?string $namespace = null,
        ?string $scope = null
    ) {
        foreach ($this->getAllResolvers() as $resolver) {
            try {
                return $resolver->getParameter($paramName, $namespace, $scope);
            } catch (ParameterNotFoundException $e) {
                // Do nothing, just let the next resolver handle it
            }
        }

        // Finally throw a ParameterNotFoundException since the chain resolver couldn't find any valid resolver for demanded parameter
        throw new ParameterNotFoundException($paramName, $namespace, [$scope]);
    }

    public function hasParameter(
        string $paramName,
        ?string $namespace = null,
        ?string $scope = null
    ): bool {
        foreach ($this->getAllResolvers() as $resolver) {
            $hasParameter = $resolver->hasParameter($paramName, $namespace, $scope);
            if ($hasParameter) {
                return true;
            }
        }

        return false;
    }

    public function setDefaultNamespace(string $defaultNamespace): void
    {
        foreach ($this->getAllResolvers() as $resolver) {
            $resolver->setDefaultNamespace($defaultNamespace);
        }
    }

    /**
     * Not supported.
     *
     * @throws \LogicException
     */
    public function getDefaultNamespace(): string
    {
        throw new \LogicException('getDefaultNamespace() is not supported by the ChainConfigResolver');
    }
}
