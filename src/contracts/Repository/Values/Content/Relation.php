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
 * @property-read \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo $sourceContentInfo Calls {@see Relation::getSourceContentInfo()}
 * @property-read \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo $destinationContentInfo Calls {@see Relation::getDestinationContentInfo()}
 * @property-read int $type The relation type bitmask containing one or more of Relation::COMMON, Relation::EMBED, Relation::LINK, Relation::FIELD
 */
abstract class Relation extends ValueObject
{
    /**
     * The relation type COMMON is a general relation between object set by a user.
     *
     * @var int
     *
     * @deprecated 5.0.0 const is deprecated and will be removed in 6.0.0. Use {@see RelationType::COMMON} instead.
     */
    public const COMMON = 1;

    /**
     * the relation type EMBED is set for a relation which is anchored as embedded link in an attribute value.
     *
     * @var int
     *
     * @deprecated 5.0.0 const is deprecated and will be removed in 6.0.0. Use {@see RelationType::EMBED} instead.
     */
    public const EMBED = 2;

    /**
     * the relation type LINK is set for a relation which is anchored as link in an attribute value.
     *
     * @var int
     *
     * @deprecated 5.0.0 const is deprecated and will be removed in 6.0.0. Use {@see RelationType::LINK} instead.
     */
    public const LINK = 4;

    /**
     * the relation type FIELD is set for a relation which is part of an relation attribute value.
     *
     * @var int
     *
     * @deprecated 5.0.0 const is deprecated and will be removed in 6.0.0. Use {@see RelationType::FIELD} instead.
     */
    public const FIELD = 8;

    /**
     * the relation type ASSET is set for a relation to asset in an attribute value.
     *
     * @var int
     *
     * @deprecated 5.0.0 const is deprecated and will be removed in 6.0.0. Use {@see RelationType::ASSET} instead.
     */
    public const ASSET = 16;

    /**
     * Id of the relation.
     *
     * @var mixed
     */
    protected $id;

    /**
     * Source content type Field Definition Id.
     * For relation not of type RelationType::COMMON this field denotes the field definition id
     * of the attribute where the relation is anchored.
     *
     * @var string
     */
    protected $sourceFieldDefinitionIdentifier;

    /**
     * the content of the source content of the relation.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo
     */
    abstract public function getSourceContentInfo(): ContentInfo;

    /**
     * the content of the destination content of the relation.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo
     */
    abstract public function getDestinationContentInfo(): ContentInfo;

    /**
     * The relation type bitmask.
     *
     * @see Relation::COMMON
     * @see Relation::EMBED
     * @see Relation::LINK
     * @see Relation::FIELD
     * @see Relation::ASSET
     *
     * @var int
     */
    protected $type;
}
