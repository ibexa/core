<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Fragment;

use Ibexa\Core\MVC\Symfony\SiteAccess;
use Symfony\Component\HttpKernel\Controller\ControllerReference;

/**
 * @internal
 */
interface SiteAccessSerializerInterface
{
    public function serializeSiteAccessAsControllerAttributes(
        SiteAccess $siteAccess,
        ControllerReference $controller
    ): void;
}
