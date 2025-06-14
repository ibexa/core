<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\Location;

use Ibexa\Contracts\Core\Repository\Event\AfterEvent;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationCreateStruct;

final class CreateLocationEvent extends AfterEvent
{
    private Location $location;

    private ContentInfo $contentInfo;

    private LocationCreateStruct $locationCreateStruct;

    public function __construct(
        Location $location,
        ContentInfo $contentInfo,
        LocationCreateStruct $locationCreateStruct
    ) {
        $this->location = $location;
        $this->contentInfo = $contentInfo;
        $this->locationCreateStruct = $locationCreateStruct;
    }

    public function getLocation(): Location
    {
        return $this->location;
    }

    public function getContentInfo(): ContentInfo
    {
        return $this->contentInfo;
    }

    public function getLocationCreateStruct(): LocationCreateStruct
    {
        return $this->locationCreateStruct;
    }
}
