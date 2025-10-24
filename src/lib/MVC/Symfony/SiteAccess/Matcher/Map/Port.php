<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\MVC\Symfony\SiteAccess\Matcher\Map;

use Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest;
use Ibexa\Core\MVC\Symfony\SiteAccess\Matcher\Map;
use Ibexa\Core\MVC\Symfony\SiteAccess\VersatileMatcher;

class Port extends Map
{
    /**
     * @param array<int|string, string> $map
     */
    public function __construct(array $map)
    {
        $normalizedMap = [];
        foreach ($map as $key => $value) {
            $normalizedMap[(string)$key] = $value;
        }
        parent::__construct($normalizedMap);
    }

    public function getName(): string
    {
        return 'port';
    }

    /**
     * Injects the request object to match against.
     *
     * @param SimplifiedRequest $request
     */
    public function setRequest(SimplifiedRequest $request): void
    {
        if (!isset($this->key)) {
            $key = $request->getPort() ?? match ($request->getScheme()) {
                'https' => 443,
                default => 80,
            };

            $this->setMapKey((string)$key);
        }

        parent::setRequest($request);
    }

    public function reverseMatch(string $siteAccessName): ?VersatileMatcher
    {
        $matcher = parent::reverseMatch($siteAccessName);
        if ($matcher instanceof self) {
            $matcher->getRequest()->setPort((int)$matcher->getMapKey());
        }

        return $matcher;
    }
}
