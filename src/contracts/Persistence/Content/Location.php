<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Contracts\Core\Persistence\Content;

use Ibexa\Contracts\Core\Persistence\ValueObject;

/**
 * Struct containing accessible properties on Location entities.
 */
class Location extends ValueObject
{
    // Following constants borrowed from ibexa_contentTreeNode, for data compatibility.
    // Actual names ought to be changed to better match current concepts.
    public const SORT_FIELD_PATH = 1;
    public const SORT_FIELD_PUBLISHED = 2;
    public const SORT_FIELD_MODIFIED = 3;
    public const SORT_FIELD_SECTION = 4;
    public const SORT_FIELD_DEPTH = 5;
    public const SORT_FIELD_CLASS_IDENTIFIER = 6;
    public const SORT_FIELD_CLASS_NAME = 7;
    public const SORT_FIELD_PRIORITY = 8;
    public const SORT_FIELD_NAME = 9;
    public const SORT_FIELD_MODIFIED_SUBNODE = 10;
    public const SORT_FIELD_NODE_ID = 11;
    public const SORT_FIELD_CONTENTOBJECT_ID = 12;

    public const SORT_ORDER_DESC = 0;
    public const SORT_ORDER_ASC = 1;

    /**
     * Location ID.
     *
     * @var mixed Location ID.
     */
    public $id;

    /**
     * Location priority.
     *
     * Position of the Location among its siblings when sorted using priority
     * sort order.
     *
     * @var int
     */
    public $priority;

    /**
     * Indicates that the Location entity has been explicitly marked as hidden.
     *
     * @var bool
     */
    public $hidden;

    /**
     * Indicates that the Location is implicitly marked as hidden by a parent
     * location.
     *
     * @var bool
     */
    public $invisible;

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
     * ID of the corresponding {@see \Ibexa\Contracts\Core\Persistence\Content}.
     *
     * @var int
     */
    public $contentId;

    /**
     * Parent ID.
     *
     * @var mixed Location ID.
     */
    public $parentId;

    /**
     * The materialized path of the location entry, eg: /1/2/.
     *
     * @var string
     */
    public $pathString;

    /**
     * Depth location has in the location tree.
     *
     * @var int
     */
    public $depth;

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
}
