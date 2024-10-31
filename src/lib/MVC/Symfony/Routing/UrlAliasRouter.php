<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\MVC\Symfony\Routing;

use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\URLAliasService;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Contracts\Core\Repository\Values\Content\URLAlias;
use Ibexa\Core\MVC\Symfony\Routing\Generator\UrlAliasGenerator;
use Ibexa\Core\MVC\Symfony\View\ViewManagerInterface;
use InvalidArgumentException;
use LogicException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Symfony\Cmf\Component\Routing\ChainedRouterInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

class UrlAliasRouter implements ChainedRouterInterface, RequestMatcherInterface
{
    public const string URL_ALIAS_ROUTE_NAME = 'ibexa.url.alias';

    public const string VIEW_ACTION = 'ibexa_content::viewAction';

    protected RequestContext $requestContext;

    protected LocationService $locationService;

    protected URLAliasService $urlAliasService;

    protected ContentService $contentService;

    protected UrlAliasGenerator $generator;

    protected ?int $rootLocationId;

    protected LoggerInterface $logger;

    public function __construct(
        LocationService $locationService,
        URLAliasService $urlAliasService,
        ContentService $contentService,
        UrlAliasGenerator $generator,
        ?RequestContext $requestContext = null,
        ?LoggerInterface $logger = null
    ) {
        $this->locationService = $locationService;
        $this->urlAliasService = $urlAliasService;
        $this->contentService = $contentService;
        $this->generator = $generator;
        $this->requestContext = $requestContext ?? new RequestContext();
        $this->logger = $logger ?? new NullLogger();
        $this->rootLocationId = null;
    }

    public function setRootLocationId(?int $rootLocationId): void
    {
        $this->rootLocationId = $rootLocationId;
    }

    /**
     * @return array<string, mixed> An array of parameters
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function matchRequest(Request $request): array
    {
        try {
            $requestedPath = $request->attributes->getString('semanticPathinfo', $request->getPathInfo());
            $urlAlias = $this->getUrlAlias($requestedPath);
            if ($this->rootLocationId === null) {
                $pathPrefix = '/';
            } else {
                $pathPrefix = $this->generator->getPathPrefixByRootLocationId((int)$this->rootLocationId);
            }

            $params = [
                '_route' => static::URL_ALIAS_ROUTE_NAME,
            ];

            return $this->buildParametersByUrlAliasType($urlAlias, $params, $requestedPath, $pathPrefix);
        } catch (NotFoundException $e) {
            throw new ResourceNotFoundException($e->getMessage(), $e->getCode(), $e);
        }
    }

    protected function removePathPrefix(string $path, string $prefix): string
    {
        if ($prefix !== '/' && mb_stripos($path, $prefix) === 0) {
            $path = mb_substr($path, mb_strlen($prefix));
        }

        return $path;
    }

    /**
     * Returns true of false on comparing $urlAlias->path and $path with case sensitivity.
     *
     * Used to determine if redirect is needed because requested path is case-different
     * from the stored one.
     */
    protected function needsCaseRedirect(URLAlias $loadedUrlAlias, string $requestedPath, string $pathPrefix): bool
    {
        // If requested path is excluded from tree root jail, compare it to loaded UrlAlias directly.
        if ($this->generator->isUriPrefixExcluded($requestedPath)) {
            return strcmp($loadedUrlAlias->path, $requestedPath) !== 0;
        }

        // Compare loaded UrlAlias with requested path, prefixed with configured path prefix.
        return
            strcmp(
                $loadedUrlAlias->path,
                $pathPrefix . ($pathPrefix === '/' ? trim($requestedPath, '/') : rtrim($requestedPath, '/'))
            ) !== 0
        ;
    }

    /**
     * Returns the UrlAlias object to use, starting from the request.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException if the path does not exist or is not valid for the given language
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    protected function getUrlAlias(string $pathInfo): UrlAlias
    {
        return $this->urlAliasService->lookup($pathInfo);
    }

    /**
     * Gets the RouteCollection instance associated with this Router.
     *
     * @return \Symfony\Component\Routing\RouteCollection A RouteCollection instance
     */
    public function getRouteCollection(): RouteCollection
    {
        return new RouteCollection();
    }

