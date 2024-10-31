<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\Routing;

use Ibexa\Contracts\Core\Repository\Values\Content\URLAlias;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\MVC\Symfony\Routing\UrlAliasRouter as BaseUrlAliasRouter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class UrlAliasRouter extends BaseUrlAliasRouter
{
    protected ConfigResolverInterface $configResolver;

    public function setConfigResolver(ConfigResolverInterface $configResolver): void
    {
        $this->configResolver = $configResolver;
    }

    /**
     * @return array<string, mixed> an array of parameters
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function matchRequest(Request $request): array
    {
        // UrlAliasRouter might be disabled from configuration.
        // An example is for running the admin interface: it needs to be entirely run through the legacy kernel.
        if ($this->configResolver->getParameter('url_alias_router') === false) {
            throw new ResourceNotFoundException('Config requires bypassing UrlAliasRouter');
        }

        return parent::matchRequest($request);
    }

    /**
     * Will return the right UrlAlias with respect to configured root location.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    protected function getUrlAlias(string $pathInfo): URLAlias
    {
        $pathPrefix = $this->rootLocationId !== null
            ? $this->generator->getPathPrefixByRootLocationId($this->rootLocationId)
            : '/';

        if (
            $this->rootLocationId === null ||
            $pathPrefix === '/' ||
            $this->generator->isUriPrefixExcluded($pathInfo)
        ) {
            return parent::getUrlAlias($pathInfo);
        }

        return $this->urlAliasService->lookup($pathPrefix . $pathInfo);
    }
}
