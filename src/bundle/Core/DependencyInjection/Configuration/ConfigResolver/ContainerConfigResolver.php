<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\DependencyInjection\Configuration\ConfigResolver;

use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\MVC\Exception\ParameterNotFoundException;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class ContainerConfigResolver implements ConfigResolverInterface
{
    protected ContainerInterface $container;

    /** @var string */
    private $scope;

    /** @var string */
    private $defaultNamespace;

    public function __construct(ContainerInterface $container, string $scope, string $defaultNamespace)
    {
        $this->container = $container;
        $this->scope = $scope;
        $this->defaultNamespace = $defaultNamespace;
    }

    public function getParameter(string $paramName, ?string $namespace = null, ?string $scope = null)
    {
        [$namespace, $scope] = $this->resolveNamespaceAndScope($namespace, $scope);
        $scopeRelativeParamName = $this->getScopeRelativeParamName($paramName, $namespace, $scope);
        if ($this->container->hasParameter($scopeRelativeParamName)) {
            return $this->container->getParameter($scopeRelativeParamName);
        }

        throw new ParameterNotFoundException($paramName, $namespace, [$scope]);
    }

    public function hasParameter(string $paramName, ?string $namespace = null, ?string $scope = null): bool
    {
        return $this->container->hasParameter($this->resolveScopeRelativeParamName($paramName, $namespace, $scope));
    }

    public function getDefaultNamespace(): string
    {
        return $this->defaultNamespace;
    }

    public function setDefaultNamespace(string $defaultNamespace): void
    {
        $this->defaultNamespace = $defaultNamespace;
    }

    private function resolveScopeRelativeParamName(string $paramName, string $namespace = null, string $scope = null): string
    {
        return $this->getScopeRelativeParamName($paramName, ...$this->resolveNamespaceAndScope($namespace, $scope));
    }

    private function resolveNamespaceAndScope(string $namespace = null, string $scope = null): array
    {
        return [$namespace ?: $this->getDefaultNamespace(), $scope ?? $this->scope];
    }

    private function getScopeRelativeParamName(string $paramName, string $namespace, string $scope): string
    {
        return "$namespace.$scope.$paramName";
    }
}
