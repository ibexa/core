<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Contracts\Core\Persistence\Content\Relation;

use Ibexa\Contracts\Core\Persistence\ValueObject;
use Ibexa\Contracts\Core\Repository\Values\Content\Relation;

/**
 * CreateStruct representing a relation between content.
 */
class CreateStruct extends ValueObject
{
    /**
     * Source Content ID.
     *
     * @var mixed
     */
    public $sourceContentId;

    /**
     * Source Content Version number.
     *
     * @var int
     */
    public $sourceContentVersionNo;

    /**
     * Source content type Field Definition Id.
     *
     * @var mixed
     */
    public $sourceFieldDefinitionId;

    /**
     * Destination Content ID.
     *
     * @var mixed
     */
    public $destinationContentId;

    /**
     * Type bitmask.
     *
     * @see Relation::COMMON
     * @see Relation::EMBED
     * @see Relation::LINK
     * @see Relation::FIELD
     *
     * @var int
     */
    public $type;
}
