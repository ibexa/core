<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\MVC\Symfony\SiteAccess\Matcher;

use Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest;
use Ibexa\Core\MVC\Symfony\SiteAccess\URILexer;
use Ibexa\Core\MVC\Symfony\SiteAccess\VersatileMatcher;

class URIText extends AffixBasedTextMatcher implements URILexer
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

    public function setRequest(SimplifiedRequest $request): void
    {
        if (!isset($this->element)) {
            $this->setMatchElement((string)$request->getPathInfo());
        }

        parent::setRequest($request);
    }

    public function analyseURI($uri): string
    {
        $uri = '/' . ltrim($uri, '/');

        return preg_replace("@$this->regex@", '', $uri) ?? $uri;
    }

    public function analyseLink($linkUri): string
    {
        $linkUri = '/' . ltrim($linkUri, '/');
        $siteAccessUri = "/$this->prefix" . $this->match() . $this->suffix;

        return $siteAccessUri . $linkUri;
    }

    public function reverseMatch(string $siteAccessName): ?VersatileMatcher
    {
        $this->request->setPathinfo("/{$this->prefix}{$siteAccessName}{$this->suffix}{$this->request->getPathInfo()}");

        return $this;
    }

    public function getRequest(): SimplifiedRequest
    {
        return $this->request;
    }
}
