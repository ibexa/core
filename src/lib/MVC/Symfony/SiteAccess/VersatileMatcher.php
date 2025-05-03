<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\MVC\Symfony\SiteAccess;

use Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest;

/**
 * Interface for SiteAccess matchers.
 *
 * VersatileMatcher makes it possible to do a reverse match (e.g. "Is this matcher knows provided SiteAccess name?").
 * Versatile matchers enable cross-siteAccess linking.
 */
interface VersatileMatcher extends Matcher
{
    /**
     * Returns a matcher object corresponding to $siteAccessName or null if non-applicable.
     *
     * Note: VersatileMatcher objects always receive a request with cleaned-up pathInfo (i.e. no SiteAccess part inside).
     *
     * @return \Ibexa\Core\MVC\Symfony\SiteAccess\VersatileMatcher|null Typically the current matcher, with an updated request.
     */
    public function reverseMatch(string $siteAccessName): ?VersatileMatcher;

    /**
     * Returns the SimplifiedRequest object corresponding to the reverse match.
     * This request object can then be used to build a link to the "reverse matched" SiteAccess.
     *
     * @see reverseMatch()
     */
    public function getRequest(): SimplifiedRequest;
}
