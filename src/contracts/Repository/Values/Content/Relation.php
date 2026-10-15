<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content;

use Ibexa\Contracts\Core\Repository\Values\ValueObject;

/**
 * Class representing a relation between content.
 *
 * @property-read mixed $id the internal id of the relation
 * @property-read string $sourceFieldDefinitionIdentifier the field definition identifier of the field where this relation is anchored if the relation is of type EMBED, LINK, or ATTRIBUTE
 * @property-read ContentInfo $sourceContentInfo Calls {@see Relation::getSourceContentInfo()}
 * @property-read ContentInfo $destinationContentInfo Calls {@see Relation::getDestinationContentInfo()}
 * @property-read int $type The relation type bitmask containing one or more of RelationType::COMMON, RelationType::EMBED, RelationType::LINK, RelationType::FIELD
 */
abstract class Relation extends ValueObject
{
    /**
     * ID of the relation.
     */
    protected int $id;

    /**
     * Source content type Field Definition identifier.
     * For relation not of type RelationType::COMMON this field denotes the field definition identifier
     * of the attribute where the relation is anchored.
     */
    protected ?string $sourceFieldDefinitionIdentifier = null;

    /**
     * The relation type bitmask.
     *
     * @see RelationType::COMMON
     * @see RelationType::EMBED
     * @see RelationType::LINK
     * @see RelationType::FIELD
     * @see RelationType::ASSET
     */
    protected int $type;

    /**
     * the content of the source content of the relation.
     *
     * @return ContentInfo
     */
    abstract public function getSourceContentInfo(): ContentInfo;

    /**
     * the content of the destination content of the relation.
     *
     * @return ContentInfo
     */
    abstract public function getDestinationContentInfo(): ContentInfo;
}
