<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\DependencyInjection\Configuration\ConfigResolver;

use Ibexa\Core\MVC\Exception\ParameterNotFoundException;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Ibexa\Core\MVC\Symfony\SiteAccess\Provider\StaticSiteAccessProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @internal
 */
class StaticSiteAccessConfigResolver extends SiteAccessConfigResolver
{
    protected ContainerInterface $container;

    public function __construct(
        ContainerInterface $container,
        SiteAccess\SiteAccessProviderInterface $siteAccessProvider,
        string $defaultNamespace
    ) {
        parent::__construct($siteAccessProvider, $defaultNamespace);

        $this->container = $container;
    }

    protected function resolverHasParameter(
        SiteAccess $siteAccess,
        string $paramName,
        string $namespace
    ): bool {
        return $this->container->hasParameter(
            $this->resolveScopeRelativeParamName($paramName, $namespace, $siteAccess->name)
        );
    }

    protected function getParameterFromResolver(
        SiteAccess $siteAccess,
        string $paramName,
        string $namespace
    ) {
        $scopeRelativeParamName = $this->getScopeRelativeParamName($paramName, $namespace, $siteAccess->name);
        if ($this->container->hasParameter($scopeRelativeParamName)) {
            return $this->container->getParameter($scopeRelativeParamName);
        }

        throw new ParameterNotFoundException($paramName, $namespace, [$siteAccess->name]);
    }

    protected function isSiteAccessSupported(SiteAccess $siteAccess): bool
    {
        return StaticSiteAccessProvider::class === $siteAccess->provider;
    }
}
