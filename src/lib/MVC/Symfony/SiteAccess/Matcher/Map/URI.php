<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\MVC\Symfony\SiteAccess\Matcher\Map;

use Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest;
use Ibexa\Core\MVC\Symfony\SiteAccess\Matcher\Map;
use Ibexa\Core\MVC\Symfony\SiteAccess\URILexer;
use Ibexa\Core\MVC\Symfony\SiteAccess\VersatileMatcher;

class URI extends Map implements URILexer
{
    /**
     * Injects the request object to match against.
     *
     * @param \Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest $request
     */
    public function setRequest(SimplifiedRequest $request): void
    {
        if (!isset($this->key)) {
            sscanf((string)$request->getPathInfo(), '/%[^/]', $key);
            $this->setMapKey(rawurldecode((string)$key));
        }

        parent::setRequest($request);
    }

    public function getName(): string
    {
        return 'uri:map';
    }

    /**
     * Fixes up $uri to remove the siteaccess part, if needed.
     *
     * @param string $uri The original URI
     *
     * @return string
     */
    public function analyseURI($uri)
    {
        if (($siteaccessPart = "/$this->key") === $uri) {
            return '/';
        }

        if (mb_strpos($uri, $siteaccessPart) === 0) {
            return mb_substr($uri, mb_strlen($siteaccessPart));
        }

        return $uri;
    }

    /**
     * Analyses $linkUri when generating a link to a route, in order to have the siteaccess part back in the URI.
     *
     * @param string $linkUri
     *
     * @return string The modified link URI
     */
    public function analyseLink($linkUri): string
    {
        // Joining slash between uriElements and actual linkUri must be present, except if $linkUri is empty or is just the slash root.
        $joiningSlash = empty($linkUri) || ($linkUri === '/') ? '' : '/';
        $linkUri = ltrim($linkUri, '/');
        // Removing query string to analyse as SiteAccess might be in it.
        $qsPos = strpos($linkUri, '?');
        $queryString = '';
        if ($qsPos !== false) {
            $queryString = substr($linkUri, $qsPos);
            $linkUri = substr($linkUri, 0, $qsPos);
        }

        return "/{$this->key}{$joiningSlash}{$linkUri}{$queryString}";
    }

    public function reverseMatch(string $siteAccessName): ?VersatileMatcher
    {
        $matcher = parent::reverseMatch($siteAccessName);
        if ($matcher instanceof self) {
            $request = $matcher->getRequest();
            // Clean up the "old" SiteAccess prefix and add the new prefix.
            $request->setPathinfo($this->analyseLink((string)$request->getPathInfo()));
        }

        return $matcher;
    }
}
