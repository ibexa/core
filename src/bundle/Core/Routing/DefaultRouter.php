<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\Routing;

use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\MVC\Symfony\Routing\RequestContextFactory;
use Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessAware;
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessRouterInterface;
use Ibexa\Core\MVC\Symfony\SiteAccess\URILexer;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RequestContext;

/**
 * Extension of Symfony default router implementing RequestMatcherInterface.
 */
class DefaultRouter extends Router implements SiteAccessAware
{
    protected ?SiteAccess $siteAccess = null;

    /** @var string[] */
    protected array $nonSiteAccessAwareRoutes = [];

    protected ConfigResolverInterface $configResolver;

    protected SiteAccessRouterInterface $siteAccessRouter;

    public function setConfigResolver(ConfigResolverInterface $configResolver): void
    {
        $this->configResolver = $configResolver;
    }

    public function setSiteAccess(?SiteAccess $siteAccess = null): void
    {
        $this->siteAccess = $siteAccess;
    }

    /**
     * Injects route names that are not supposed to be SiteAccess aware.
     * i.e. Routes pointing to asset generation (like assetic).
     *
     * @param string[] $routes
     */
    public function setNonSiteAccessAwareRoutes(array $routes): void
    {
        $this->nonSiteAccessAwareRoutes = $routes;
    }

    public function setSiteAccessRouter(SiteAccessRouterInterface $siteAccessRouter): void
    {
        $this->siteAccessRouter = $siteAccessRouter;
    }

    /**
     * @return array<string, mixed> An array of parameters
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

        return parent::matchRequest($request);
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
            } elseif ($this->logger) {
                $siteAccess = $this->siteAccess;
                $this->logger->notice("Could not generate a link using provided 'siteaccess' parameter: {$parameters['siteaccess']}. Generating using current context.");
            }

            unset($parameters['siteaccess']);
        }

        try {
            $url = parent::generate($name, $parameters, $referenceType);
        } catch (RouteNotFoundException $e) {
            // Switch back to original context, for next links generation.
            $this->setContext($originalContext);
            throw $e;
        }

        // Now putting back SiteAccess URI if needed.
        if ($isSiteAccessAware && $siteAccess && $siteAccess->matcher instanceof URILexer) {
            if ($referenceType === self::ABSOLUTE_URL || $referenceType === self::NETWORK_PATH) {
                $scheme = $context->getScheme();
                $port = '';
                if ($scheme === 'http' && $this->context->getHttpPort() !== 80) {
                    $port = ':' . $this->context->getHttpPort();
                } elseif ($scheme === 'https' && $this->context->getHttpsPort() !== 443) {
                    $port = ':' . $this->context->getHttpsPort();
                }

                $base = $context->getHost() . $port . $context->getBaseUrl();
            } else {
                $base = $context->getBaseUrl();
            }

            $linkUri = $base ? substr($url, strpos($url, $base) + strlen($base)) : $url;
            $url = str_replace($linkUri, $siteAccess->matcher->analyseLink($linkUri), $url);
        }

        // Switch back to original context, for next links generation.
        $this->setContext($originalContext);

        return $url;
    }

    /**
     * Checks if $routeName is a siteAccess aware route, and thus needs to have siteAccess URI prepended.
     * Will be used for link generation, only in the case of URI SiteAccess matching.
     */
    protected function isSiteAccessAwareRoute(string $routeName): bool
    {
        foreach ($this->nonSiteAccessAwareRoutes as $ignoredPrefix) {
            if (str_starts_with($routeName, $ignoredPrefix)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Merges context from $simplifiedRequest into a clone of the current context.
     */
    public function getContextBySimplifiedRequest(SimplifiedRequest $simplifiedRequest): RequestContext
    {
        // inline-instantiated on purpose as it's lightweight and injecting it here through DI can be complicated
        return (new RequestContextFactory($this->context))->getContextBySimplifiedRequest($simplifiedRequest);
    }
}
