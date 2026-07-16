<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\ContentType;

use DateTimeInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;

/**
 * This class is used for creating content types.
 *
 * @property FieldDefinitionCreateStruct[] $fieldDefinitions the collection of field definitions
 */
abstract class ContentTypeCreateStruct extends ValueObject
{
    /**
     * String unique identifier of a type.
     *
     * Required.
     */
    public string $identifier;

    /**
     * Main language Code.
     *
     * Required.
     */
    public ?string $mainLanguageCode = null;

    /**
     * The remote id.
     */
    public ?string $remoteId = null;

    /**
     * URL alias schema.
     */
    public ?string $urlAliasSchema = null;

    /**
     * Name schema.
     */
    public ?string $nameSchema = null;

    /**
     * Determines if the type is a container.
     */
    public bool $isContainer = false;

    /**
     * Specifies which property the child locations should be sorted on by default when created.
     *
     * Valid values are found at {@link Location::SORT_FIELD_*}
     */
    public int $defaultSortField = Location::SORT_FIELD_PUBLISHED;

    /**
     * Specifies whether the sort order should be ascending or descending by default when created.
     *
     * Valid values are {@link Location::SORT_ORDER_*}
     */
    public int $defaultSortOrder = Location::SORT_ORDER_DESC;

    /**
     * If an instance of a content type is created the always available flag is set
     * by default this this value.
     */
    public bool $defaultAlwaysAvailable = true;

    /**
     * An array of names with languageCode keys.
     *
     * Required. - at least one name in the main language is required
     *
     * @var array<string, string> an array of string
     */
    public array $names = [];

    /**
     * An array of descriptions with languageCode keys.
     *
     * @var array<string, string> an array of string
     */
    public array $descriptions = [];

    /**
     * If set this value overrides the current user as creator.
     */
    public ?int $creatorId = null;

    /**
     * If set this value overrides the current time for creation.
     */
    public ?DateTimeInterface $creationDate = null;

    /**
     * Adds a new field definition.
     */
    abstract public function addFieldDefinition(FieldDefinitionCreateStruct $fieldDef): void;
}
