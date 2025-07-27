<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\MVC\Symfony\Routing;

use Ibexa\Core\MVC\Symfony\SiteAccess;
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessAware;
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessRouterInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

/**
 * Base class for Ibexa Url generation.
 */
abstract class Generator implements SiteAccessAware
{
    protected RequestContext $requestContext;

    protected SiteAccessRouterInterface $siteAccessRouter;

    protected ?SiteAccess $siteAccess;

    protected ?LoggerInterface $logger;

    public function setRequestContext(RequestContext $requestContext): void
    {
        $this->requestContext = $requestContext;
    }

    public function setSiteAccessRouter(SiteAccessRouterInterface $siteAccessRouter): void
    {
        $this->siteAccessRouter = $siteAccessRouter;
    }

    public function setSiteAccess(?SiteAccess $siteAccess = null): void
    {
        $this->siteAccess = $siteAccess;
    }

    public function setLogger(?LoggerInterface $logger = null): void
    {
        $this->logger = $logger;
    }

    /**
     * Triggers URL generation for $urlResource and $parameters.
     *
     * @param mixed $urlResource Type can be anything, depending on the context. It's up to the router to pass the appropriate value to the implementor.
     * @param array<string, mixed> $parameters An arbitrary hash of parameters to generate a link.
     *                          SiteAccess name can be provided as 'siteaccess' to generate a link to it (cross siteaccess link).
     * @param int $referenceType The type of reference to be generated (one of the constants)
     */
    public function generate(mixed $urlResource, array $parameters, int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string
    {
        $siteAccess = $this->siteAccess;
        $requestContext = $this->requestContext;

        // Retrieving the appropriate SiteAccess to generate the link for.
        if (isset($parameters['siteaccess'])) {
            $siteAccess = $this->siteAccessRouter->matchByName($parameters['siteaccess']);
            if ($siteAccess instanceof SiteAccess && $siteAccess->matcher instanceof SiteAccess\VersatileMatcher) {
                // inline-instantiated on purpose, as it's lightweight and difficult to inject into DefaultRouter which also uses it
                $requestContext = (new RequestContextFactory($this->requestContext))->getContextBySimplifiedRequest(
                    $siteAccess->matcher->getRequest()
                );
            } elseif (isset($this->logger)) {
                $siteAccess = $this->siteAccess;
                $this->logger->notice("Could not generate a link using provided 'siteaccess' parameter: {$parameters['siteaccess']}. Generating using current context.");
                unset($parameters['siteaccess']);
            }
        }

        $url = $this->doGenerate($urlResource, $parameters);

        // Add the SiteAccess URI back if needed.
        if (null !== $siteAccess && $siteAccess->matcher instanceof SiteAccess\URILexer) {
            $url = $siteAccess->matcher->analyseLink($url);
        }

        $url = $requestContext->getBaseUrl() . $url;

        if ($referenceType === UrlGeneratorInterface::ABSOLUTE_URL) {
            $url = $this->generateAbsoluteUrl($url, $requestContext);
        }

        return $url;
    }

    /**
     * Generates the URL from $urlResource and $parameters.
     *
     * @param array<string, mixed> $parameters
     */
    abstract public function doGenerate(mixed $urlResource, array $parameters): string;

    protected function generateAbsoluteUrl(string $uri, RequestContext $requestContext): string
    {
        $scheme = $requestContext->getScheme();
        $port = '';
        if ($scheme === 'http' && $requestContext->getHttpPort() !== 80) {
            $port = ':' . $requestContext->getHttpPort();
        } elseif ($scheme === 'https' && $requestContext->getHttpsPort() !== 443) {
            $port = ':' . $requestContext->getHttpsPort();
        }

        return $scheme . '://' . $requestContext->getHost() . $port . $uri;
    }
}
