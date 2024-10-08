<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\DependencyInjection\Configuration\ConfigResolver;

use Ibexa\Core\MVC\Exception\ParameterNotFoundException;
use Ibexa\Core\MVC\Symfony\Configuration\VersatileScopeInterface;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessAware;

abstract class SiteAccessConfigResolver implements VersatileScopeInterface, SiteAccessAware
{
    /** @var \Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessProviderInterface */
    protected $siteAccessProvider;

    /** @var \Ibexa\Core\MVC\Symfony\SiteAccess */
    protected $currentSiteAccess;

    /** @var string */
    protected $defaultScope;

    /** @var string */
    protected $defaultNamespace;

    public function __construct(
        SiteAccess\SiteAccessProviderInterface $siteAccessProvider,
        string $defaultNamespace
    ) {
        $this->siteAccessProvider = $siteAccessProvider;
        $this->defaultNamespace = $defaultNamespace;
    }

    public function hasParameter(string $paramName, ?string $namespace = null, ?string $scope = null): bool
    {
        [$namespace, $scope] = $this->resolveNamespaceAndScope($namespace, $scope);
        if (!$this->isSiteAccessScope($scope)) {
            return false;
        }

        $siteAccess = $this->siteAccessProvider->getSiteAccess($scope);
        if (!$this->isSiteAccessSupported($siteAccess)) {
            return false;
        }

        return $this->resolverHasParameter($siteAccess, $paramName, $namespace);
    }

    public function getParameter(string $paramName, ?string $namespace = null, ?string $scope = null)
    {
        [$namespace, $scope] = $this->resolveNamespaceAndScope($namespace, $scope);

        if (!$this->isSiteAccessScope($scope)) {
            throw new ParameterNotFoundException($paramName, $namespace, [$scope]);
        }

        $siteAccess = $this->siteAccessProvider->getSiteAccess($scope);
        if (!$this->isSiteAccessSupported($siteAccess)) {
            throw new ParameterNotFoundException($paramName, $namespace, [$scope]);
        }

        return $this->getParameterFromResolver($siteAccess, $paramName, $namespace);
    }

    public function getDefaultNamespace(): string
    {
        return $this->defaultNamespace;
    }

    public function setDefaultNamespace($defaultNamespace): void
    {
        $this->defaultNamespace = $defaultNamespace;
    }

    public function getDefaultScope(): string
    {
        return $this->defaultScope ?: $this->currentSiteAccess->name;
    }

    public function setDefaultScope(string $scope): void
    {
        $this->defaultScope = $scope;
    }

    public function setSiteAccess(SiteAccess $siteAccess = null): void
    {
        $this->currentSiteAccess = $siteAccess;
    }

    protected function isSiteAccessScope(string $scope): bool
    {
        return $this->siteAccessProvider->isDefined($scope);
    }

    /**
     * Returns true if current config provider supports given Site Access.
     */
    protected function isSiteAccessSupported(SiteAccess $siteAccess): bool
    {
        return true;
    }

    protected function resolveScopeRelativeParamName(string $paramName, ?string $namespace = null, ?string $scope = null): string
    {
        return $this->getScopeRelativeParamName($paramName, ...$this->resolveNamespaceAndScope($namespace, $scope));
    }

    protected function resolveNamespaceAndScope(?string $namespace = null, ?string $scope = null): array
    {
        return [$namespace ?: $this->getDefaultNamespace(), $scope ?: $this->getDefaultScope()];
    }

    protected function getScopeRelativeParamName(string $paramName, string $namespace, string $scope): string
    {
        return "$namespace.$scope.$paramName";
    }

    abstract protected function resolverHasParameter(SiteAccess $siteAccess, string $paramName, string $namespace): bool;

    abstract protected function getParameterFromResolver(SiteAccess $siteAccess, string $paramName, string $namespace);
}
