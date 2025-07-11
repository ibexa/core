<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content;

use Ibexa\Contracts\Core\Repository\Values\ValueObject;

/**
 * This class is used to create a new Location for a content object.
 */
class LocationCreateStruct extends ValueObject
{
    /**
     * The id of the parent location under which the new location should be created.
     *
     * Required.
     */
    public int $parentLocationId;

    /**
     * Location priority.
     *
     * Position of the Location among its siblings when sorted using priority
     * sort order.
     */
    public int $priority = 0;

    /**
     * Indicates that the Location entity has been explicitly marked as hidden.
     */
    public bool $hidden = false;

    /**
     * An universally unique string identifier.
     *
     * Needs to be a unique Location->remoteId string value.
     */
    public ?string $remoteId = null;

    /**
     * Specifies which property the child locations should be sorted on.
     *
     * Valid values are found at {@link Location::SORT_FIELD_*}
     *
     * If not set, will be taken out of ContentType's default sort field
     */
    public ?int $sortField = null;

    /**
     * Specifies whether the sort order should be ascending or descending.
     *
     * Valid values are {@link Location::SORT_ORDER_*}
     *
     * If not set, will be taken out of ContentType's default sort order
     */
    public ?int $sortOrder = null;
}
