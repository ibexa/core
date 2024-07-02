<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\MVC\Symfony\SiteAccess\Matcher;

use Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest;
use Ibexa\Core\MVC\Symfony\SiteAccess\URILexer;
use Ibexa\Core\MVC\Symfony\SiteAccess\VersatileMatcher;

class URIText extends PrefixSuffixBasedTextMatcher implements URILexer
{
    protected function buildRegex(): string
    {
        return '^(/' . preg_quote($this->prefix, '@') . '(\w+)' . preg_quote($this->suffix, '@') . ')';
    }

    protected function getMatchedItemNumber(): int
    {
        return 2;
    }

    public function getName(): string
    {
        return 'uri:text';
    }

    /**
     * Injects the request object to match against.
     *
     * @param \Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest $request
     */
    public function setRequest(SimplifiedRequest $request): void
    {
        if (!$this->element) {
            $this->setMatchElement($request->pathinfo);
        }

        parent::setRequest($request);
    }

    /**
     * Analyses $uri and removes the SiteAccess part, if needed.
     *
     * @param string $uri The original URI
     *
     * @return string The modified URI
     */
    public function analyseURI($uri): string
    {
        $uri = '/' . ltrim($uri, '/');

        return preg_replace("@$this->regex@", '', $uri) ?? $uri;
    }

    /**
     * Analyses $linkUri when generating a link to a route, in order to have the SiteAccess part back in the URI.
     *
     * @param string $linkUri
     *
     * @return string The modified link URI
     */
    public function analyseLink($linkUri): string
    {
        $linkUri = '/' . ltrim($linkUri, '/');
        $siteAccessUri = "/$this->prefix" . $this->match() . $this->suffix;

        return $siteAccessUri . $linkUri;
    }

    public function reverseMatch($siteAccessName): ?VersatileMatcher
    {
        $this->request->setPathinfo("/{$this->prefix}{$siteAccessName}{$this->suffix}{$this->request->pathinfo}");

        return $this;
    }

    public function getRequest(): SimplifiedRequest
    {
        return $this->request;
    }
}
