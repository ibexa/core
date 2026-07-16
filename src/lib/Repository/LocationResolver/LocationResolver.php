<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Repository\LocationResolver;

use Ibexa\Contracts\Core\Repository\Exceptions\BadStateException;
use Ibexa\Contracts\Core\Repository\Exceptions\ForbiddenException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;

/**
 * @internal For internal use by Ibexa core packages
 */
interface LocationResolver
{
    /**
     * @throws NotFoundException
     * @throws ForbiddenException
     * @throws BadStateException
     */
    public function resolveLocation(ContentInfo $contentInfo): Location;
}
