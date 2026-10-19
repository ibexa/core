<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Contracts\Core\Persistence\Content;

use Ibexa\Contracts\Core\Persistence\ValueObject;
use Ibexa\Contracts\Core\Repository\Values\Content\RelationType;

/**
 * Class representing a relation between content.
 */
class Relation extends ValueObject
{
    /**
     * Id of the relation.
     *
     * @var mixed
     */
    public $id;

    /**
     * Source Content ID.
     *
     * @var mixed
     */
    public $sourceContentId;

    /**
     * Source Content Version.
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
     * Bitmask of {@see RelationType} values.
     *
     * @var int
     */
    public $type;
}
