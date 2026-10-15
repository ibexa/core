<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\MVC\Symfony\Routing\Generator;

use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\MVC\Symfony\Routing\Generator;
use Ibexa\Core\MVC\Symfony\Routing\UrlAliasRouter;
use Symfony\Component\Routing\RouterInterface;

/**
 * URL generator for UrlAlias based links.
 *
 * @see UrlAliasRouter
 */
class UrlAliasGenerator extends Generator
{
    public const string INTERNAL_CONTENT_VIEW_ROUTE = 'ibexa.content.view';

    private Repository $repository;

    private RouterInterface $defaultRouter;

    private int $rootLocationId;

    /** @var string[] */
    private array $excludedUriPrefixes = [];

    /** @var array<string, array<int, string>> */
    private array $pathPrefixMap = [];

    private ConfigResolverInterface $configResolver;

    /**
     * Array of characters that are potentially unsafe for output for (x)html, json, etc,
     * and respective url-encoded value.
     *
     * @var array<string, string>
     */
    private array $unsafeCharMap;

    /**
     * @param array<string, string> $unsafeCharMap
     */
    public function __construct(
        Repository $repository,
        RouterInterface $defaultRouter,
        ConfigResolverInterface $configResolver,
        array $unsafeCharMap = []
    ) {
        $this->repository = $repository;
        $this->defaultRouter = $defaultRouter;
        $this->configResolver = $configResolver;
        $this->unsafeCharMap = $unsafeCharMap;
    }

    /**
     * Generates the URL from $urlResource and $parameters.
     * Entries in $parameters will be added in the query string.
     *
     * @param Location $urlResource
     */
    public function doGenerate(
        mixed $urlResource,
        array $parameters
    ): string {
        $siteAccess = $parameters['siteaccess'] ?? null;

        unset($parameters['language'], $parameters['contentId'], $parameters['siteaccess']);

        $pathString = $this->createPathString($urlResource, $siteAccess);
        $queryString = $this->createQueryString($parameters);
        $url = $pathString . $queryString;

        return $this->filterCharactersOfURL($url);
    }

    /**
     * Injects current root locationId that will be used for link generation.
     */
    public function setRootLocationId(int $rootLocationId): void
    {
        $this->rootLocationId = $rootLocationId;
    }

    /**
     * @param string[] $excludedUriPrefixes
     */
    public function setExcludedUriPrefixes(array $excludedUriPrefixes): void
    {
        $this->excludedUriPrefixes = $excludedUriPrefixes;
    }

    /**
     * Returns path corresponding to $rootLocationId.
     *
     * @param array<string>|null $languages
     *
     * @throws NotFoundException
     */
    public function getPathPrefixByRootLocationId(
        ?int $rootLocationId,
        ?array $languages = null,
        ?string $siteAccess = null
    ): string {
        if ($rootLocationId === null || $rootLocationId === 0) {
            return '';
        }

        if (!isset($this->pathPrefixMap[$siteAccess])) {
            $this->pathPrefixMap[$siteAccess] = [];
        }

        if (!isset($this->pathPrefixMap[$siteAccess][$rootLocationId])) {
            $this->pathPrefixMap[$siteAccess][$rootLocationId] = $this->repository
                ->getURLAliasService()
                ->reverseLookup(
                    $this->loadLocation($rootLocationId, $languages),
                    null,
                    false,
                    $languages
                )
                ->path;
        }

        return $this->pathPrefixMap[$siteAccess][$rootLocationId];
    }

    /**
     * Checks if passed URI has an excluded prefix, when a root location is defined.
     */
    public function isUriPrefixExcluded(string $uri): bool
    {
        foreach ($this->excludedUriPrefixes as $excludedPrefix) {
            $excludedPrefix = '/' . ltrim($excludedPrefix, '/');
            if (mb_stripos($uri, $excludedPrefix) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Loads a location by its locationId, regardless to user limitations since the router is invoked BEFORE security (no user authenticated yet).
     * Not to be used for link generation.
     *
     * @param array<string>|null $languages
     */
    public function loadLocation(
        int $locationId,
        ?array $languages = null
    ): Location {
        return $this->repository->sudo(
            static function (Repository $repository) use ($locationId, $languages): Location {
                /* @var $repository \Ibexa\Core\Repository\Repository */
                return $repository->getLocationService()->loadLocation($locationId, $languages);
            }
        );
    }

    private function createPathString(
        Location $location,
        ?string $siteAccess = null
    ): string {
        $urlAliasService = $this->repository->getURLAliasService();

        if (!empty($siteAccess)) {
            // We generate for a different SiteAccess, so potentially in a different language.
            $languages = $this->configResolver->getParameter('languages', null, $siteAccess);
            $urlAliases = iterator_to_array(
                $urlAliasService->listLocationAliases($location, false, null, null, $languages)
            );
            // Use the target SiteAccess root location
            $rootLocationId = $this->configResolver->getParameter('content.tree_root.location_id', null, $siteAccess);
        } else {
            $languages = null;
            $urlAliases = iterator_to_array($urlAliasService->listLocationAliases($location, false));
            $rootLocationId = $this->rootLocationId ?? null;
        }

        if (!empty($urlAliases)) {
            $path = $urlAliases[0]->path;
            // Remove rootLocation's prefix if needed.
            if ($rootLocationId !== null) {
                $pathPrefix = $this->getPathPrefixByRootLocationId($rootLocationId, $languages, $siteAccess);
                // "/" cannot be considered as a path prefix since it's root, so we ignore it.
                if ($pathPrefix !== '/' && ($path === $pathPrefix || mb_stripos($path, $pathPrefix . '/') === 0)) {
                    $path = mb_substr($path, mb_strlen($pathPrefix));
                } elseif ($pathPrefix !== '/' && !$this->isUriPrefixExcluded($path) && $this->logger !== null) {
                    // Location path is outside configured content tree and doesn't have an excluded prefix.
                    // This is most likely an error (from content edition or link generation logic).
                    $this->logger->warning("Generating a link to a location outside root content tree: '$path' is outside tree starting to location #$rootLocationId");
                }
            }
        } else {
            $path = $this->defaultRouter->generate(
                self::INTERNAL_CONTENT_VIEW_ROUTE,
                ['contentId' => $location->contentId, 'locationId' => $location->id]
            );
        }

        return $path ?: '/';
    }

    /**
     * Creates query string from parameters. If `_fragment` parameter is provided then
     * fragment identifier is added at the end of the URL.
     *
     * @param array<string, mixed> $parameters
     */
    private function createQueryString(array $parameters): string
    {
        $queryString = '';
        $fragment = null;
        if (isset($parameters['_fragment'])) {
            $fragment = $parameters['_fragment'];
            unset($parameters['_fragment']);
        }

        if (!empty($parameters)) {
            $queryString = '?' . http_build_query($parameters, '', '&');
        }

        if ($fragment) {
            // logic aligned with Symfony 3.4: \Symfony\Component\Routing\Generator\UrlGenerator::doGenerate
            $queryString .= '#' . strtr(rawurlencode($fragment), ['%2F' => '/', '%3F' => '?']);
        }

        return $queryString;
    }

    /**
     * Replace potentially unsafe characters with url-encoded counterpart.
     */
    private function filterCharactersOfURL(string $url): string
    {
        return strtr($url, $this->unsafeCharMap);
    }
}
