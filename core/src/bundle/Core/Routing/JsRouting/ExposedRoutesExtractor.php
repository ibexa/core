<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\Routing\JsRouting;

use FOS\JsRoutingBundle\Extractor\ExposedRoutesExtractorInterface;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Decorator of FOSJsRouting routes extractor.
 * Ensures that base URL contains the SiteAccess part when applicable.
 *
 * @internal
 */
class ExposedRoutesExtractor implements ExposedRoutesExtractorInterface
{
    private ExposedRoutesExtractorInterface $innerExtractor;

    private RequestStack $requestStack;

    public function __construct(ExposedRoutesExtractorInterface $innerExtractor, RequestStack $requestStack)
    {
        $this->innerExtractor = $innerExtractor;
        $this->requestStack = $requestStack;
    }

    public function getRoutes(): RouteCollection
    {
        return $this->innerExtractor->getRoutes();
    }

    /**
     * Will add the SiteAccess if configured in the URI.
     */
    public function getBaseUrl(): string
    {
        $baseUrl = $this->innerExtractor->getBaseUrl();
        $request = $this->requestStack->getMainRequest();
        if ($request === null) {
            return $baseUrl;
        }

        $siteAccess = $request->attributes->get('siteaccess');
        if ($siteAccess instanceof SiteAccess && $siteAccess->matcher instanceof SiteAccess\URILexer) {
            $baseUrl .= $siteAccess->matcher->analyseLink('');
        }

        return $baseUrl;
    }

    public function getPrefix(string $locale): string
    {
        return $this->innerExtractor->getPrefix($locale);
    }

    public function getHost(): string
    {
        return $this->innerExtractor->getHost();
    }

    public function getScheme(): string
    {
        return $this->innerExtractor->getScheme();
    }

    public function getCachePath($locale): string
    {
        return $this->innerExtractor->getCachePath($locale);
    }

    public function getResources(): array
    {
        return $this->innerExtractor->getResources();
    }

    public function getPort(): string
    {
        return $this->innerExtractor->getPort() ?? '';
    }

    public function isRouteExposed(Route $route, string $name): bool
    {
        return $this->innerExtractor->isRouteExposed($route, $name);
    }
}
