<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Limitation\Target;

use Ibexa\Contracts\Core\Limitation\Target;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;

/**
 * Location Limitation target.
 */
final class DestinationLocation extends ValueObject implements Target
{
    /** @var int */
    private $locationId;

    /** @var \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo */
    private $targetContentInfo;

    public function __construct(int $locationId, ContentInfo $targetContentInfo, array $properties = [])
    {
        $this->locationId = $locationId;
        $this->targetContentInfo = $targetContentInfo;

        parent::__construct($properties);
    }

    public function getLocationId(): int
    {
        return $this->locationId;
    }

    public function getTargetContentInfo(): ContentInfo
    {
        return $this->targetContentInfo;
    }
}
