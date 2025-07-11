<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\Location;

use Ibexa\Contracts\Core\Repository\Event\AfterEvent;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;

final class CopySubtreeEvent extends AfterEvent
{
    private Location $location;

    private Location $subtree;

    private Location $targetParentLocation;

    public function __construct(
        Location $location,
        Location $subtree,
        Location $targetParentLocation
    ) {
        $this->location = $location;
        $this->subtree = $subtree;
        $this->targetParentLocation = $targetParentLocation;
    }

    public function getLocation(): Location
    {
        return $this->location;
    }

    public function getSubtree(): Location
    {
        return $this->subtree;
    }

    public function getTargetParentLocation(): Location
    {
        return $this->targetParentLocation;
    }
}
