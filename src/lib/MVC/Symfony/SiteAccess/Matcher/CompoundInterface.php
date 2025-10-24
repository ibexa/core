<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\MVC\Symfony\SiteAccess\Matcher;

use Ibexa\Core\MVC\Symfony\SiteAccess\Matcher;
use Ibexa\Core\MVC\Symfony\SiteAccess\MatcherBuilderInterface;
use Ibexa\Core\MVC\Symfony\SiteAccess\VersatileMatcher;

interface CompoundInterface extends VersatileMatcher
{
    /**
     * Injects the matcher builder to allow the Compound matcher to properly build the underlying matchers.
     *
     * @param MatcherBuilderInterface $matcherBuilder
     */
    public function setMatcherBuilder(MatcherBuilderInterface $matcherBuilder): void;

    /**
     * Returns all used sub-matchers.
     *
     * @return Matcher[]
     */
    public function getSubMatchers(): array;

    /**
     * Replaces sub-matchers.
     *
     * @param Matcher[] $subMatchers
     */
    public function setSubMatchers(array $subMatchers): void;
}
