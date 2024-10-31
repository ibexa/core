<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\MVC\Symfony\Routing;

use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\URLWildcardService;
use Ibexa\Core\MVC\Symfony\Routing\Generator\UrlAliasGenerator;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Cmf\Component\Routing\ChainedRouterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

class UrlWildcardRouter implements ChainedRouterInterface, RequestMatcherInterface
{
    public const string URL_ALIAS_ROUTE_NAME = 'ibexa.url.alias';

    private URLWildcardService $wildcardService;

    private UrlAliasGenerator $generator;

    private RequestContext $requestContext;

    private LoggerInterface $logger;

    public function __construct(
        URLWildcardService $wildcardService,
        UrlAliasGenerator $generator,
        RequestContext $requestContext
    ) {
        $this->wildcardService = $wildcardService;
        $this->generator = $generator;
        $this->requestContext = $requestContext;
        $this->logger = new NullLogger();
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @return array<string, mixed> An array of parameters
     *
     * @throws \Symfony\Component\Routing\Exception\ResourceNotFoundException
     */
    public function matchRequest(Request $request): array
    {
        $requestedPath = $request->attributes->get('semanticPathinfo', $request->getPathInfo());

        try {
            $urlWildcardTranslationResult = $this->wildcardService->translate($requestedPath);
        } catch (NotFoundException $e) {
            throw new ResourceNotFoundException($e->getMessage(), $e->getCode(), $e);
        }

        $this->logger->info("UrlWildcard matched. Destination URL: $urlWildcardTranslationResult->uri");

        // set translated path for the next router
        $request->attributes->set('semanticPathinfo', $urlWildcardTranslationResult->uri);
        $request->attributes->set('needsRedirect', (bool) $urlWildcardTranslationResult->forward);

        // and throw Exception to pass processing to the next router
        throw new ResourceNotFoundException();
    }

    /**
     * @return \Symfony\Component\Routing\RouteCollection
     */
    public function getRouteCollection(): RouteCollection
    {
        return new RouteCollection();
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function generate(string $name, array $parameters = [], int $referenceType = self::ABSOLUTE_PATH): string
    {
        throw new RouteNotFoundException('Could not match route');
    }

    public function setContext(RequestContext $context): void
    {
        $this->requestContext = $context;
        $this->generator->setRequestContext($context);
    }

    public function getContext(): RequestContext
    {
        return $this->requestContext;
    }

    /**
     * Not supported. Please use matchRequest() instead.
     *
     * @return array<string, mixed>
     *
     * @throws \RuntimeException
     */
    public function match(string $pathinfo): array
    {
        throw new \RuntimeException("The UrlWildcardRouter doesn't support the match() method. Use matchRequest() instead.");
    }

    /**
     * Whether the router supports the thing in $name to generate a route.
     *
     * This check does not need to look if the specific instance can be
     * resolved to a route, only whether the router can generate routes from
     * objects of this class.
     */
    public function supports(string $name): bool
    {
        return $name === static::URL_ALIAS_ROUTE_NAME;
    }

    public function getRouteDebugMessage(string $name, array $parameters = []): string
    {
        return $name;
    }
}
