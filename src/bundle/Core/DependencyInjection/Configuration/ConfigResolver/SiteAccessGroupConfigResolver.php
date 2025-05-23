<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\DependencyInjection\Configuration\ConfigResolver;

use Ibexa\Core\MVC\Exception\ParameterNotFoundException;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Ibexa\Core\MVC\Symfony\SiteAccessGroup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @internal
 */
class SiteAccessGroupConfigResolver extends SiteAccessConfigResolver
{
    protected ContainerInterface $container;

    /** @var string[][] */
    protected $siteAccessGroups;

    public function __construct(
        ContainerInterface $container,
        SiteAccess\SiteAccessProviderInterface $siteAccessProvider,
        string $defaultNamespace,
        array $siteAccessGroups
    ) {
        parent::__construct($siteAccessProvider, $defaultNamespace);

        $this->container = $container;
        $this->siteAccessGroups = $siteAccessGroups;
    }

    final public function hasParameter(string $paramName, ?string $namespace = null, ?string $scope = null): bool
    {
        [$namespace, $scope] = $this->resolveNamespaceAndScope($namespace, $scope);

        if ($this->isSiteAccessGroupScope($scope)) {
            return $this->resolverHasParameterForGroup(new SiteAccessGroup($scope), $paramName, $namespace);
        }

        return parent::hasParameter($paramName, $namespace, $scope);
    }

    final public function getParameter(string $paramName, ?string $namespace = null, ?string $scope = null)
    {
        [$namespace, $scope] = $this->resolveNamespaceAndScope($namespace, $scope);

        if ($this->isSiteAccessGroupScope($scope)) {
            return $this->getParameterFromResolverForGroup(new SiteAccessGroup($scope), $paramName, $namespace);
        }

        return parent::getParameter($paramName, $namespace, $scope);
    }

    protected function resolverHasParameter(SiteAccess $siteAccess, string $paramName, string $namespace): bool
    {
        foreach ($siteAccess->groups as $group) {
            $groupScopeParamName = $this->resolveScopeRelativeParamName($paramName, $namespace, $group->getName());
            if ($this->container->hasParameter($groupScopeParamName)) {
                return true;
            }
        }

        return false;
    }

    protected function resolverHasParameterForGroup(SiteAccessGroup $siteAccessGroup, string $paramName, string $namespace): bool
    {
        $groupScopeParamName = $this->resolveScopeRelativeParamName($paramName, $namespace, $siteAccessGroup->getName());

        return $this->container->hasParameter($groupScopeParamName);
    }

    protected function getParameterFromResolver(SiteAccess $siteAccess, string $paramName, string $namespace)
    {
        $triedScopes = [];

        foreach ($siteAccess->groups as $group) {
            $groupScopeParamName = $this->resolveScopeRelativeParamName($paramName, $namespace, $group->getName());
            if ($this->container->hasParameter($groupScopeParamName)) {
                return $this->container->getParameter($groupScopeParamName);
            }

            $triedScopes[] = $group->getName();
        }

        throw new ParameterNotFoundException($paramName, $namespace, $triedScopes);
    }

    protected function getParameterFromResolverForGroup(SiteAccessGroup $siteAccessGroup, string $paramName, string $namespace)
    {
        $groupScopeParamName = $this->resolveScopeRelativeParamName($paramName, $namespace, $siteAccessGroup->getName());

        if (!$this->container->hasParameter($groupScopeParamName)) {
            throw new ParameterNotFoundException($paramName, $namespace, [$siteAccessGroup]);
        }

        return $this->container->getParameter($groupScopeParamName);
    }

    private function isSiteAccessGroupScope($scope): bool
    {
        return array_key_exists($scope, $this->siteAccessGroups);
    }
}
