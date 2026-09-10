<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Routing;

use Ibexa\Core\MVC\Symfony\Routing\RequestContextFactory;
use Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessAware;
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessRouterInterface;
use Ibexa\Core\MVC\Symfony\SiteAccess\URILexer;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\CacheWarmer\WarmableInterface;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

/**
 * SiteAccess-aware decorator of the Symfony router.
 *
 * Matching honours the `semanticPathinfo` request attribute set by the SiteAccess matcher, and link generation
 * prepends the SiteAccess URI part (for URI-based SiteAccess matchers) and supports the `siteaccess` route parameter.
 */
final class DefaultRouter implements RouterInterface, RequestMatcherInterface, WarmableInterface, SiteAccessAware
{
    private ?SiteAccess $siteAccess = null;

    /**
     * @param string[] $nonSiteAccessAwareRoutes route name prefixes that are not supposed to be SiteAccess aware,
     *        i.e. routes pointing to asset generation
     */
    public function __construct(
        private readonly RouterInterface&RequestMatcherInterface $innerRouter,
        private readonly SiteAccessRouterInterface $siteAccessRouter,
        private readonly array $nonSiteAccessAwareRoutes = [],
        private readonly ?LoggerInterface $logger = null
    ) {
    }

    public function setSiteAccess(?SiteAccess $siteAccess = null): void
    {
        $this->siteAccess = $siteAccess;
    }

    public function getInnerRouter(): RouterInterface&RequestMatcherInterface
    {
        return $this->innerRouter;
    }

    public function setContext(RequestContext $context): void
    {
        $this->innerRouter->setContext($context);
    }

    public function getContext(): RequestContext
    {
        return $this->innerRouter->getContext();
    }

    public function getRouteCollection(): RouteCollection
    {
        return $this->innerRouter->getRouteCollection();
    }

    /**
     * @return array<string, mixed>
     */
    public function match(string $pathinfo): array
    {
        return $this->innerRouter->match($pathinfo);
    }

    /**
     * @return array<string, mixed>
     */
    public function matchRequest(Request $request): array
    {
        if ($request->attributes->has('semanticPathinfo')) {
            $request = $request->duplicate();
            $request->server->set(
                'REQUEST_URI',
                $request->attributes->get('semanticPathinfo')
            );
        }

        return $this->innerRouter->matchRequest($request);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function generate(string $name, array $parameters = [], int $referenceType = self::ABSOLUTE_PATH): string
    {
        $siteAccess = $this->siteAccess;
        $originalContext = $context = $this->getContext();
        $isSiteAccessAware = $this->isSiteAccessAwareRoute($name);

        // Retrieving the appropriate SiteAccess to generate the link for.
        if (isset($parameters['siteaccess']) && $isSiteAccessAware) {
            $siteAccess = $this->siteAccessRouter->matchByName($parameters['siteaccess']);
            if ($siteAccess instanceof SiteAccess && $siteAccess->matcher instanceof SiteAccess\VersatileMatcher) {
                // Switch request context for link generation.
                $context = $this->getContextBySimplifiedRequest($siteAccess->matcher->getRequest());
                $this->setContext($context);
            } else {
                $siteAccess = $this->siteAccess;
                $this->logger?->notice("Could not generate a link using provided 'siteaccess' parameter: {$parameters['siteaccess']}. Generating using current context.");
            }

            unset($parameters['siteaccess']);
        }

        try {
            $url = $this->innerRouter->generate($name, $parameters, $referenceType);

            // Now putting back SiteAccess URI if needed.
            if ($isSiteAccessAware && $siteAccess !== null && $siteAccess->matcher instanceof URILexer) {
                $url = $this->prependSiteAccessUri($url, $context, $referenceType, $siteAccess->matcher);
            }

            return $url;
        } finally {
            // Switch back to original context, for next links generation, including when generation fails.
            $this->setContext($originalContext);
        }
    }

    private function prependSiteAccessUri(string $url, RequestContext $context, int $referenceType, URILexer $matcher): string
    {
        if ($referenceType === self::ABSOLUTE_URL || $referenceType === self::NETWORK_PATH) {
            $scheme = $context->getScheme();
            $port = '';
            if ($scheme === 'http' && $context->getHttpPort() !== 80) {
                $port = ':' . $context->getHttpPort();
            } elseif ($scheme === 'https' && $context->getHttpsPort() !== 443) {
                $port = ':' . $context->getHttpsPort();
            }

            $base = $context->getHost() . $port . $context->getBaseUrl();
        } else {
            $base = $context->getBaseUrl();
        }

        $linkUri = $base ? substr($url, strpos($url, $base) + strlen($base)) : $url;

        return str_replace($linkUri, $matcher->analyseLink($linkUri), $url);
    }

    /**
     * @return string[]
     */
    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        if ($this->innerRouter instanceof WarmableInterface) {
            return $this->innerRouter->warmUp($cacheDir, $buildDir);
        }

        return [];
    }

    /**
     * Merges context from $simplifiedRequest into a clone of the current context.
     */
    private function getContextBySimplifiedRequest(SimplifiedRequest $simplifiedRequest): RequestContext
    {
        // Instantiated per call on purpose: the factory clones the current context and mutates that clone,
        // so it is per-call state and cannot be a shared service.
        return (new RequestContextFactory($this->getContext()))->getContextBySimplifiedRequest($simplifiedRequest);
    }

    /**
     * Checks if $routeName is a siteAccess aware route, and thus needs to have siteAccess URI prepended.
     * Will be used for link generation, only in the case of URI SiteAccess matching.
     */
    private function isSiteAccessAwareRoute(string $routeName): bool
    {
        foreach ($this->nonSiteAccessAwareRoutes as $ignoredPrefix) {
            if (str_starts_with($routeName, $ignoredPrefix)) {
                return false;
            }
        }

        return true;
    }
}
