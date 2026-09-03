<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\MVC\Symfony\SiteAccess;

use Ibexa\Core\MVC\Symfony\SiteAccess;

/**
 * Interface for SiteAccess aware services.
 *
 * @deprecated 6.0, to be removed in 7.0. Use \Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessServiceInterface::getCurrent() instead.
 */
interface SiteAccessAware
{
    public function setSiteAccess(?SiteAccess $siteAccess = null);
}
