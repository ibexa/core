<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\MVC\Symfony\SiteAccess\Matcher\Map;

use Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest;
use Ibexa\Core\MVC\Symfony\SiteAccess\Matcher\Map;
use Ibexa\Core\MVC\Symfony\SiteAccess\VersatileMatcher;

class Host extends Map
{
    public function getName(): string
    {
        return 'host:map';
    }

    /**
     * Injects the request object to match against.
     *
     * @param SimplifiedRequest $request
     */
    public function setRequest(SimplifiedRequest $request): void
    {
        if (!isset($this->key)) {
            $this->setMapKey((string)$request->getHost());
        }

        parent::setRequest($request);
    }

    public function reverseMatch(string $siteAccessName): ?VersatileMatcher
    {
        $matcher = parent::reverseMatch($siteAccessName);
        if ($matcher instanceof self) {
            $matcher->getRequest()->setHost($matcher->getMapKey());
        }

        return $matcher;
    }
}
