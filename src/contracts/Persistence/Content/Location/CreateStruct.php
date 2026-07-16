<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Contracts\Core\Persistence\Content\Location;

use Ibexa\Contracts\Core\Persistence\ValueObject;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;

class CreateStruct extends ValueObject
{
    /**
     * Location priority.
     *
     * Position of the Location among its siblings when sorted using priority
     * sort order.
     *
     * @var int
     */
    public $priority = 0;

    /**
     * Indicates that the Location entity has been explicitly marked as hidden.
     *
     * @var bool
     */
    public $hidden = false;

    /**
     * Indicates that the Location is implicitly marked as hidden by a parent
     * location.
     *
     * @var bool
     */
    public $invisible = false;

    /**
     * Remote ID.
     *
     * A universally unique identifier.
     *
     * @var mixed
     */
    public $remoteId;

    /**
     * Content ID.
     *
     * ID of the corresponding {@see Content}.
     *
     * @var int
     */
    public $contentId;

    /**
     * Content version.
     *
     * Version of the corresponding {@see Content}.
     *
     * @todo Rename to $contentVersionNo?
     *
     * @var int
     */
    public $contentVersion;

    /**
     * Identifier of the main location.
     *
     * If the content object in this location has multiple locations,
     * $mainLocationId will point to the main one.
     * This is allowed to be set to true, this will mean this should become main location
     * (@todo Find a better way to deal with being able to create the main location)
     *
     * @var int|true
     */
    public $mainLocationId = true;

    /**
     * Specifies which property the child locations should be sorted on.
     *
     * Valid values are found at {@link Location::SORT_FIELD_*}
     *
     * @var mixed
     */
    public $sortField;

    /**
     * Specifies whether the sort order should be ascending or descending.
     *
     * Valid values are {@link Location::SORT_ORDER_*}
     *
     * @var mixed
     */
    public $sortOrder;

    /**
     * Parent location's Id.
     *
     * @var int
     */
    public $parentId;
}
