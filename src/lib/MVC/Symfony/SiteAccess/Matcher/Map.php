<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\MVC\Symfony\SiteAccess\Matcher;

use Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest;
use Ibexa\Core\MVC\Symfony\SiteAccess\VersatileMatcher;

abstract class Map implements VersatileMatcher
{
    /**
     * String that will be looked up in the map.
     */
    protected ?string $key = null;

    /**
     * Map used for the matching.
     *
     * @var array<string, string|bool>
     */
    protected array $map = [];

    /**
     * Map used for reverse matching.
     *
     * @var array<string, string>
     */
    protected array $reverseMap = [];

    protected SimplifiedRequest $request;

    /**
     * @param array<string, string> $map Map used for matching.
     */
    public function __construct(array $map)
    {
        $this->map = $map;
    }

    /**
     * Do not serialize the SiteAccess configuration to reduce ESI request URL size.
     *
     * @see https://issues.ibexa.co/browse/EZP-23168
     *
     * @return array<string>
     */
    public function __sleep()
    {
        $this->map = [];
        $this->reverseMap = [];

        return ['key'];
    }

    public function setRequest(SimplifiedRequest $request): void
    {
        $this->request = $request;
    }

    public function getRequest(): SimplifiedRequest
    {
        return $this->request;
    }

    /**
     * Injects the key that will be used for matching against the map configuration.
     */
    public function setMapKey(?string $key): void
    {
        $this->key = $key;
    }

    public function getMapKey(): ?string
    {
        return $this->key;
    }

    public function match(): string|bool
    {
        return $this->map[$this->key] ?? false;
    }

    public function reverseMatch(string $siteAccessName): ?VersatileMatcher
    {
        $reverseMap = $this->getReverseMap($siteAccessName);

        if (!isset($reverseMap[$siteAccessName])) {
            return null;
        }

        $this->setMapKey($reverseMap[$siteAccessName]);

        return $this;
    }

    /**
     * @return array<string, string>
     */
    private function getReverseMap(string $defaultSiteAccess): array
    {
        if (!empty($this->reverseMap)) {
            return $this->reverseMap;
        }

        /** @var array<string, string|true> $map */
        $map = $this->map;
        foreach ($map as &$value) {
            // $value can be true in the case of the use of a Compound matcher
            if ($value === true) {
                $value = $defaultSiteAccess;
            }
        }
        /** @var array<string, string> $map */

        /** @var array<string, string> */
        return $this->reverseMap = array_flip($map);
    }
}
