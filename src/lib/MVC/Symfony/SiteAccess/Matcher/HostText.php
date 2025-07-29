<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\MVC\Symfony\SiteAccess\Matcher;

use Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest;
use Ibexa\Core\MVC\Symfony\SiteAccess\VersatileMatcher;

class HostText extends AffixBasedTextMatcher
{
    protected function buildRegex(): string
    {
        return '^' . preg_quote($this->prefix, '@') . "([\w-]+)" . preg_quote($this->suffix, '@') . '$';
    }

    protected function getMatchedItemNumber(): int
    {
        return 1;
    }

    public function getName(): string
    {
        return 'host:text';
    }

    public function setRequest(SimplifiedRequest $request): void
    {
        if (!isset($this->element)) {
            $this->setMatchElement((string)$request->getHost());
        }

        parent::setRequest($request);
    }

    public function reverseMatch(string $siteAccessName): ?VersatileMatcher
    {
        $this->request->setHost($this->prefix . $siteAccessName . $this->suffix);

        return $this;
    }

    public function getRequest(): SimplifiedRequest
    {
        return $this->request;
    }
}