    /**
     * {@inheritDoc}
     *
     * The "location" key in $parameters must be set to a valid {@see \Ibexa\Contracts\Core\Repository\Values\Content\Location} object.
     * "locationId" parameter can also be provided.
     *
     * @param array<string, mixed> $parameters An array of parameters
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     *
     * @api
     */
    public function generate(
        string $name,
        array $parameters = [],
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string {
        if ($name === '' &&
            array_key_exists(RouteObjectInterface::ROUTE_OBJECT, $parameters) &&
            $this->supportsObject($parameters[RouteObjectInterface::ROUTE_OBJECT])
        ) {
            $location = $parameters[RouteObjectInterface::ROUTE_OBJECT];
            unset($parameters[RouteObjectInterface::ROUTE_OBJECT]);

            return $this->generator->generate($location, $parameters, $referenceType);
        }

        // Normal route name
        if ($name === static::URL_ALIAS_ROUTE_NAME) {
            if (isset($parameters['location']) || isset($parameters['locationId'])) {
                // Check if location is a valid Location object
                if (isset($parameters['location']) && !$parameters['location'] instanceof Location) {
                    throw new LogicException(
                        "When generating a UrlAlias route, the 'location' parameter must be a valid " . Location::class . '.'
                    );
                }

                $location = $parameters['location'] ?? $this->locationService->loadLocation($parameters['locationId']);
                unset($parameters['location'], $parameters['locationId'], $parameters['viewType'], $parameters['layout']);

                return $this->generator->generate($location, $parameters, $referenceType);
            }

            if (isset($parameters['contentId'])) {
                $contentInfo = $this->contentService->loadContentInfo($parameters['contentId']);
                unset($parameters['contentId'], $parameters['viewType'], $parameters['layout']);

                if (empty($contentInfo->mainLocationId)) {
                    throw new LogicException('Cannot generate a UrlAlias route for content without main Location.');
                }

                return $this->generator->generate(
                    $this->locationService->loadLocation($contentInfo->mainLocationId),
                    $parameters,
                    $referenceType
                );
            }

            throw new InvalidArgumentException(
                "When generating a UrlAlias route, either 'location', 'locationId', or 'contentId' must be provided."
            );
        }

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
        throw new RuntimeException(
            "The UrlAliasRouter doesn't support the match() method. Use matchRequest() instead."
        );
    }

    /**
     * Whether the router supports the thing in $name to generate a route.
     *
     * This check does not need to look if the specific instance can be
     * resolved to a route, only whether the router can generate routes from
     * objects of this class.
     *
     * @param string|object $name The route name or route object
     */
    public function supports(string|object $name): bool
    {
        return $name === static::URL_ALIAS_ROUTE_NAME || (is_object($name) && $this->supportsObject($name));
    }

    private function supportsObject(object $object): bool
    {
        return $object instanceof Location;
    }

    public function getRouteDebugMessage(string $name, array $parameters = []): string
    {
        return $name;
    }

    /**
     * @param array<string, mixed> $params An array of parameters
     *
     * @return array<string, mixed> An array of parameters
     *
     * @throws \Ibexa\Contracts\Core\Exception\InvalidArgumentException
     */
    private function buildParametersByUrlAliasType(
        URLAlias $urlAlias,
        array $params,
        string $requestedPath,
        string $pathPrefix
    ): array {
        return match ($urlAlias->type) {
            URLAlias::LOCATION => $this->buildParametersForLocationUrlAlias(
                $urlAlias,
                $params,
                $requestedPath,
                $pathPrefix
            ),
            URLAlias::RESOURCE => $this->buildParametersForResourceUrlAlias(
                $urlAlias,
                $params,
                $requestedPath,
                $pathPrefix
            ),
            URLAlias::VIRTUAL => $this->buildParametersForVirtualUrlAlias(
                $urlAlias,
                $params,
                $requestedPath,
                $pathPrefix
            ),
            default => throw new \Ibexa\Contracts\Core\Exception\InvalidArgumentException(
                '$urlAlias->type',
                "Unknown URLAlias type: $urlAlias->type"
            )
        };
    }

    /**
     * @param array<string, mixed> $params An array of parameters
     *
     * @return array<string, mixed> An array of parameters
     */
    private function buildParametersForLocationUrlAlias(
        URLAlias $urlAlias,
        array $params,
        string $requestedPath,
        string $pathPrefix
    ): array {
        $location = $this->generator->loadLocation($urlAlias->destination);
        $params += [
            '_controller' => static::VIEW_ACTION,
            'contentId' => $location->contentId,
            'locationId' => $urlAlias->destination,
            'viewType' => ViewManagerInterface::VIEW_TYPE_FULL,
            'layout' => true,
        ];

        // For Location alias setup 301 redirect to Location's current URL when:
        // 1. alias is history
        // 2. alias is custom with forward flag true
        // 3. requested URL is not case-sensitive equal with the one loaded
        if ($urlAlias->isHistory === true || ($urlAlias->isCustom === true && $urlAlias->forward === true)) {
            $params += [
                'semanticPathinfo' => $this->generator->generate($location, []),
                'needsRedirect' => true,
                // Specify not to prepend siteaccess while redirecting when applicable since it would be already present (see UrlAliasGenerator::doGenerate())
                'prependSiteaccessOnRedirect' => false,
            ];
        } elseif ($this->needsCaseRedirect($urlAlias, $requestedPath, $pathPrefix)) {
            $params += [
                'semanticPathinfo' => $this->removePathPrefix($urlAlias->path, $pathPrefix),
                'needsRedirect' => true,
            ];

            if ($urlAlias->destination instanceof Location) {
                $params += ['locationId' => $urlAlias->destination->id];
            }
        }

        $this->logger->info(
            "UrlAlias matched location #{$urlAlias->destination}. Forwarding to ViewController"
        );

        return $params;
    }

    /**
     * @param array<string, mixed> $params An array of parameters
     *
     * @return array<string, mixed> An array of parameters
     */
    private function buildParametersForResourceUrlAlias(
        URLAlias $urlAlias,
        array $params,
        string $requestedPath,
        string $pathPrefix
    ): array {
        // In URLAlias terms, "forward" means "redirect".
        if ($urlAlias->forward) {
            $params += [
                'semanticPathinfo' => '/' . trim($urlAlias->destination, '/'),
                'needsRedirect' => true,
            ];
        } elseif ($this->needsCaseRedirect($urlAlias, $requestedPath, $pathPrefix)) {
            // Handle case-correction redirect
            $params += [
                'semanticPathinfo' => $this->removePathPrefix($urlAlias->path, $pathPrefix),
                'needsRedirect' => true,
            ];
        } else {
            $params += [
                'semanticPathinfo' => '/' . trim($urlAlias->destination, '/'),
                'needsForward' => true,
            ];
        }

        return $params;
    }

    /**
     * @param array<string, mixed> $params An array of parameters
     *
     * @return array<string, mixed> An array of parameters
     */
    private function buildParametersForVirtualUrlAlias(
        URLAlias $urlAlias,
        array $params,
        string $requestedPath,
        string $pathPrefix
    ): array {
        // Handle case-correction redirect
        if ($this->needsCaseRedirect($urlAlias, $requestedPath, $pathPrefix)) {
            $params += [
                'semanticPathinfo' => $this->removePathPrefix($urlAlias->path, $pathPrefix),
                'needsRedirect' => true,
            ];
        } else {
            // Virtual aliases should load the Content at homepage URL
            $params += [
                'semanticPathinfo' => '/',
                'needsForward' => true,
            ];
        }

        return $params;
    }
}
