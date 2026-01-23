<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\ContentType;

use DateTimeInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Contracts\Core\Repository\Values\MultiLanguageDescription;
use Ibexa\Contracts\Core\Repository\Values\MultiLanguageName;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;

/**
 * this class represents a content type value.
 *
 * @property-read ContentTypeGroup[] $contentTypeGroups calls getContentTypeGroups
 * @property-read FieldDefinitionCollection $fieldDefinitions calls getFieldDefinitions() or on access getFieldDefinition($fieldDefIdentifier)
 * @property-read mixed $id the id of the content type
 * @property-read int $status the status of the content type. One of ContentType::STATUS_DEFINED|ContentType::STATUS_DRAFT|ContentType::STATUS_MODIFIED
 * @property-read string $identifier @deprecated 4.6.7 accessing magic getter is deprecated and will be removed in 5.0.0. Use {@see ContentType::getIdentifier()} instead.
 * @property-read \DateTime $creationDate the date of the creation of this content type
 * @property-read \DateTime $modificationDate the date of the last modification of this content type
 * @property-read mixed $creatorId the user id of the creator of this content type
 * @property-read mixed $modifierId the user id of the user which has last modified this content type
 * @property-read string $remoteId a global unique id of the content object
 * @property-read string $urlAliasSchema URL alias schema. If nothing is provided, $nameSchema will be used instead.
 * @property-read string $nameSchema  The name schema.
 * @property-read string $mainLanguageCode the main language of the content type names and description used for fallback.
 * @property-read bool $defaultAlwaysAvailable if an instance of a content type is created the always available flag is set by default this this value.
 * @property-read string[] $languageCodes array of language codes used by content type translations.
 * @property-read int $defaultSortField Specifies which property the child locations should be sorted on by default when created. Valid values are found at {@link Location::SORT_FIELD_*}
 * @property-read int $defaultSortOrder Specifies whether the sort order should be ascending or descending by default when created. Valid values are {@link Location::SORT_ORDER_*}
 */
abstract class ContentType extends ValueObject implements MultiLanguageName, MultiLanguageDescription
{
    /** @var int Status constant for defined (aka "published") Type */
    public const int STATUS_DEFINED = 0;

    /** @var int Status constant for draft (aka "temporary") Type */
    public const int STATUS_DRAFT = 1;

    /** @var int Status constant for modified (aka "deferred for publishing") Type */
    public const int STATUS_MODIFIED = 2;

    /**
     * Content type ID.
     */
    protected int $id;

    /**
     * The status of the content type.
     *
     * @var int One of Type::STATUS_DEFINED|Type::STATUS_DRAFT|Type::STATUS_MODIFIED
     */
    protected int $status;

    /**
     * String identifier of a content type.
     */
    protected string $identifier;

    /**
     * Creation date of the content type.
     */
    protected DateTimeInterface $creationDate;

    /**
     * Modification date of the content type.
     */
    protected DateTimeInterface $modificationDate;

    /**
     * Creator user id of the content type.
     */
    protected int $creatorId;

    /**
     * Modifier user id of the content type.
     */
    protected int $modifierId;

    /**
     * Unique remote ID of the content type.
     */
    protected string $remoteId;

    /**
     * URL alias schema.
     *
     * If nothing is provided, $nameSchema will be used instead.
     */
    protected string $urlAliasSchema;

    /**
     * Name schema.
     *
     * Can be composed of FieldDefinition identifier place holders.
     * These place holders must comply this pattern : <field_definition_identifier>.
     * An OR condition can be used :
     * <field_def|other_field_def>
     * In this example, field_def will be used if available. If not, other_field_def will be used for content name generation
     */
    protected string $nameSchema;

    /**
     * A flag used to hint if content of this type may have children or not. It is highly recommended to respect this flag and not create/move content below non-containers.
     * But this flag is not considered as part of the content model and the API will not in any way enforce this flag to be respected.
     */
    protected bool $isContainer = false;

    /**
     * If an instance of a content type is created the always available flag is set
     * by default to this value.
     */
    protected bool $defaultAlwaysAvailable = true;

    /**
     * Specifies which property the child locations should be sorted on by default when created.
     *
     * Valid values are found at {@link Location::SORT_FIELD_*}
     */
    protected int $defaultSortField = Location::SORT_FIELD_PUBLISHED;

    /**
     * Specifies whether the sort order should be ascending or descending by default when created.
     *
     * Valid values are {@link Location::SORT_ORDER_*}
     */
    protected int $defaultSortOrder = Location::SORT_ORDER_DESC;

    /**
     * List of language codes used by translations.
     *
     * @var string[]
     */
    protected array $languageCodes = [];

    /**
     * This method returns the content type groups this content type is assigned to.
     *
     * @return ContentTypeGroup[]
     */
    abstract public function getContentTypeGroups(): array;

    /**
     * This method returns the content type field definitions from this type.
     */
    abstract public function getFieldDefinitions(): FieldDefinitionCollection;

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * This method returns the field definition for the given identifier.
     *
     * @param string $fieldDefinitionIdentifier
     *
     * @return FieldDefinition|null
     */
    public function getFieldDefinition(string $fieldDefinitionIdentifier): ?FieldDefinition
    {
        if ($this->hasFieldDefinition($fieldDefinitionIdentifier)) {
            return $this->getFieldDefinitions()->get($fieldDefinitionIdentifier);
        }

        return null;
    }

    /**
     * This method returns true if the field definition for the given identifier exists.
     */
    public function hasFieldDefinition(string $fieldDefinitionIdentifier): bool
    {
        return $this->getFieldDefinitions()->has($fieldDefinitionIdentifier);
    }

    /**
     * Returns true if field definition with given field type identifier exists.
     */
    public function hasFieldDefinitionOfType(string $fieldTypeIdentifier): bool
    {
        return $this->getFieldDefinitions()->anyOfType($fieldTypeIdentifier);
    }

    /**
     * Returns collection of the field definition for the given field type identifier.
     */
    public function getFieldDefinitionsOfType(string $fieldTypeIdentifier): FieldDefinitionCollection
    {
        return $this->getFieldDefinitions()->filterByType($fieldTypeIdentifier);
    }

    /**
     * Returns true if field definition with given field type identifier or null.
     */
    public function getFirstFieldDefinitionOfType(string $fieldTypeIdentifier): ?FieldDefinition
    {
        $fieldDefinitionsOfType = $this->getFieldDefinitionsOfType($fieldTypeIdentifier);
        if (!$fieldDefinitionsOfType->isEmpty()) {
            return $fieldDefinitionsOfType->first();
        }

        return null;
    }

    public function isContainer(): bool
    {
        return $this->isContainer;
    }
}
