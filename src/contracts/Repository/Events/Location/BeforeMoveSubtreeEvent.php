<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\Location;

use Ibexa\Contracts\Core\Repository\Event\BeforeEvent;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;

final class BeforeMoveSubtreeEvent extends BeforeEvent
{
    private Location $location;

    private Location $newParentLocation;

    public function __construct(Location $location, Location $newParentLocation)
    {
        $this->location = $location;
        $this->newParentLocation = $newParentLocation;
    }

    public function getLocation(): Location
    {
        return $this->location;
    }

    public function getNewParentLocation(): Location
    {
        return $this->newParentLocation;
    }
}
